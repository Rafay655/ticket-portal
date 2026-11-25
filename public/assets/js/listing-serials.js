(function (global) {
  'use strict';
  function bindSerialNumbers() {
    var $ = global.jQuery;
    if (!$ || !$.fn.dataTable) return;
    $(global.document).on('draw.dt.listingSerials', function (event, settings) {
      if (!settings || !settings.nTable || !settings.nTable.hasAttribute('data-serial-offset')) return;
      var header = settings.nTable.querySelector('thead th[data-serial-column]');
      if (!header) return;
      var api = new $.fn.dataTable.Api(settings);
      var start = Number(settings.nTable.getAttribute('data-serial-offset')) || 0;
      var pageStart = api.page.info().start;
      var offset = settings.oFeatures.bServerSide ? pageStart : start + pageStart;
      api.column(header.cellIndex, { page: 'current', order: 'applied', search: 'applied' }).nodes().each(function (cell, index) {
        cell.textContent = offset + index + 1;
      });
    });
  }
  // Bind to the final jQuery/DataTables instance after all layout scripts load.
  if (global.document.readyState === 'loading') {
    global.document.addEventListener('DOMContentLoaded', bindSerialNumbers);
  } else {
    bindSerialNumbers();
  }
})(window);
