(function ($, Drupal, drupalSettings) {

  'use strict';

    Drupal.behaviors.multistep_submit = {
        attach: function (context, settings) {

            var form = $(".multistep-submit-form").show();
            console.log(drupalSettings.multistep_submit.transition);
            form.steps({
                headerTag: "h3",
                bodyTag: "fieldset",
                transitionEffect: drupalSettings.multistep_submit.transition,
                stepsOrientation: drupalSettings.multistep_submit.orientation,
                /* Labels */
                labels: {
                    cancel: drupalSettings.multistep_submit.buttons.cancel,
                    current: "current step:",
                    pagination: "Pagination",
                    finish: drupalSettings.multistep_submit.buttons.finish,
                    next: drupalSettings.multistep_submit.buttons.next,
                    previous: drupalSettings.multistep_submit.buttons.previous,
                    loading: "Loading ..."
                },
                onStepChanging: function (event, currentIndex, newIndex)
                {
                    // Allways allow previous action even if the current form is not valid!
                    if (currentIndex > newIndex)
                    {
                        return true;
                    }

                    form.validate().settings.ignore = ":disabled,:hidden";
                    return form.valid();
                },
                onFinishing: function (event, currentIndex)
                {
                    form.validate().settings.ignore = ":disabled";
                    return form.valid();
                },
                onFinished: function (event, currentIndex)
                {
                    $( ".multistep-submit-form .multistep-sbumit-btn" ).trigger( "click" );
                }
            }).validate({
                errorPlacement: function errorPlacement(error, element) { element.before(error); },
                rules: {
                    'edit-pass-pass2': {
                        equalTo: "#edit-pass-pass1"
                    }
                },
                messages: {
                    'edit-pass-pass2': {
                        equalTo: "Please enter the same password as above"
                    },
                }
            });
     }
  };

})(jQuery, Drupal, drupalSettings);