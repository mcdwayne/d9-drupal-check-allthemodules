/* global imce:true */
(function ($, Drupal, imce) {
  'use strict';

  /**
   * @file
   * Defines Search plugin for IMCE.
   */

   var imce_search_results = '';

  /**
   * Init handler for Search.
   */
  imce.bind('init', imce.searchInit = function () {
    // Add toolbar button.
    imce.addTbb('search', {
      title: Drupal.t('Search'),
      content: imce.createSearchForm(),
      // shortcut: 'Ctrl+Alt+W',
      icon: 'file-text'
    });
  });

  /**
   * Creates search form.
   *
   * @return {*}
   *    Return object form.
   */
  imce.createSearchForm = function () {
    var form = imce.searchForm;

    if (!form) {
      form = imce.searchForm = imce.createEl('<form class="imce-search-form">' +
          '<div class="imce-form-item">' +
          '<label>' + Drupal.t('Keywords') + '</label>' +
          '<input type="text" name="name" size="30" maxlength="50" />' +
          '</div>' +
          '<div class="imce-form-actions">' +
          '<button type="submit" name="op" class="imce_search_button">' + Drupal.t('Search') + '</button>' +
          '</div>' +
          '</form>');
      form.onsubmit = imce.eSearchSubmit;

      var els = form.elements;
      els.name.placeholder = Drupal.t('Search for files');
    }

    return form;
  };

  /**
   * Submit event for search form.
   *
   * @return {boolean}
   *    Return false.
   */
  imce.eSearchSubmit = function () {
    // first remove any existing results
    $('a.file_result').remove();

    var els = this.elements;
    var keywords = els.name.value;
    var form_submit = $('form.imce-search-form button');
    var data = {keywords: keywords};
    imce.ajax('search', {data: data}).done(function(response){
      if (response.data) {
        $(response.data).each(function(index, item) {
          var image_file_types = ['jpg','gif','png','JPG','PNG','GIF']
          var file_ext = item.url.split('.').pop();
          var image_preview = '';
          if (image_file_types.indexOf(file_ext) != -1) {
            image_preview = '<img width=200 src="'+item.url+'">';
          }
          var row = '<p><a class="file_result" href="'+item.url+'">'+image_preview+item.filename+'</a></p>';
          form_submit.before(row);
        });

        // define click handler for results
        $("a.file_result").click(function() {
          var query = imce.getQuery();
          var urlField = query.inputId;
          var parentWin = window.opener || window.parent;
          imce.parentWin = parentWin;
          urlField = parentWin.document.getElementById(urlField);
          // if using linkit, use a different field selector, else fallback on the ID in the URL
          if (query.sendto.indexOf('linkit') != -1) {
            urlField = $(parentWin.document).find('[data-drupal-selector="edit-attributes-href"]');
          }
          imce.parentWin.focus();
          (imce.parentWin.jQuery||$)(urlField).val($(this).attr('href')).blur().change().focus();
          imce.getTbb('search').closePopup();
          window.parent.close();
          return false;
        });
      }
    });

    // return false to make the form not reload the window
    return false;
  };

})(jQuery, Drupal, imce);
