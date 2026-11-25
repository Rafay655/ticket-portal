<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }
        body {
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        table {
            max-width: 820px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
        }
        .header {
            background: #0a3aa9;
            text-align: center;
            color: white;
            padding: 20px;
        }
        .header p {
            font-size: 24px;
            font-weight: 700;
            margin: 0;
        }
        .content {
            padding: 40px;
        }
        .content p {
            font-size: 16px;
            line-height: 1.5;
            color: #555;
        }
        .footer {
            background-color: #f4f4f4;
            text-align: center;
            font-size: 12px;
            color: #888;
            padding: 10px 0;
        }
    </style>
</head>
<body>
    <table style="width: 100%; font-family: Arial, sans-serif; border-collapse: collapse; margin: auto; border: 1px solid #ddd;">
    <tr>
        <td style="background-color: #0A3AA9; color: white; padding: 20px; text-align: center; font-size: 24px; font-weight: bold;">
            Flexi Desk – Grievances Portal
        </td>
    </tr>
    <tr>
        <td style="padding: 20px; color: #333; font-size: 16px;">
            <p>Dear {{ $user->name }},</p>
            <p>Your account has been successfully created on <strong>Flexi Desk – Grievances Portal</strong>.</p>

            <p>You can log in using the following credentials:</p>

            <p><strong>Portal URL:</strong> <a href="https://ticket.myflexihr.com/login" style="color: #0A3AA9; text-decoration: underline;">https://ticket.myflexihr.com/login</a></p>
            <p><strong>Email:</strong> {{ $user->email }}</p>
            <p><strong>Password:</strong> {{ $password }}</p>
           
            <p><em>Note: For security reasons, please change your password immediately after logging in for the first time.</em></p>
         
            @include('emails.partials.regional-poc')
            <p>Should your grievance remain unresolved beyond the defined resolution timeline, you may escalate the matter by contacting Shan Elahi at <a href="mailto:shan.elahi@guest.jazz.com.pk">shan.elahi@guest.jazz.com.pk</a> and copying <a href="mailto:hr@hrpsp.net">hr@hrpsp.net</a> for record and visibility.</p>
            @include('emails.partials.app-downloads')
          
            <p style="margin-top: 20px; font-weight:700;font-style: italic; font-size: 16px; color: #FF0000;">
                *This is an automated message from Flexi Desk. Please do not reply to this email.
            </p>
        </td>
    </tr>
    <tr>
        <td style="background-color: #0A3AA9; color: white; padding: 20px; font-size: 14px;">
            <div style="max-width: 1200px; margin: auto;text-align:center">
                <h3 style="margin: 0 0 10px 0;">Flexi Desk</h3>
                <p style="margin: 0 0 5px 0;">
                    Powered by <strong>Flexi IT Services (Pvt.) Ltd.</strong> | <strong>HR Perfect Solution Providers (Pvt.) Ltd</strong>
                </p>
                <p style="margin: 0 0 5px 0;">
                    615 - Siddiq Trade Centre, Main Boulevard, Gulberg II, Lahore - 54660
                </p>
                <p style="margin: 0 0 5px 0;">
                    Website: <a href="https://www.hrpsp.net" style="color: #ffffff; text-decoration: underline;">www.hrpsp.net</a>
                    &nbsp;|&nbsp; 
                    Email: <a href="mailto:info@hrpsp.net" style="color: #ffffff; text-decoration: underline;">info@hrpsp.net</a>
                </p>
                <p style="margin: 0;">
                    Phone: 042-35756321 - 042-35786321/22
                </p>
            </div>
        </td>
    </tr>
</table>

</body>
</html>
