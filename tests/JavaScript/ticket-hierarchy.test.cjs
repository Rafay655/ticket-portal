const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('public/assets/js/ticket-hierarchy.js', 'utf8');
class Select {
  constructor(value = '', selected = '') { this.options = []; this.current = value; this.selected = selected; this.listeners = {}; this.validity = ''; }
  get value() { return this.current; }
  set value(value) { this.current = this.options.some(option => option.value === String(value)) ? String(value) : ''; }
  replaceChildren() { this.options = []; this.current = ''; }
  appendChild(option) { this.options.push(option); }
  addEventListener(name, callback) { this.listeners[name] = callback; }
  getAttribute() { return this.selected; }
  setCustomValidity(message) { this.validity = message; }
  change(value) { this.current = String(value); this.listeners.change(); }
}
function setup({ typeId = '1', categoryId = '10', subId = '100', withSub = true, categoryName = 'Facilities' } = {}) {
  const type = new Select(typeId), category = new Select('', categoryId), sub = withSub ? new Select('', subId) : null;
  const data = { categories: [{ id: 10, name: categoryName, ticket_type_id: 1 }, { id: 20, name: 'Other', ticket_type_id: 2 }], subCategories: [{ id: 100, name: 'Office', ticket_category_id: 10 }, { id: 200, name: 'Other child', ticket_category_id: 20 }] };
  const elements = { '[data-ticket-type]': type, '[data-ticket-category]': category, '[data-ticket-sub-category]': sub, '[data-ticket-hierarchy-options]': { textContent: JSON.stringify(data) } };
  const group = { querySelector: selector => elements[selector] };
  const document = { readyState: 'complete', querySelectorAll: () => [group] };
  vm.runInNewContext(source, { window: { document }, JSON, String, Option: function (text, value) { this.text = text; this.value = value; } });
  return { type, category, sub };
}

test('restores the saved three-level selection', () => {
  const h = setup(); assert.equal(h.category.value, '10'); assert.equal(h.sub.value, '100');
  assert.deepEqual(h.category.options.map(o => o.value), ['', '10']);
  assert.deepEqual(h.sub.options.map(o => o.value), ['', '100']);
});
test('changing type clears category and leaf and filters the new parent', () => {
  const h = setup(); h.type.change('2');
  assert.equal(h.category.value, ''); assert.equal(h.sub.value, '');
  assert.deepEqual(h.category.options.map(o => o.value), ['', '20']);
  h.category.change('20'); assert.deepEqual(h.sub.options.map(o => o.value), ['', '200']);
});
test('changing category never preserves an old leaf', () => {
  const h = setup(); h.category.change(''); assert.equal(h.sub.value, ''); assert.equal(h.sub.disabled, true);
});
test('empty type disables descendants without a validation error', () => {
  const h = setup({ typeId: '', categoryId: '', subId: '' });
  assert.equal(h.category.disabled, true); assert.equal(h.sub.disabled, true); assert.equal(h.type.validity, '');
});
test('an unmapped type blocks submission with a clear message', () => {
  const h = setup({ typeId: '99' }); assert.equal(h.category.disabled, true); assert.match(h.type.validity, /no mapped Ticket Categories/);
});
test('cross-parent saved selections are discarded', () => {
  const h = setup({ typeId: '1', categoryId: '20', subId: '200' }); assert.equal(h.category.value, ''); assert.equal(h.sub.value, '');
});
test('sub-category administration supports only the two parent selectors', () => {
  const h = setup({ withSub: false }); assert.equal(h.category.value, '10'); assert.equal(h.category.validity, '');
});
test('labels are assigned as text rather than injected HTML', () => {
  const name = '<img src=x onerror=alert(1)>'; const h = setup({ categoryName: name }); assert.equal(h.category.options[1].text, name);
});