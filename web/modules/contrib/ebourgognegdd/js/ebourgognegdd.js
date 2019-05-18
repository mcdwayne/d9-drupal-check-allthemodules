/**
 * @file
 */

(function ($) {
  Drupal.behaviors.ebourgognegdd = {
    attach: function (context, settings) {
      $(document).ready(function () {

        function includeCss() {
          try {
            var cssUrl = drupalSettings.ebourgogne_gdd.css_url;
            jQuery("head").append('<style media="all" type="text/css">@import url("' + cssUrl + '");</style>');
          }
          catch (e) {

          }
        }

        /* if a div with id = gdd exist we load the custom css of the guide */
        if ($("#gdd").length) {

                    includeCss();

                    /*
          var script   = document.createElement("script");
          script.type  = "text/javascript";
          script.src   = "http://localhost:8082/spl-droits-demarches-fo/api/v1/guide/part/N19806/78076.js";
          document.getElementById("gdd").appendChild(script);
           */
        }

        var prev_flux_id,
        saved_structure = {},
        saved_content = {},
        parents = {};

        try {
          var guideBaseUrl = drupalSettings.ebourgogne_gdd.ebou_gdd_fo_api_url;
          var listGuideBaseUrl = drupalSettings.ebourgogne_gdd.ebou_gdd_bo_api_url;
          var organismId = drupalSettings.ebourgogne_gdd.organism_id;
          var apiKey = drupalSettings.ebourgogne_gdd.ebou_api_key;
        }
        catch (e) {

        }

        function get_gdd_list(flux_id, root_id) {
          var url = listGuideBaseUrl + "?root="
           + root_id
           + "&flux="
           + flux_id
           + "&organismId="
           + organismId;

          // Retrieving GDD lists.
          $.ajax({
            url: url,
            type: 'GET',
            async: false,
            beforeSend: function (xhrObj) {
              xhrObj.setRequestHeader('ebou-api-key', apiKey);
            },
            success:function (data) {
              // Save data so that we only have to download it once.
              saved_structure[flux_id][root_id] = data;
              display_gdd_list(root_id, saved_structure[flux_id][root_id]);
            },
            error:function () {
              // TODO: handle error.
              return null;
            },
          });

          return null;
        }

        function display_gdd_list(root_id, datas) {
          $('li.ebou-gdd-item[data-id="' + root_id + '"]').append("<ul>\n</ul>");

          var $ul = $('li.ebou-gdd-item[data-id="' + root_id + '"] ul'),
          html = "",
          data;

          // For each item, we append it to the list and register its callback for item toggling (this way each item has only one callback)
          datas.forEach(function (elt, index, array) {
            html = "<li class=\"ebou-gdd-item\" data-id=\"" + elt['id'] + "\""
            + ((elt['hasChildren']) ? " data-haschildren=\"true\"" : "")
            + "><span"
            + ((elt['hasChildren']) ? " class=\"expand\">" : ">")
            + "</span>"
            + "<span>"
            + elt['text'] + "</li>\n"
            + "</span>";

            $ul.append(html);
            $('li.ebou-gdd-item[data-id="' + elt['id'] + '"] span:first').click(toggle_item);
            $('li.ebou-gdd-item[data-id="' + elt['id'] + '"] span:nth-child(2)').click(select_item);
          });
        }

        function toggle_item() {
          var $item = $(this);

          if ($item.hasClass('expand')) {
            expand_item($item);
          }
          else if ($item.hasClass('collapse')) {
             collapse_item($item);
          }
        }

        function select_item() {
                var $item = $(this);

                var    flux_id = $('#gdd-flux option:selected').val(),
          gdd_id = $item.parent('li').attr('data-id');

          $(".selected").removeClass('selected');
          $item.addClass('selected');

            // Url of the js that will display the guide.
          var jsSrc = guideBaseUrl + flux_id + '/' + gdd_id + '/' + organismId + '.js';

          $("#selection").val($item.html());
          $("#guideUrl").val(jsSrc);
          $("#flux").val(flux_id);
          $("#guide").val(gdd_id);
          $("#organism").val(organismId);
        }

        function expand_item($item) {
          var    flux_id = $('#gdd-flux option:selected').val(),
          gdd_id = $item.parent('li').attr('data-id');

          $item.removeClass('expand').addClass('collapse');

          if (saved_structure[flux_id] == undefined) {
            saved_structure[flux_id] = {};
          }

          // If the datas have been saved, uses the save, otherwise download them.
          if (saved_structure[flux_id][gdd_id] != undefined) {
            display_gdd_list(gdd_id, saved_structure[flux_id][gdd_id]);
          }
          else {
             get_gdd_list(flux_id, gdd_id);
          }
        }

        function collapse_item($item) {

          $item.siblings('ul').remove();
          $item.removeClass('collapse').addClass('expand');
        }

        function manage_flux(flux_id){
          if (flux_id != "") {
            if (prev_flux_id != "") {
              // Save current tree only if not empty flux type.
              saved_content[prev_flux_id] = $('#gdd-list').html();
            }
            else {
              $("#guideUrl").val('');
              $("#flux").val('');
              $("#guide").val('');
              $("#organism").val('');
              $("#cssUrl").val('');
            }

            $('#gdd-list').empty();

            if (saved_content[flux_id]) {
              // Add saved tree.
              $('#gdd-list').append(saved_content[flux_id]);
            }
            else {

                                   var label = $('#gdd-flux option:selected').text();

              $('#gdd-list').append(
              "<ul>\n\t<li class=\"ebou-gdd-item\" data-id=\"root_" + flux_id + "\" data-haschildren=\"true\">"
              + "<span class=\"expand\"></span>&nbsp;"
              + label
              + "</li>\n</ul>\n"
              // Append new root type.
              );
            }
            // Register callback for root item toggling.
            $('li.ebou-gdd-item[data-id=\"root_' + flux_id + '\"] span').click(toggle_item);
          }
          // If flux type selected is 'empty', we juste empty the tree.
          else {
                 $('#gdd-list').empty();
          }

                  prev_flux_id = flux_id;
        }

        $('#gdd-flux').change(function () {
          var flux_id = this.value;
                    manage_flux(flux_id);
        });

                // Display the tree of the defaut selected flux.
                manage_flux($('#gdd-flux').val());
                $('li.ebou-gdd-item[data-id=\"root_' + $('#gdd-flux').val() + '\"] span').trigger("click");

      });
    }
  };
})(jQuery);
