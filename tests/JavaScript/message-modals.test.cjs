const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('public/assets/js/sweet_alerts.js', 'utf8');
const tick = () => new Promise(setImmediate);
function harness() {
  const calls = [], redirects = [], events = {};
  const document = { body: {}, querySelector: () => null, addEventListener: (name, handler) => events[name] = handler };
  const window = { document, location: { assign: url => redirects.push(url) }, Swal: { fire: options => new Promise(resolve => calls.push({ options, resolve })) } };
  vm.runInNewContext(source, { window, Promise, WeakSet, Object, Boolean, String });
  return { window, calls, redirects, events, document, api: window.AppMessages };
}

test('messages stay open and show an explicit confirmation button', async () => {
  const h = harness();
  const p = h.api.show({ type: 'success', message: 'Saved.' });
  await tick();
  assert.equal(h.calls[0].options.icon, 'success');
  assert.equal(h.calls[0].options.showConfirmButton, true);
  assert.equal(h.calls[0].options.toast, false);
  assert.equal(h.calls[0].options.allowOutsideClick, false);
  assert.equal(h.calls[0].options.timer, undefined);
  assert.equal(h.calls[0].options.allowEscapeKey, false);
  h.calls[0].resolve({ isConfirmed: true }); await p;
});

test('redirect waits for OK and does not run on dismissal', async () => {
  const h = harness();
  const p = h.window.basciAlert({ type: 'success', message: 'Saved.', redirect: '/tickets' });
  await tick(); assert.equal(h.redirects.length, 0);
  h.calls[0].resolve({ isConfirmed: true }); await p;
  assert.deepEqual(h.redirects, ['/tickets']);
  const p2 = h.api.show({ message: 'Dismiss', redirect: '/other' });
  await tick(); h.calls[1].resolve({ isConfirmed: false }); await p2;
  assert.deepEqual(h.redirects, ['/tickets']);
});

test('multiple messages are queued rather than replacing each other', async () => {
  const h = harness();
  const p = h.api.showMany([{ type: 'error', message: 'First' }, { type: 'success', message: 'Second' }]);
  await tick(); assert.equal(h.calls.length, 1);
  h.calls[0].resolve({ isConfirmed: true }); await tick();
  assert.equal(h.calls.length, 2); assert.equal(h.calls[1].options.text, 'Second');
  h.calls[1].resolve({ isConfirmed: true }); await p;
});

test('server text and titles are rendered as text, not HTML', async () => {
  const h = harness(); const html = '<img src=x onerror=alert(1)>';
  const p = h.api.show({ type: 'error', message: html, title: html });
  await tick(); assert.equal(h.calls[0].options.text, html);
  assert.equal(h.calls[0].options.titleText, html);
  assert.equal(h.calls[0].options.html, undefined);
  assert.equal(h.calls[0].options.title, undefined);
  h.calls[0].resolve({ isConfirmed: true }); await p;
});

test('AJAX errors are deduplicated and include field errors', async () => {
  const h = harness(); const xhr = { status: 422, responseJSON: { errors: { email: ['Invalid email.'], name: ['Missing name.'] } } };
  const p = h.api.requestError(xhr); await h.api.requestError(xhr); await tick();
  assert.equal(h.calls.length, 1); assert.equal(h.calls[0].options.text, 'Invalid email.\nMissing name.');
  h.calls[0].resolve({ isConfirmed: true }); await p;
});

test('duplicate ticket AJAX error opens existing ticket after confirmation', async () => {
  const h = harness(); const p = h.api.requestError({ status: 409, responseJSON: { code: 'duplicate_active_ticket', message: 'Existing ticket.', redirect_url: '/tickets_view/7' } });
  await tick(); assert.equal(h.redirects.length, 0);
  h.calls[0].resolve({ isConfirmed: true }); await p;
  assert.deepEqual(h.redirects, ['/tickets_view/7']);
});

test('delete confirmation still supports cancellation', async () => {
  const h = harness(); const p = h.window.deleteAlert({}); await tick();
  assert.equal(h.calls[0].options.showCancelButton, true);
  assert.equal(h.calls[0].options.confirmButtonText, 'Yes, delete it!');
  h.calls[0].resolve({ isConfirmed: false }); const result = await p;
  assert.equal(result.isConfirmed, false);
});

test('messages from an open Bootstrap modal stay inside its focus scope', async () => {
  const h = harness(); const modal = {}; h.document.querySelector = () => modal;
  const p = h.api.show({ message: 'Check fields.' }); await tick();
  assert.equal(h.calls[0].options.target, modal);
  h.calls[0].resolve({ isConfirmed: true }); await p;
});

test('form confirmation preserves the original submitter and waits for consent', async () => {
  const h = harness(); const submitted = [];
  const button = { disabled: false, getAttribute: () => 'Are you sure?', form: { requestSubmit: b => submitted.push(b) } };
  let prevented = false;
  h.events.click({ target: { closest: () => button }, preventDefault: () => prevented = true, stopPropagation: () => {} });
  await tick(); assert.equal(prevented, true); assert.equal(submitted.length, 0);
  h.calls[0].resolve({ isConfirmed: true }); await tick();
  assert.equal(submitted[0], button);
});