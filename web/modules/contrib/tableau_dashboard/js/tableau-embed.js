/**
 * @file
 * Javascript file used to save custom view if user changes any filters.
 *
 * User: rok
 * Date: 26/04/2017
 * Time: 09:24.
 */

(function ($) {

  'use strict';
  Drupal.behaviors.tableau_dashboard = {
    attach: function (context, settings) {

      // We need to check if there is more than one Tableua dashboard being rendered to the page.
      var dashboards = $.map(drupalSettings.tableau_dashboard.dashboards, function(value, index) {
        return [value];
      });

      // We loop through each of the dashboards, set the url, options and intantiate the Viz object for each one.
      for (var i = 0; i < dashboards.length; i++) {
        $.ajax({
          type: 'GET',
          url: '/tableau/ticket',
          cache: false,
          success: function(ticket) {

            // Store current dashboard and create a container Div element which we will later append the Viz container to.
            var dashboard = dashboards.shift(),
                containerDiv = document.createElement("DIV");

            // Extract the Desktop, Tablet and Mobile field values from Drupal and create a media query from them.
            var minWidthPhone = '(min-width:' + drupalSettings.tableau_dashboard.mobile + 'px)',
                minWidthTablet = '(min-width:' + drupalSettings.tableau_dashboard.tablet + 'px)',
                minWidthDesktop = '(min-width:' + drupalSettings.tableau_dashboard.desktop + 'px)';

            // Run a check on current matching media query against the ones produced from the field value above.
            // Set the mediaWidth value based on that result. This is set to Desktop as default.
            var meidaWidth = 'desktop';
            // Check if mobile is true;
            if (window.matchMedia(minWidthPhone).matches) {
              meidaWidth = 'mobile';
            }
            // Check if tablet is true;
            if (window.matchMedia(minWidthTablet).matches) {
              meidaWidth = 'tablet';
            }
            // Check if desktop is true;
            if (window.matchMedia(minWidthDesktop).matches) {
              meidaWidth = 'desktop';
            }

            // Store the Viz container.
            var vizContainer = $(".vizContainer[data-tableau-container=" + dashboard.containerId + "]", context);
            // Append the viz Container to the container Div with the container Id

            vizContainer.append(containerDiv);

            // If the Site Name is blank or is equal to default we apply the default URL structure. Otherwise we apply the Site based URL structure.
            var url = drupalSettings.tableau_dashboard.url + "/trusted/" + ticket + "/views/" + dashboard.display;
            // If the siteName is set within Drupal then we change the structure and add in the site name to the request url.
            if (drupalSettings.tableau_dashboard.siteName || drupalSettings.tableau_dashboard.siteName == 'default') {
              url = drupalSettings.tableau_dashboard.url + "/trusted/" + ticket + "/t/" + drupalSettings.tableau_dashboard.siteName + "/views/" + dashboard.display;
            }

            // Once we have run all our logic on the options we can now build the options array to pass through to the tableau class.
            var options = {
              hideTabs: drupalSettings.tableau_dashboard.hideTabs,
              hideToolbar: drupalSettings.tableau_dashboard.hideToolbar,
              device: meidaWidth,
              onFirstInteractive: function () {
                viz.getWorkbook();
              }
            };

            // Instantiate a new viz object and pass it the request url and options.
            var viz = new tableau.Viz(containerDiv, url, options);
          },
          fail: function(data) {
            // Log the error in console. We can't make assumptions on the type of errors you can get at this point.
            console.log(data);
          }
        });
      }
    }
  };
})(jQuery, drupalSettings);