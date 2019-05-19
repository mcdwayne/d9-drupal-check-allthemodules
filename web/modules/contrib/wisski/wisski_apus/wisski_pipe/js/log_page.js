(function($, window, undefined) {

  window.WissKI = window.WissKI || {};
  window.WissKI.pipe = window.WissKI.pipe || {};

  window.WissKI.pipe.logReload = 0;

  window.WissKI.pipe.getLogs = function(ticket, levels) {
    
    var query = {"ticket": ticket};
    if (typeof "levels" == 'array') {
      query->levels = levels;
    }

    $.ajax({
      url: drupalSettings.path.baseUrl + 'wisski/apus/pipe/log/json',
      content_type : "application/json",
      type : "POST",
      data : 'query=' . JSON.stringify(query),
      success : function (response) {
        var html = WissKI.pipe.syntaxhilite(response);
        $('#analyse_log').html(html);
        if (WissKI.pipe.logReload != 0) {
          if (WissKI.pipe.logReload == 2) WissKI.pipe.logReload = 0;
          window.setTimeout(function() {WissKI.pipe.getLogs(ticket, levels)}, 1500);
        }
      }
    });

  }

  $().ready(function() {
    var d = drupalSettings;
    if (d.WissKI && d.WissKI.pipe && d.WissKI.pipe.log_page && d.WissKI.pipe.log_page.ticket) {
      WissKI.pipe.getLogs(d.WissKI.pipe.log_page.ticket, null);
    }
  });

})(jQuery, window);


