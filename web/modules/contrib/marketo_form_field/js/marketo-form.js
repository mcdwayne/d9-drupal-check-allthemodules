(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.marketoFormSetup = {
    loaded: false,

    attach: function (context, settings) {
      if (typeof settings.marketo_form_field.marketoForms === 'undefined' || settings.marketo_form_field.marketoForms.length === 0) {
        return;
      }

      // Prevent calling Marketo loadForm twice.
      if (this.loaded) {
        return;
      }

      this.loaded = true;
      $.each(settings.marketo_form_field.marketoForms, function (i, formSettings) {
        MktoForms2.loadForm(settings.marketo_form_field.instanceHost, settings.marketo_form_field.munchkinId, formSettings.formId, function(form) {
          form.getFormElem()
            .removeClass('mktoForm')
            .addClass('marketo-form')
            .attr('style', '');

          form.onSuccess(function() {
            var $success = '<div class="messages messages--status marketo-form-success">' + Drupal.checkPlain(formSettings.successMessage) + '</div>';
            form.getFormElem().hide().parent().append($success);

            return false;
          });
        });
      });
    }
  }

}(jQuery, Drupal, drupalSettings));
