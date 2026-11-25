const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('public/assets/js/listing-serials.js', 'utf8');
function harness({ offset = 0, pageStart = 0, serverSide = false, cells = [{}, {}, {}], marked = true, loading = false } = {}) {
  let handler, ready;
  const table = { hasAttribute: () => marked, getAttribute: () => String(offset), querySelector: () => ({ cellIndex: 0 }) };
  const settings = { nTable: table, oFeatures: { bServerSide: serverSide } };
  const document = { readyState: loading ? 'loading' : 'complete', addEventListener: (name, callback) => ready = callback };
  const api = { page: { info: () => ({ start: pageStart }) }, column: (index, selector) => {
    assert.equal(index, 0); assert.equal(selector.page, 'current');
    return { nodes: () => ({ each: callback => cells.forEach(callback) }) };
  } };
  const $ = () => ({ on: (name, callback) => handler = callback });
  $.fn = { dataTable: { Api: function () { return api; } } };
  vm.runInNewContext(source, { window: { document, jQuery: $ }, Number });
  return { draw: () => handler({}, settings), bind: () => ready(), cells };
}

test('first page starts at one', () => {
  const h = harness(); h.draw(); assert.deepEqual(h.cells.map(c => c.textContent), [1, 2, 3]);
});
test('Laravel page offset continues numbering on the next page', () => {
  const h = harness({ offset: 10 }); h.draw(); assert.deepEqual(h.cells.map(c => c.textContent), [11, 12, 13]);
});
test('client pagination adds its offset to the Laravel offset', () => {
  const h = harness({ offset: 20, pageStart: 5 }); h.draw(); assert.deepEqual(h.cells.map(c => c.textContent), [26, 27, 28]);
});
test('sort and filter draws number visible rows in displayed order', () => {
  const cells = [{ textContent: 17, id: 'C' }, { textContent: 11, id: 'A' }];
  const h = harness({ cells }); h.draw();
  assert.deepEqual(cells.map(c => c.textContent), [1, 2]);
  assert.deepEqual(cells.map(c => c.id), ['C', 'A']);
});
test('server pagination offset is not counted twice', () => {
  const h = harness({ offset: 10, pageStart: 10, serverSide: true }); h.draw();
  assert.deepEqual(h.cells.map(c => c.textContent), [11, 12, 13]);
});
test('unmarked detail tables are not modified', () => {
  const cells = [{ textContent: 'Ticket ID' }]; const h = harness({ cells, marked: false }); h.draw();
  assert.equal(cells[0].textContent, 'Ticket ID');
});
test('empty filtered results do not create fake serial rows', () => {
  const h = harness({ cells: [] }); h.draw(); assert.deepEqual(h.cells, []);
});
test('initialization waits for final layout scripts when document is loading', () => {
  const h = harness({ loading: true }); h.bind(); h.draw();
  assert.deepEqual(h.cells.map(c => c.textContent), [1, 2, 3]);
});