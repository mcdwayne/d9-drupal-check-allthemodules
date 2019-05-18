(function ($, Drupal, drupalSettings, DrupalDRD) {

  DrupalDRD = DrupalDRD || {};

  Drupal.behaviors.drd = {
    attach: function(context, settings) {
      $('.view-drd-domain table thead th.views-field-name-2').append('<div class="toggle-domain-name">-</div>');
      $('.view-drd-domain table thead th .toggle-domain-name').click(function() {
        $('.drd-domain-name').toggleClass('show-domain');
      });
      $('.drd-view .view-filters').prepend('<div class="toggle-filter">Filter</div>');
      $('.drd-view .view-filters .toggle-filter').click(function() {
        $(this).parent().toggleClass('visible');
      });

      $('body.drd .views-form form #edit-header,' +
        'body.drd .views-form form #edit-actions,' +
        'body.drd .views-form form #edit-actions--1,' +
        'body.drd .views-form form #edit-actions--2,' +
        'body.drd .views-form form #edit-actions--3').addClass('drd-action-form-elements');
      $('body.drd .views-form form .form-checkbox').change(function() {
        if (this.checked || $('.drd-view form tbody tr.selected').length > 0) {
          $('.drd-action-form-elements').slideDown('fast');
        }
        else {
          $('.drd-action-form-elements').slideUp('fast');
        }
      });

      $('body.drd.drd-project tbody .views-field-domain, body.drd.drd-project tbody .views-field-version').each(function() {
        var content = this.innerHTML,
          count = (content.match(/<br>/g) || []).length + 1;
        this.innerHTML = '<div class="count">' + count + '<div class="list">' + content + '</div></div>';
        $(this).addClass('visible');
      });

      DrupalDRD.domainNameHandler();
    }
  };

  DrupalDRD.domainNameHandler = function () {
    $('.drd-domain-name div.token-widget').click(function () {
      var $temp = $('<input>');
      $('body').append($temp);
      $temp.val($(this).attr('token')).select();
      document.execCommand('copy');
      $temp.remove();
    });
  };

}) (jQuery, Drupal, drupalSettings);
