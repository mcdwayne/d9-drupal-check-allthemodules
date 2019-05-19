/**
 * @file
 * Provides stripe attachment logic.
 */

(function ($, window, Drupal, drupalSettings, Stripe) {

  'use strict';

  var stripe = null;
  // Create a Stripe client
  if (drupalSettings.stripe.apiKey) {
    stripe = Stripe(drupalSettings.stripe.apiKey);
  }

  /**
   * Attaches the stripe behavior
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   */
  Drupal.behaviors.stripe = {
    attach: function (context, settings) {

      // Stripe was not initialized, do nothing.
      if (!stripe) {
        return;
      }

      for (var base in settings.stripe.elements) {
        var element = $('#' + base, context)[0];
        if (!element) {
          continue;
        }
        var form = element.form;
        if (!$(form).data('stripe-element')) {
          $(form).data('stripe-element', base);
        }

        // Make sure we only attach the stripe card element a single
        // element per form
        if ($(form).data('stripe-element') == base) {
          // Provide proper scope for closures of each stripe event
          (function(element, form) {
            var stripeSelectors = JSON.parse(element.getAttribute('data-drupal-stripe-selectors'))

            // Create an instance of Elements
            var elements = stripe.elements();

            var options = {};
            if (stripeSelectors && stripeSelectors['address_zip']) {
              options.hidePostalCode = true;
            }

            // Allow other modules to change these options
            $(element).trigger('drupalStripe.elementCreate', ['card', options]);

            // Create an instance of the card Element
            var card = elements.create('card', options);

            // Add an instance of the card Element into the `card-element` <div>
            card.mount('#' + element.id + '-card-element');

            card.on('ready', function(e) {
              // Handle real-time validation errors from the card Element.
              card.addEventListener('change', function(event) {
                var displayError = document.getElementById(element.id + '-card-errors');
                if (event.error) {
                  displayError.textContent = event.error.message;
                } else {
                  displayError.textContent = '';
                }
              });

              // Using the same approach as drupal own double submit prevention
              // @see core/drupal.form
              function onFormSubmit(e) {
                var $form = $(e.currentTarget);
                var formValues = $form.find(':input').not(element).serialize();
                var previousValues = $form.attr('data-stripe-form-submit-last');
                e.preventDefault();

                if (previousValues !== formValues) {

                  // Store data to prevent double submit
                  $form.attr('data-stripe-form-submit-last', formValues);

                  $form.trigger('drupalStripe.submitStart');

                  // Collect all stripe options from the provided selectors
                  var stripeOptions = {name: ''};
                  for (var data in stripeSelectors) {
                    var selector = stripeSelectors[data];
                    if (selector) {
                      stripeOptions[data] = $(selector, form).val();
                    }
                  }

                  // Name special handling
                  if (stripeOptions['first_name'] ) {
                    stripeOptions['name'] += stripeOptions['first_name'];
                    if (stripeOptions['last_name']) {
                      stripeOptions['name'] += ' ';
                    }
                  }
                  if (stripeOptions['last_name']) {
                    stripeOptions['name'] += stripeOptions['last_name'];
                  }

                  // Allow other modules to change these options
                  $(element).trigger('drupalStripe.createToken', [card, stripeOptions]);

                  // Filter out unknown options and special handling for some of them
                  // https://stripe.com/docs/stripe-js/reference#stripe-create-token
                  var validOptions = [
                    'name',
                    'address_line1',
                    'address_line2',
                    'address_city',
                    'address_state',
                    'address_zip',
                    'address_country',
                    'currency'
                  ];
                  var options = {};
                  for (var option in stripeOptions) {
                    if (validOptions.indexOf(option) !== -1) {
                      options[option] = stripeOptions[option];
                    }
                  }

                  stripe.createToken(card, options).then(function(result) {
                    if (result.error) {
                      // Inform the user if there was an error
                      var errorElement = document.getElementById(element.id + '-card-errors');
                      errorElement.textContent = result.error.message;
                      $form.removeAttr('data-stripe-form-submit-last');
                      $form.trigger('drupalStripe.submitStop');
                    } else {
                      // Send the token to your server
                      element.setAttribute('value', result.token.id);
                      form.submit();
                    }
                  });
                }
              }

              $(form).once('stripe-single-submit').on('submit.stripeSingleSubmit', onFormSubmit);

              // Adding a stripe processing class using our custom events
              $(form).on('drupalStripe.submitStart', function(e) {
                $(this).addClass('stripe-processing');
              });

              $(form).on('drupalStripe.submitStop', function(e) {
                $(this).removeClass('stripe-processing');
              });
            });

          }(element, form));
        }
      }
    }
  };

})(jQuery, window, Drupal, drupalSettings, Stripe);
