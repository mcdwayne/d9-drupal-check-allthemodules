(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.omedaSubscriptionsConfiguration = {
    attach: function (context) {
      var $form = $('.omeda-subscriptions-configuration-form', context);

      setToggleMode($form);

      $form.on('click', '.select-all', function (e) {
        e.preventDefault();
        $('.subscription', $form).prop('checked', true);
        setToggleMode($form);
      });

      $form.on('click', '.deselect-all', function (e) {
        e.preventDefault();
        $('.subscription', $form).prop('checked', false);
        setToggleMode($form);
      });

      $form.on('change', '.subscription', function () {
        setToggleMode($form);
      });
    }
  };

  function setToggleMode($form) {
    var has_unchecked = false;
    $('.subscription', $form).each(function () {
      if (!$(this).prop('checked')) {
        has_unchecked = true;
        return false;
      }
    });

    if (has_unchecked) {
      $('.check-toggle', $form).addClass('select-all').removeClass('deselect-all').val(Drupal.t('Select All'));
    }
    else {
      $('.check-toggle', $form).addClass('deselect-all').removeClass('select-all').val(Drupal.t('Deselect All'));
    }

  }

})(jQuery, Drupal);
