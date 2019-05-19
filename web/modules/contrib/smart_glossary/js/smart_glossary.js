/**
 * @file
 *
 * JavaScript functionalities for the Smart Glossary frontend.
 */

var headerInterval = null;

(function ($, Drupal, drupalSettings) {
  $.urlParam = function(key){
    var result = new RegExp(key + "=([^&]*)", "i").exec(window.location.search);
    return result && decodeURIComponent(result[1]) || "";
  };

  $(document).ready(function() {
    // Add the uri-parameter to every language-switcher.
    var uri = $.urlParam('uri');

    if (uri != "") {
      $("#block-locale-language a.language-link, #block-locale-language-content a.language-link").each(function() {
        var href = $(this).attr("href") + "?uri=" + uri;
        $(this).attr("href", href);
      });
    }

    $("#smart-glossary-autocomplete").find(".concept-autocomplete").autocomplete({
      source: drupalSettings.path.baseUrl + "smart-glossary/search/" + drupalSettings.smart_glossary.id.replace("/", "|"),
      minLength: 2,  // TODO: Make minLength customizable
      select: function( event, ui ) {
        if (ui.item) {
          window.location.href = ui.item.url;
        }
      }
    });

    if ($(".chart-header").find(".show-help").length == 0) {
      headerInterval = window.setInterval(function(){
        addHeaderEvents();
      }, 50);
    }

    /**
     * Adds the buttons for all additional operations in the Visual Mapper header.
     *
     * @param {object} vm
     *   The VisualMapper object.
     * @param {object} data
     *   The concept data to show the operations for.
     */
    var addOperationButtons = function(vm, data) {
      var $chart_header = $(".chart-header");

      // Prepare the "Show Definition" button.
      var $show_definition = $chart_header.find(".show-definition");
      if ($show_definition.length === 0) {
        $chart_header.prepend('<div class="show-definition button"></div>');
        $show_definition = $chart_header.find(".show-definition");
      }

      // Prepare the "Show Content" button.
      var show_content = $chart_header.find(".show-content");
      if (show_content.length === 0) {
        $chart_header.prepend('<div class="show-content button"></div>');
        show_content = $chart_header.find(".show-content");
      }

      if (headerInterval === null && $chart_header.find(".show-help").length === 0) {
        headerInterval = window.setInterval(function(){
          addHeaderEvents();
        }, 50);
      }

      // Data is available for the concept.
      if (data.hasOwnProperty('type')) {
        // Add the "Show Definition" button.
        // Normal concept.
        if (data.type !== "project" && data.type !== "conceptScheme") {
          $show_definition.html('<a href="' + glossaryUrl + "/" + data.name + "?uri=" + data.id + '">' + ((typeof settings !== "undefined" && settings.hasOwnProperty("wording")) ? settings.wording.showDefinitionButton : "Show definition") + '</a>');
        }
        // Concept scheme.
        else {
          $show_definition.html((typeof settings !== "undefined" && settings.hasOwnProperty("wording")) ? settings.wording.noDefinition : "no definition");
        }

        // Add the "Show Content" button.
        if (data.hasOwnProperty('content_button')) {
          show_content.html(data.content_button);

          // Since data gets fetched via AJAX, the JS for the connected content
          // needs to be updated here.
          var concept_selection = $(".semantic-connector-concept");
          concept_selection.each(function() {
            if ($(this).find('ul.semantic-connector-concept-destination-links').length > 0) {
              $(this).find('a.semantic-connector-concept-link').click(function () {
                $(this).siblings('ul.semantic-connector-concept-destination-links').show();
                return false;
              });
            }
          });
          $(".semantic-connector-concept-destination-links").mouseover(function() {
            $(this).show();
          });
          concept_selection.mouseout(function() {
            $(this).find('.semantic-connector-concept-destination-links').hide();
          });
        }
        else {
          show_content.empty();
        }
      }
      // Data has to be fetched first.
      else {
        // Empty both button areas first.
        $show_definition.html("");
        show_content.empty();

        // Then load the data.
        $.ajax({
          dataType: "json",
          url: drupalSettings.path.baseUrl + "smart-glossary/get-visual-mapper-data-slim/" + drupalSettings.smart_glossary.id.replace("/", "|"),
          data: {
            uri: data.id,
            lang: vm.language
          },
          success: function (concept) {
            if (concept) {
              // Since data gets fetched via AJAX, the JS for the connected content
              // needs to be updated here.
              addOperationButtons(vm, concept);
            }
          }
        });
      }
    };

    if (typeof visualMapper !== "undefined") {
      // Add the "Show Definition" button.
      visualMapper.addListener("conceptLoaded", addOperationButtons);
    }
  });

  var addHeaderEvents = function() {
    if ($(".chart-navigation").length > 0) {
      clearInterval(headerInterval);
      headerInterval = null;

      var $chart_header = $(".chart-header").prepend('<div class="show-help button"><a href="#">' + ((typeof settings !== "undefined" && settings.hasOwnProperty("wording")) ? settings.wording.helpButton : "Help") + '</a></div>');
      var $help_area = $('#smart-glossary-help-area');
      if ($help_area.length === 0) {
        $help_area = $('<div id="smart-glossary-help-area" style="width:' + ($chart_header.width() - 4) + 'px; margin-left: ' + $chart_header.css("marginLeft") + '; border-color: ' + settings.headerColor + ';">' + ((typeof settings !== "undefined" && settings.hasOwnProperty("wording")) ? settings.wording.helpText.value : "No help text defined")).insertAfter($chart_header);
      }
      $chart_header.find(".show-help").click(function() {
        if ($chart_header.hasClass("collapsed")) {
          $chart_header.removeClass("collapsed");
          $help_area.removeClass("collapsed");
          $help_area.slideDown(500);
        }
        else {
          if ($help_area.hasClass("collapsed")) {
            $(".glossary-root-button").d3Click();
            $chart_header.addClass("collapsed");
            $chart_header.find(".show-help").click();
          }
          else {
            $chart_header.addClass("collapsed");
            $help_area.addClass("collapsed");
            $help_area.slideUp(500);
          }
        }
        return false;
      });

      $(".glossary-root-button").click(function() {
        if (!$help_area.hasClass("collapsed")) {
          $chart_header.addClass("collapsed");
          $help_area.addClass("collapsed");
          $help_area.slideUp(500);
          $(".glossary-root-button").d3Click();
        }
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
