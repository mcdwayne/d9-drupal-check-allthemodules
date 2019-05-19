/**
 * @file
 */

(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.event_extras_sfs = {
    attach: function (context, settings) {
      // Only fire once when page first loads.
      if (context === document) {
        var container = $("#event-sfs");
        container.addClass("ajax-progress-throbber").append("<div class=\"throbber\" style=\"padding-left: 18px;\">Loading</div>");

        var servicepath = 'http://www.stopforumspam.com/api?f=jsonp&ip=' + settings.wee.hostname;

        if (typeof settings.wee.username !== 'undefined') {
          servicepath += '&username=' + settings.wee.username;
        }

        $.ajax({
          url: servicepath,
          dataType: "jsonp",
          success: function (data) {
             container.removeClass("ajax-progress-throbber");
            $(".throbber", container).remove();
            if (!data.success) {
              container.append(data.error + " " + servicepath);
            }
            else {
              var table = $("<table style=\"width: 100%\"></table>").addClass("sfs");
              var row = $("<tr><th>Service path</th><td>" + servicepath + "</td></tr>");
              table.append(row);
              $.each(data, function (index, value) {
                if (index !== "success") {
                  var rowtitle = "";
                  if (index === "ip") {
                    rowtitle = settings.wee.hostname;
                  }
                  else if (index === "username") {
                    rowtitle = settings.wee.username;
                  }
                  var row = ("<tr><th>" + index + "</th><td>" + rowtitle + "</td></tr>");
                  table.append(row);
                  $.each(value, function (index2, value) {
                    var row = $("<tr><th>" + index + " " + index2 + "</th><td>" + value + "</td></tr>");
                    table.append(row);
                  });
                }
              });
              container.append(table);
              $("table tr:even", container).addClass("even");
              $("table tr:odd", container).addClass("odd");
            }
          },
          error: function (object, status) {
            container.removeClass("ajax-progress");
            $(".throbber", container).remove();
            container.append(status);
          }
        })
      }
    }
  };
})(jQuery, Drupal, drupalSettings);
