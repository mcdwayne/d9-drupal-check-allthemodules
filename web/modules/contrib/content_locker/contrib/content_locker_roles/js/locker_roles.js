/**
 * @file
 * Content locker consent js.
 */

(function ($, window, Drupal) {
  Drupal.behaviors.lockerConsent = {
    attach: function attach(context, settings) {
      function LockerConsent(holder) {
        Drupal.Locker.apply(this, arguments);
      }

      if (typeof Drupal.Locker !== 'undefined') {

        LockerConsent.prototype = Object.create(new Drupal.Locker());
        LockerConsent.prototype.constructor = LockerConsent;

        /**
         * Render consent buttons
         * @param {element} holder html element
         */
        LockerConsent.prototype.render = function (holder) {
          if (holder.length) {
            this.holder = holder;
            var lock = $('<span>').addClass(this.iconClass);
            var errors = $('<div>').addClass(this.errorClass).addClass('element-hidden');

            lock.appendTo(holder);
            errors.appendTo(holder);

            var lockerOptionsGeneral = Drupal.getLockerOptions(settings, 'roles', 'general');

            var lockerText = '';
            if (lockerOptionsGeneral && lockerOptionsGeneral.text) {
              lockerText = lockerOptionsGeneral.text.value;
            }

            $('<div>').addClass(this.contentClass).html(lockerText).appendTo(holder);
          }
          else {
            throw new Error(
              'holder should not be empty.'
            );
          }
        };
      }

      var lockerPlaceholder = $('.content-locker');
      if (lockerPlaceholder && lockerPlaceholder.length) {
        lockerPlaceholder.once('content-locker-roles').each(function (i, el) {
          var holder = $(el);
          var type = holder.data('content-locker-type');
          var lockerConsent;
          if (type === 'roles') {
            var baseOptions; var lockerOptions;
            if (typeof Drupal.Locker !== 'undefined') {
              baseOptions = Drupal.getLockerOptions(settings, 'roles', 'base');
              lockerOptions = Drupal.getLockerOptions(settings, 'roles');
              lockerConsent = new LockerConsent(holder);

              if (lockerOptions) {
                var lockerSettings = {
                  type: type,
                  options: lockerOptions,
                  errorClass: 'locker-error',
                  holder: holder,
                  contentClass: 'locker-content',
                  iconClass: 'icon-lock',
                  actionClass: 'locker-actions',
                  lockClass: 'locked-content'
                };

                lockerConsent.setOptions(lockerSettings);
                lockerConsent.isAjax = baseOptions.ajax ? baseOptions.ajax : 0;
                lockerConsent.isCookie = baseOptions.cookie ? baseOptions.cookie : 0;
                lockerConsent.cookieLife = baseOptions.cookie_lifetime ? baseOptions.cookie_lifetime : 0;
                lockerConsent.errorClass = lockerSettings.errorClass;
                lockerConsent.subscribeEvents(holder, lockerConsent);
                lockerConsent.render(holder);
              }

              lockerConsent.fireEvent(lockerConsent.lockevent, holder, lockerConsent.type);
            }
          }
        });
      }
    }
  };
})(jQuery, window, Drupal);
