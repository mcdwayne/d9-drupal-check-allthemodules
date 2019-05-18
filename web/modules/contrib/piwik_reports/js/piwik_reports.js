(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.PiwikReportsBehavior = {
    attach: function (context, settings) {
      // can access setting from 'drupalSettings';
        var pk_url = drupalSettings.piwik_reports.piwikJS.url;
        var query_string = drupalSettings.piwik_reports.piwikJS.query_string + '&jsoncallback=?';
        console.log(query_string);
        var header = "<table class='sticky-enabled sticky-table'><tbody></tbody></table>";
        // Add the table and show "Loading data..." status message for long running requests.
        $("#piwikpageviews").html(header);
        $("#piwikpageviews > table > tbody").html("<tr><td>" + Drupal.t('Loading data...') + "</td></tr>");
        // Get data from remote Piwik server.
        $.getJSON(pk_url + 'index.php?' + query_string, function(data){
          var item = '';
          $.each(data, function(key, val) {
            item = val;
          });
          var pk_content = "";
          if (item != '') {
            if (item.nb_visits) {
              pk_content += "<tr><td>" + Drupal.t('Visits') + "</td>";
              pk_content += "<td>" + item.nb_visits + "</td></tr>" ;
            }
            if (item.nb_hits) {
              pk_content += "<tr><td>" + Drupal.t('Page views') + "</td>";
              pk_content += "<td>" + item.nb_hits + "</td></tr>" ;
            }
          }
          // Push data into table and replace "Loading data..." status message.
          if (pk_content) {
            $("#piwikpageviews > table > tbody").html(pk_content);
          }
          else {
            $("#piwikpageviews > table > tbody > tr > td").html(Drupal.t('No data available.'));
          }
        });
    }
  };
})(jQuery, Drupal, drupalSettings);