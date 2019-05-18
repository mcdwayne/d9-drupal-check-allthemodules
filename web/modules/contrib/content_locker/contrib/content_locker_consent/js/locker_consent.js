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
            var lock = $('<span>')
              .addClass(this.iconClass);
            var choice1 = $('<button>')
              .addClass('cl-control')
              .addClass('cl-' + this.type)
              .addClass('locker-btn')
              .attr('value', '1')
              .html('Yes');

            var choice2 = $('<button>')
              .addClass('cl-control')
              .addClass('cl-' + this.type)
              .addClass('locker-btn')
              .attr('value', '0')
              .html('No');

            var wrapRadios = $('<div>')
              .addClass('cl-wrapper')
              .addClass(this.actionClass);


            var errors = $('<div>')
              .addClass(this.errorClass)
              .addClass('element-hidden');
            lock.appendTo(holder);
            errors.appendTo(holder);
            var lockerOptionsGeneral = Drupal.getLockerOptions(settings, 'consent', 'general');
            var lockerText = '';
            if (lockerOptionsGeneral
                    && lockerOptionsGeneral.text) {
              lockerText = lockerOptionsGeneral.text.value;
            }

            $('<div>')
              .addClass(this.contentClass)
              .html(lockerText).appendTo(holder);
            choice1.appendTo(wrapRadios);
            choice2.appendTo(wrapRadios);
            wrapRadios.appendTo(holder);
          }
          else {
            throw new Error(
              'holder should not be empty.'
            );
          }
        };

        /**
         * Check if content is locked
         *
         * @param {element} holder html element
         * @return {*} value
         */
        LockerConsent.prototype.isLocked = function (holder) {
          var control = $('input[name="choice-radio"]:checked', holder);
          if (control && control.length) {
            return control.value;
          }
          return 1;
        };

      }

      var lockerPlaceholder = $('.content-locker');
      if (lockerPlaceholder && lockerPlaceholder.length) {
        lockerPlaceholder.once('content-locker-consent').each(function (i, el) {
          var holder = $(el);
          var type = holder.data('content-locker-type');
          var lockerConsent;
          if (type === 'consent') {
            var baseOptions; var lockerOptions;
            if (typeof Drupal.Locker !== 'undefined') {
              baseOptions = Drupal.getLockerOptions(settings, 'consent', 'base');
              lockerOptions = Drupal.getLockerOptions(settings, 'consent');
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
                var uuid = Drupal.userId();
                lockerConsent.errorClass = lockerSettings.errorClass;
                lockerConsent.setUserId(uuid);
                lockerConsent.subscribeEvents(holder, lockerConsent);
                lockerConsent.render(holder);
              }

              var radios = $('.locker-btn', holder);
              if (radios && radios.length) {
                radios.on('click', function (e) {
                  e.preventDefault();
                  var value = $(e.target).attr('value');
                  switch (value) {
                    case '1':
                      lockerConsent.hideError(e);
                      lockerConsent.fireEvent(lockerConsent.unlockevent, holder,
                        this.value);
                      return false;
                    case '0':
                      lockerConsent.rejectEvent(e);
                      return false;
                  }
                  return false;
                });

              }

              if (lockerConsent
                && lockerConsent.isLocked(holder)) {
                lockerConsent.fireEvent(lockerConsent.lockevent, holder, lockerConsent.type);
              }
              else {
                lockerConsent.fireEvent(lockerConsent.unlockevent, holder, lockerConsent.type);
              }
            }
          }
        });
      }
    }
  };

})(jQuery, window, Drupal);
