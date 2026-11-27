-- Flexi Desk: approved categories, existing Sub-Categories, priorities and Ticket Settings.
-- Select the portal database first. Back it up before execution.
-- Prerequisite: both 2026_11_25 hierarchy migrations must already be applied.
-- Run the WHOLE file in one MySQL/phpMyAdmin session. Requires CREATE ROUTINE permission.
-- Existing IDs, tickets, setting rows and escalation rows are preserved.
-- Stops/rolls back if records are missing or historical type assignments conflict.

DELIMITER $$
CREATE PROCEDURE flexidesk_apply_category_mapping_20261125()
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;
    CREATE TEMPORARY TABLE fd_category_map (
        code VARCHAR(10) PRIMARY KEY,
        category_name VARCHAR(255) NOT NULL,
        type_name VARCHAR(255) NOT NULL
    );
    INSERT INTO fd_category_map VALUES
        ('ITA', 'IT & Asset Management', 'HRPSP POC'),
        ('HRE', 'HR & Employee Separation', 'HRPSP POC'),
        ('PAY', 'Payroll & Compensation', 'HRPSP POC'),
        ('COM', 'Commission & Incentives', 'HRPSP POC'),
        ('STM', 'Sales & Target Management', 'Jazzcash'),
        ('WER', 'Workplace & Employee Relations', 'Jazzcash');

    CREATE TEMPORARY TABLE fd_subcategory_map (
        sub_name VARCHAR(255) PRIMARY KEY,
        code VARCHAR(10) NOT NULL,
        recommended_priority VARCHAR(20) NULL
    );
    INSERT INTO fd_subcategory_map VALUES
        ('Laptop Issuance Status Required', 'ITA', 'Medium'),
        ('Resignation Acceptance Letter Required', 'HRE', 'Medium'),
        ('Experience Letter Required', 'HRE', 'Medium'),
        ('Final Settlement Calculation Issue', 'HRE', 'High'),
        ('Commission Miscalculations', 'COM', NULL),
        ('Salary Not Disbursed', 'PAY', 'Urgent'),
        ('Payslip Not Available', 'PAY', 'Medium'),
        ('Payslip Discrepancy', 'PAY', NULL),
        ('Salary Discrepancy', 'PAY', 'High'),
        ('Fuel & CMA Not Paid', 'PAY', NULL),
        ('Tax Over Deduction', 'PAY', NULL),
        ('Discrepancy in Pay for Leave or Absence', 'PAY', NULL),
        ('Commission Not Disbursed', 'COM', NULL),
        ('Incentive Not Disbursed', 'COM', NULL),
        ('Office Environment and Facilities Issues', 'WER', NULL),
        ('Lack of Transparency in Target Allocation', 'STM', 'Medium'),
        ('Favoritism in Target Setting', 'STM', 'High'),
        ('Unsatisfactory Work Environment', 'WER', NULL),
        ('Toxic Workplace Atmosphere', 'WER', 'High'),
        ('Dispute with Management', 'WER', 'High');

    IF EXISTS (
        SELECT 1 FROM fd_category_map m
        WHERE (SELECT COUNT(*) FROM ticket_types t WHERE LOWER(TRIM(t.name)) = LOWER(m.type_name)) <> 1
    ) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Missing or ambiguous Ticket Type; no changes applied.';
    END IF;

    IF EXISTS (
        SELECT 1 FROM fd_subcategory_map m
        WHERE NOT EXISTS (SELECT 1 FROM ticket_sub_types s
            WHERE LOWER(REPLACE(TRIM(s.name), ' ', '')) = LOWER(REPLACE(m.sub_name, ' ', '')))
    ) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'An expected Sub-Category is missing; no changes applied.';
    END IF;

    IF EXISTS (
        SELECT 1 FROM ticket_categories c
        JOIN fd_category_map m ON m.code = c.code
        JOIN ticket_types t ON LOWER(TRIM(t.name)) = LOWER(m.type_name)
        WHERE c.name <> m.category_name OR (c.ticket_type_id IS NOT NULL AND c.ticket_type_id <> t.id)
    ) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Category code conflicts with an existing category; no changes applied.';
    END IF;

    CREATE TEMPORARY TABLE fd_resolved_subcategories AS
        SELECT s.id AS sub_id, m.code, m.recommended_priority, t.id AS type_id
        FROM ticket_sub_types s
        JOIN fd_subcategory_map m ON LOWER(REPLACE(TRIM(s.name), ' ', '')) = LOWER(REPLACE(m.sub_name, ' ', ''))
        JOIN fd_category_map c ON c.code = m.code
        JOIN ticket_types t ON LOWER(TRIM(t.name)) = LOWER(c.type_name);

    IF EXISTS (
        SELECT 1 FROM fd_resolved_subcategories r
        JOIN ticket_settings ts ON ts.ticket_sub_type_id = r.sub_id
        WHERE ts.ticket_type_id <> r.type_id
    ) OR EXISTS (
        SELECT 1 FROM fd_resolved_subcategories r
        JOIN tickets tk ON tk.ticketsubtype_id = r.sub_id
        WHERE tk.tickettype_id <> r.type_id
    ) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Previous Ticket Type assignment conflicts; review records before mapping.';
    END IF;

    INSERT INTO ticket_categories (name, code, ticket_type_id, created_at, updated_at)
        SELECT m.category_name, m.code, t.id, NOW(), NOW()
        FROM fd_category_map m
        JOIN ticket_types t ON LOWER(TRIM(t.name)) = LOWER(m.type_name)
        WHERE NOT EXISTS (SELECT 1 FROM ticket_categories c WHERE c.code = m.code);

    UPDATE ticket_categories c
        JOIN fd_category_map m ON m.code = c.code
        JOIN ticket_types t ON LOWER(TRIM(t.name)) = LOWER(m.type_name)
        SET c.ticket_type_id = t.id, c.updated_at = NOW()
        WHERE c.ticket_type_id IS NULL;

    IF EXISTS (
        SELECT 1 FROM fd_resolved_subcategories r
        JOIN ticket_sub_types s ON s.id = r.sub_id
        JOIN ticket_categories c ON c.code = r.code
        WHERE s.ticket_category_id IS NOT NULL AND s.ticket_category_id <> c.id
    ) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Sub-Category already mapped elsewhere; no changes applied.';
    END IF;

    UPDATE ticket_sub_types s
        JOIN fd_resolved_subcategories r ON r.sub_id = s.id
        JOIN ticket_categories c ON c.code = r.code
        SET s.ticket_category_id = c.id,
            s.priority = COALESCE(r.recommended_priority, s.priority),
            s.updated_at = NOW()
        WHERE s.ticket_category_id IS NULL
           OR (r.recommended_priority IS NOT NULL AND s.priority <> r.recommended_priority);

    -- Add missing settings only. Existing settings IDs and escalation links stay intact.
    INSERT INTO ticket_settings (ticket_type_id, ticket_sub_type_id, created_at, updated_at)
        SELECT r.type_id, r.sub_id, NOW(), NOW()
        FROM fd_resolved_subcategories r
        WHERE NOT EXISTS (SELECT 1 FROM ticket_settings ts
            WHERE ts.ticket_type_id = r.type_id AND ts.ticket_sub_type_id = r.sub_id);

    COMMIT;

    SELECT t.name AS ticket_type, c.code, c.name AS ticket_category,
           s.id AS existing_subcategory_id, s.name AS ticket_subcategory, s.priority,
           ts.id AS setting_id
        FROM fd_resolved_subcategories r
        JOIN ticket_types t ON t.id = r.type_id
        JOIN ticket_sub_types s ON s.id = r.sub_id
        JOIN ticket_categories c ON c.id = s.ticket_category_id
        LEFT JOIN ticket_settings ts ON ts.ticket_type_id = r.type_id AND ts.ticket_sub_type_id = r.sub_id
        ORDER BY t.name, c.code, s.name;

    DROP TEMPORARY TABLE fd_resolved_subcategories;
    DROP TEMPORARY TABLE fd_subcategory_map;
    DROP TEMPORARY TABLE fd_category_map;
END$$
DELIMITER ;

CALL flexidesk_apply_category_mapping_20261125();
DROP PROCEDURE flexidesk_apply_category_mapping_20261125;
