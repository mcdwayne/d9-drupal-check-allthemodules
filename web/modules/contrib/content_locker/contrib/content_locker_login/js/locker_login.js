/**
 * @file
 * Content locker log in js.
 */

(function ($, window, Drupal) {
  Drupal.behaviors.lockerLogin = {
    attach: function attach(context, settings) {
      function LockerLogin(holder) {
        Drupal.Locker.apply(this, arguments);
      }

      if (typeof Drupal.Locker !== 'undefined') {


        LockerLogin.prototype = Object.create(new Drupal.Locker());
        LockerLogin.prototype.constructor = LockerLogin;

        LockerLogin.prototype.render = function (holder) {

          var loginLink; var registerLink; var redirect;
          if (holder.length) {
            this.holder = holder;
            redirect = this.setRedirect('current');
            loginLink = $('<button>')
              .attr('data-href', '/user/login' + redirect)
              .addClass('cl' + this.type)
              .addClass('cl-login')
              .addClass('locker-btn')
              .text('Log in');

            registerLink = $('<button>')
              .attr('data-href', '/user/register' + redirect)
              .addClass('cl' + this.type)
              .addClass('cl-register')
              .addClass('locker-btn')
              .text('Register');

            var wrap = $('<div>')
              .addClass('cl-wrapper');

            var lock = $('<span>')
              .addClass(this.iconClass);
            lock.appendTo(holder);
            var lockerOptionsGeneral = Drupal.getLockerOptions(settings, 'log_in', 'general');
            var lockerText = '';
            if (lockerOptionsGeneral
              && lockerOptionsGeneral.text) {
              lockerText = lockerOptionsGeneral.text.value ? lockerOptionsGeneral.text.value : '';
            }
            $('<div>')
              .addClass(this.contentClass)
              .html(lockerText).appendTo(holder);
            var errors = $('<div>')
              .addClass(this.errorClass)
              .addClass('element-hidden');
            errors.appendTo(holder);
            loginLink.appendTo(wrap);
            registerLink.appendTo(wrap);
            wrap.appendTo(holder);
          }
          else {
            throw new Error(
              'holder should not be empty.'
            );
          }
        };

        LockerLogin.prototype.isLocked = function (holder) {
          return !Drupal.cookies('tw_nlnk');
        };

        LockerLogin.prototype.rejectEvent = function (e) {

        };

        LockerLogin.prototype.fireEvent = function (type) {
          $(document).trigger(type, this);
        };

        LockerLogin.prototype.setRedirect = function (destination) {
          var destinStr; var currentPath;
          if (destination && destination.length) {
            switch (destination) {
              case 'current':
                currentPath = location.pathname;
                destinStr = '?destination=' + encodeURIComponent(currentPath.substring(currentPath.indexOf('/') + 1));
                break;
              default:
                break;
            }
            return destinStr;
          }
          throw new Error(
            'setRedirect should receive non empty string. Got empty string.'
          );
        };
        LockerLogin.prototype.hideContent = function () {
          var content = $('.' + this.lockClass, this.holder);
          if (content && content.length) {
            content.hide();
          }
        };
      }

      $(document).ready(function () {
        if (drupalSettings.user.uid === 0) {
          var lockerPlaceholder = $('.content-locker');
          if (lockerPlaceholder && lockerPlaceholder.length) {
            lockerPlaceholder.once('content-locker-login').each(
              function (i, el) {
                var holder = $(el);
                var type = holder.data('content-locker-type');
                var uuid; var lockerOptions; var lockerSettings; var lockerLogin;
                if (type === 'log_in') {
                  if (typeof Drupal.Locker !== 'undefined') {
                    lockerOptions = Drupal.getLockerOptions(settings, type);
                    baseOptions = Drupal.getLockerOptions(settings, 'log_in', 'base');
                    lockerSettings = {
                      type: type,
                      options: lockerOptions,
                      errorClass: 'locker-error',
                      holder: holder,
                      contentClass: 'locker-content',
                      iconClass: 'icon-lock',
                      actionClass: 'locker-actions',
                      lockClass: 'locked-content'
                    };
                    if (lockerOptions) {
                      lockerLogin = new LockerLogin(holder);
                      lockerLogin.setOptions(lockerSettings);
                      lockerLogin.errorClass = lockerSettings.errorClass;
                      lockerLogin.isCookie = baseOptions.cookie;
                      lockerLogin.cookieLife = baseOptions.cookie_lifetime;
                      uuid = Drupal.userId();
                      lockerLogin.setUserId(uuid);
                      lockerLogin.subscribeEvents(holder, lockerLogin);
                      lockerLogin.render(holder);
                      if (baseOptions && baseOptions.ajax === 0) {
                        lockerLogin.hideContent();
                      }
                      $('.cllog_in').on('click', function (e) {
                        location.href = $(e.target).data('href');
                      });
                    }
                  }
                }
              }
            );
          }
        }
      });
    }
  };
})(jQuery, window, Drupal);
