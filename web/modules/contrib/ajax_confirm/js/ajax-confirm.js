/**
 * @file
 * Confirm modal dialog for specific ajax submits.
 */
(function ($, Drupal) {

  /**
   * Attaches the confirm dialog to all enabled elements for ajax confirmation.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the ajax confirmation behaviors.
   * @prop {Drupal~behaviorDetach} detach
   *   Detaches the autocomplete behaviors.
   */
  Drupal.behaviors.ajaxConfirm = {
    attach: function (context, settings) {

      function confirmDeferred(dialog_options) {
        var defer = $.Deferred();

        var dialogOptions = {
          modal: true,
          title: Drupal.t('Confirm action'),
          zIndex: 10000,
          autoOpen: true,
          width: 'auto',
          resizable: false,
          buttons: {
            'button_confirm': {
              text: Drupal.t('Yes'),
              click: function () {
                $(this).dialog('close');
                defer.resolve('yes');
              }
            },
            'button_reject': {
              text: Drupal.t('No'),
              click: function () {
                $(this).dialog('close');
                defer.resolve('no');
              },
              primary: true
            }
          },
          close: function (event, ui) {
            $(this).remove();
          }
        };

        $.extend(true, dialogOptions, dialog_options);

        var text = typeof dialog_options.text != 'undefined' ? dialog_options.text : Drupal.t('Are you sure?');

        $('<div></div>').appendTo('body')
          .html('<div>' + text + '</div>')
          .dialog(dialogOptions);

        return defer.promise();
      }

      /**
       * Returns the ajax instance corresponding to an element.
       *
       * @param element
       *   The element for which to return its ajax instance.
       *
       * @returns {Drupal.Ajax | null}
       *   The ajax instance if found, otherwise null.
       */
      function findAjaxInstance(element) {
        var ajax = null;
        var selector = '#' + element.id;
        for (var index in Drupal.ajax.instances) {
          var ajaxInstance = Drupal.ajax.instances[index];
          if (ajaxInstance && (ajaxInstance.selector == selector)) {
            ajax = ajaxInstance;
            break;
          }
        }
        return ajax;
      }

      /**
       * Adds ajax confirmation to the specified class selectors in the settings.
       *
       * @param {HTMLDocument|HTMLElement} [context=document]
       *   An element to attach behaviors to.
       * @param {object} [settings=drupalSettings]
       *   An object containing settings for the current context. If none is given,
       *   the global {@link drupalSettings} object is used.
       */
      function ajaxConfirm(context, settings) {
        if (typeof settings.ajaxConfirm != 'undefined') {
          for (var classNameConfirm in settings.ajaxConfirm) {
            $(context).find('.' + classNameConfirm).once('ajax-confirm').each(function () {
              var ajax = findAjaxInstance(this);
              if (ajax) {
                // Store the original beforeSend function, which will be called
                // if the user confirms the action.
                ajax.options.originalBeforeSend = ajax.options.beforeSend;
                
                // Overwrite the original beforeSend function, so that we first 
                // interrupt the ajax submit and then show an jquery dialog and 
                // if the user confirms the action then the ajax action will be
                // triggered again and the original beforeSend function will be
                // called.
                ajax.options.beforeSend = function (xmlhttprequest, options) {
                  if (!ajax.alreadyConfirmed) {
                    // Wait for an user input and if desired trigger the ajax
                    // submission again but flag the ajax object so that the
                    // next time we do not interrupt the submission.
                    confirmDeferred(settings.ajaxConfirm[classNameConfirm]).then(function (answer) {
                      if (answer == 'yes') {
                        ajax.alreadyConfirmed = true;
                        $(ajax.element).trigger(ajax.element_settings.event);
                      }
                    });
                    // Interrupt the ajax submission.
                    ajax.ajaxing = false;
                    return false;
                  }
                  else {
                    var beforeSend = ajax.options.originalBeforeSend(xmlhttprequest, options);
                    ajax.alreadyConfirmed = false;
                    return beforeSend;
                  }
                };
              }
            });
          }
        }
      }

      ajaxConfirm(context, settings);
    }
  };

})(jQuery, Drupal);
