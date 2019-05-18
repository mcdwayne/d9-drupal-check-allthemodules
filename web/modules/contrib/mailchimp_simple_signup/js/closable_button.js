/**
 * @file
 * Attaches behaviors for a closable button.
 * @see https://github.com/js-cookie/js-cookie
 */

(function ($, Drupal) {
  Drupal.behaviors.closableButton = {
    attach: function (context, settings) {

      /**
       * Cookie name.
       *
       * @type {string}
       */
      var cookieName = 'mailchimp_simple_signup_closable_button';

      /**
       * Wrapper element that encloses the button provided by the Twig template.
       *
       * @type {*|HTMLElement}
       */
      var closableWrapper = $(context).find('.mailchimp-simple-signup__closable-button');

      /**
       * Close button.
       *
       * @type {*|HTMLElement}
       */
        // @todo use icon library preferences and set it from config
      var closeButton = $('<button type="button" class="close js-closable-close" aria-label="Close"><span aria-hidden="true">&times;</span></button>');

      /**
       * Sets the cookie initial value and append a close button for each instance of the wrapper element.
       */
      closableWrapper.each(function (i, elem) {
        // Append the close button for each mailing list link element
        // and append the button for each found path.
        // @todo the cookie could be set for each different path if several mailing lists are provided.
        var elem_path = $(elem).find('a').first().attr('href');
        $(elem).attr('mailchimp_simple_signup_closable_button', elem_path)
          .append(closeButton.clone());
        // Sets the cookie to visible.
        if (typeof Cookies.get(cookieName) === 'undefined') {
          Cookies.set(cookieName, 'visible');
          $(elem).addClass('js-visible');
          // Show closableWrapper if cookie is not set as invisible.
        }
        else if (Cookies.get(cookieName) !== 'invisible') {
          $(elem).addClass('js-visible');
        }
      });

      /**
       * Sets the cookie value related to a path to invisible while clicking on the close button.
       */
      $('.js-closable-close').on('click', function () {
        Cookies.set(cookieName, 'invisible', {expires: parseInt(settings.cookie_expire_days)});
        $(this).closest(closableWrapper).removeClass('js-visible');
      });
    }
  };
})(jQuery, Drupal);
