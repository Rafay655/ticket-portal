(function (global) {
  'use strict';

  var queue = Promise.resolve();
  var handledRequests = new WeakSet();
  var confirmingButtons = new WeakSet();
  var titles = { success: 'Success', error: 'Something went wrong', warning: 'Please note', info: 'Information', question: 'Please confirm' };

  function text(value) {
    if (value == null) return '';
    if (Array.isArray(value)) return value.map(text).filter(Boolean).join('\n');
    if (typeof value === 'object') return text(value.message || value.errors || value.error || Object.values(value));
    return String(value);
  }

  function enqueue(options, confirmation) {
    options = options || {};
    var run = function () {
      var icon = options.type || options.icon || 'info';
      if (!titles[icon]) icon = 'info';
      return global.Swal.fire({
        icon: icon,
        titleText: options.title || titles[icon],
        text: text(options.message),
        toast: false,
        showConfirmButton: true,
        confirmButtonText: options.confirmButtonText || 'OK',
        confirmButtonColor: '#0a3aa9',
        showCancelButton: Boolean(confirmation),
        cancelButtonText: options.cancelButtonText || 'Cancel',
        allowOutsideClick: false,
        allowEscapeKey: Boolean(confirmation),
        heightAuto: false,
        returnFocus: true,
        target: global.document.querySelector('.modal.show') || global.document.body,
        customClass: { container: 'app-message-container', popup: 'app-message-modal' }
      }).then(function (result) {
        if (result.isConfirmed && options.redirect) global.location.assign(options.redirect);
        return result;
      });
    };
    var pending = queue.then(run);
    // A rendering failure must not prevent a later message from opening.
    queue = pending.catch(function () {});
    return pending;
  }

  var api = {
    show: function (options) { return enqueue(options, false); },
    showMany: function (messages) {
      return Promise.all((messages || []).map(function (message) { return api.show(message); }));
    },
    confirm: function (options) { return enqueue(options, true); },
    requestError: function (xhr) {
      if (xhr && typeof xhr === 'object') {
        if (handledRequests.has(xhr)) return Promise.resolve();
        handledRequests.add(xhr);
      }
      var data = (xhr && xhr.responseJSON) || {};
      var message = text(data.errors) || text(data.message || data.error);
      if (!message) {
        message = xhr && xhr.status === 419
          ? 'Your session has expired. Please refresh the page and try again.'
          : 'The request could not be completed. Please try again.';
      }
      return api.show({ type: 'error', message: message, redirect: data.code === 'duplicate_active_ticket' ? data.redirect_url : undefined });
    }
  };

  global.AppMessages = api;
  // Retain the existing helper name used by AJAX forms and delete actions.
  global.basciAlert = function (options) { return api.show(options); };
  global.deleteAlert = function () {
    return api.confirm({ type: 'warning', title: 'Do you want to delete it?', message: 'This action cannot be undone.', confirmButtonText: 'Yes, delete it!' });
  };

  global.document.addEventListener('click', function (event) {
    var button = event.target.closest('[data-confirm-message]');
    if (!button || !button.form || button.disabled) return;
    event.preventDefault();
    event.stopPropagation();
    if (confirmingButtons.has(button)) return;
    confirmingButtons.add(button);
    api.confirm({ type: 'warning', message: button.getAttribute('data-confirm-message'), confirmButtonText: 'Yes, continue' }).then(function (result) {
      if (result.isConfirmed) button.form.requestSubmit(button);
    }).finally(function () { confirmingButtons.delete(button); });
  });
})(window);
