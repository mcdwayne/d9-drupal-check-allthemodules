/**
 * @file
 * Js file to make the tables responsive.
 */

(function ($, Drupal) {
  Drupal.behaviors.myBehavior = {
    attach: function (context, settings) {
      maxWidth = drupalSettings.simpleResponsiveTable.maxWidth;

      // Add data title for each table row td.
      $(context).find('table').once('processTable').each(function () {
        headers = [];
        $(this).find('tr th').each(function () {
          header = $(this).text();
          // Handle sort headers.
          header = header.replace(Drupal.t('Sort descending'), '');
          header = header.replace(Drupal.t('Sort ascending'), '');
          headers.push(header.trim());
        });
        $(this).find('tr').each(function () {
          $(this).find('td').each(function (index) {
            $(this).attr('data-title', headers[index]);
            if ($(this).text().trim() == '' && $(this).html().trim() == '') {
              $(this).html('<div class="simple-responsive-table-empty-row-data"></div>');
            }
          });
        });
      });

      function applyResponsiveTable(){
        if ($(window).width() <= maxWidth) {
          $('table').each(function(){
            $(this).addClass('simple-responsive-table');
            $(this).removeClass('sticky-enabled');
          });
        } else {
          $('table').each(function(){
            if($(this).attr('class') == 'simple-responsive-table'){
              $(this).removeAttr('class');
            } else {
              $(this).removeClass('simple-responsive-table');
            };
          });
        };
      };

      // Execute code when document loads for the first time.
      // ----------------------------------------------------
      $(context).find('table').once('applyResponsiveTableClass(').each(function () {
        applyResponsiveTable();
      });

      // Execute code when the window is resized.
      //-----------------------------------------
      $(window).resize(function () {
        applyResponsiveTable();
      });


    }
  };
})(jQuery, Drupal);
