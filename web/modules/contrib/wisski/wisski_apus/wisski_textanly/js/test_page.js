(function($, window, undefined) {

  window.WissKI = window.WissKI || {};
  window.WissKI.textanly = window.WissKI.textanly || {};

  $('#analyse_do').click(function() {

    var text = $('#analyse_text')[0].value;
    var pipe = $('#analyse_pipe')[0];
    pipe = pipe.value;
//    The .value prop contains the value of the field directly, not the index
//    pipe = pipe.options[pipe.value].textContent;
    var ticket = 'test_page_ticket_' + Math.floor((Math.random() * 10000));
    analyse_interval = null;

    $('#analyse_log').html('');
    $('#analyse_result').html('Processing ' + ticket + '...');
    // escape text

    window.WissKI.pipe.logReload = 0;

    $.ajax({
      url: window.drupalSettings.path.baseUrl + 'wisski/apus/pipe/analyse',
      content_type : "application/json",
      type : "POST",
      data : 'query=' + JSON.stringify({"ticket": ticket, "data": {text: text}, "pipe": pipe}),
      success : function (response) {
        var html = window.WissKI.pipe.syntaxhilite(response);
        
console.log("json1",response);
//        var json = $.parseJSON(response);
        var json = response;
        if (json.hasOwnProperty('annos')) {
          var annos = json.annos;
          var t = text;

          annos.sort(function(a, b) { return b.range[0] - a.range[0]; });
          var laststart = t.length;
          for (i in annos) {
            var a = annos[i];
            if (laststart < a.range[1]) continue;
            laststart = a.range[0];
            t = t.substring(0, a.range[0]) + '<span style="border:double green 1px;" title="' + a.class + ': ' + a.range.join(', ') + '">' + t.substring(a.range[0], a.range[1]) + '</span>' + t.substring(a.range[1]);
          }

          html = t + '<hr/>' + html;
        }

        $('#analyse_result').html(html);
        window.WissKI.pipe.logReload = 0;
        window.WissKI.pipe.getLogs(ticket, 'all');
      },
      error : function() {
        $('#analyse_result').html('An error occured');
        window.WissKI.pipe.logReload = 2;
      }
    });
    
    return false;

  });

  var log = $('#analyse_log')[0].textContent;
  if (log) $('#analyse_log').html(WissKI.pipe.syntaxhilite(log));

  var log = $('#analyse_result')[0].textContent;
  if (log) $('#analyse_result').html(WissKI.pipe.syntaxhilite(log));

})(jQuery, window);

