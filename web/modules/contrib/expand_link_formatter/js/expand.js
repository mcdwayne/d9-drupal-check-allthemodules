/**
 * @file
 * Contains expand component.
 */

(function (Drupal, drupalSettings) {
  'use strict';

  /**
   * Enable expand formatted for given fields.
   *
   *  If "data-js-expand-target" attribute is present on expandBtn
   *  and it's value is present as target containers' class than multiple expand components will work.
   *  Otherwise it is assumed that there is only 1 expand component present on the page.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.expandLinkFormatter = {
    attach: function (context, settings) {
      // Utility method - forEach.
      var forEach = function (array, callback, scope) {
        for (var i = 0; i < array.length; i++) {
          callback.call(scope, i, array[i]);
        }
      };

      var expandBtns = document.querySelectorAll('.expand--more');

      forEach(expandBtns, function (index, btn) {
        btn.addEventListener('click', function (e) {
          e.preventDefault();
          var expandTarget = btn.getAttribute('data-js-expand-target');
          if (expandTarget != null) {
            var expand = document.querySelector('.expand' + expandTarget);
          }
          else {
            var expand = document.querySelector('.expand');
          }

          var label = btn.querySelector('.expand--label');

          if (expand.classList.contains('expand--closed')) {
            expand.classList.remove('expand--closed');
            expand.classList.add('expand--opened');
            btn.classList.remove('expand--more');
            btn.classList.add('expand--less');
            if (typeof settings.expandLinkFormatter.expandLinkLabel != 'undefined') {
              label.innerHTML = Drupal.t(settings.expandLinkFormatter.collapseLinkLabel);
            }
            else {
              label.innerHTML = Drupal.t('Show less');
            }
          }
          else {
            expand.classList.remove('expand--opened');
            expand.classList.add('expand--closed');
            btn.classList.remove('expand--less');
            btn.classList.add('expand--more');
            if (typeof settings.expandLinkFormatter.expandLinkLabel != 'undefined') {
              label.innerHTML = Drupal.t(settings.expandLinkFormatter.expandLinkLabel);
            }
            else {
              label.innerHTML = Drupal.t('Show more');
            }
          }
        });
      });
    }
  };
})(Drupal, drupalSettings);
