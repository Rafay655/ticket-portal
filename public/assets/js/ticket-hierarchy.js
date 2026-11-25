(function (global) {
  'use strict';
  function setup(group) {
    var type = group.querySelector('[data-ticket-type]');
    var category = group.querySelector('[data-ticket-category]');
    var subCategory = group.querySelector('[data-ticket-sub-category]');
    var payload = group.querySelector('[data-ticket-hierarchy-options]');
    if (!type || !category || !payload) return;
    var data = JSON.parse(payload.textContent);
    function fill(select, items, label, selected) {
      select.replaceChildren();
      select.appendChild(new Option(label, ''));
      items.forEach(function (item) { select.appendChild(new Option(item.name, String(item.id))); });
      select.value = selected || '';
      select.disabled = items.length === 0;
    }
    function updateSubCategory(selected) {
      if (!subCategory) return;
      var items = data.subCategories.filter(function (item) { return String(item.ticket_category_id) === category.value; });
      fill(subCategory, items, category.value ? 'Select Ticket Sub-Category' : 'Select Ticket Category first', selected);
      category.setCustomValidity(category.value && items.length === 0 ? 'This Ticket Category has no mapped Ticket Sub-Categories. Please contact your administrator.' : '');
    }
    function updateCategory(selected, selectedSubCategory) {
      var items = data.categories.filter(function (item) { return String(item.ticket_type_id) === type.value; });
      fill(category, items, type.value ? 'Select Ticket Category' : 'Select Ticket Type first', selected);
      type.setCustomValidity(type.value && items.length === 0 ? 'This Ticket Type has no mapped Ticket Categories. Please contact your administrator.' : '');
      category.setCustomValidity('');
      updateSubCategory(selectedSubCategory);
    }
    type.addEventListener('change', function () { updateCategory('', ''); });
    category.addEventListener('change', function () { updateSubCategory(''); });
    updateCategory(category.getAttribute('data-selected'), subCategory && subCategory.getAttribute('data-selected'));
  }
  function initialize() { global.document.querySelectorAll('[data-ticket-hierarchy]').forEach(setup); }
  global.TicketHierarchyForm = { setup: setup };
  if (global.document.readyState === 'loading') global.document.addEventListener('DOMContentLoaded', initialize);
  else initialize();
})(window);
