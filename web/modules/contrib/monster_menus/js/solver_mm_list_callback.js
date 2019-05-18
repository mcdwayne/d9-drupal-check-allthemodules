(function ($) {
  mmListCallbacks.mmSolverCallback = function (mmList) {
    var mmtid;
    var val = $(mmList.hiddenElt).val();
    $('#mm-solver-table')
      .slideUp('fast', function () {
        if (val && (mmtid = val.match(/^(\d+)\{/)) && mmtid[0] != '') {
          $.get(drupalSettings.MM.solver_mm_list_callback.path + mmtid[1], function (data) {
            $('#mm-solver-table')
              .html(data)
              .slideDown('fast');
          });
        }
      });
  };

  $('#mm-solver-link a').click(function() {
    setTimeout(function() {
      $('#edit-solver-choose').focus();
    }, 500);
  });
})(jQuery);