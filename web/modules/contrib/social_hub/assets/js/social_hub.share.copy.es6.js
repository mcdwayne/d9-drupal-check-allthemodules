/**
 * @file
 * Share content copying its URL.
 */

(($, { behaviors }, { socialHub }) => {
  behaviors.socialHubShareCopy = {
    attach() {
      const { instances } = socialHub;
      for (let i = 0; i < instances.length; i++) {
        $(instances[i])
          .once('social_hub--share__copy')
          .each((i, el) => {
            const $el = $(el);
            $el.on('click', function(e) {
              e.preventDefault();
              const settings = $(this).data('social-hub');
              copyTextToClipboard(settings.url);
            });
          });
      }
    },
  };

  /**
   * Fallback for browsers with no Clipboard API support.
   *
   * @param {string} text Text to copy.
   * @param {?Function} _callback Callback function to show notification
   *     (optional).
   */
  function fallbackCopyTextToClipboard(text, _callback) {
    const clipboard = document.createElement('textarea');
    clipboard.value = text;
    document.body.appendChild(clipboard);
    clipboard.focus();
    clipboard.select();

    try {
      document.execCommand('copy');
      _callback(Drupal.t('Copied!'));
    } catch (err) {
      _callback(
        Drupal.t(`Error copying text to clipboard: ${err}`),
        Drupal.t('Error'),
      );
      console.error('Error copying text to clipboard', err);
    }

    document.body.removeChild(clipboard);
  }

  /**
   * Close popup trigger.
   *
   * @param {string} id The popup id.
   */
  function closePopup(id) {
    const el = document.querySelector(`#${id}`);
    el.parentNode.removeChild(el);
  }

  /**
   * Generate unique ID.
   *
   * @param {number} length ID length (defaults: 12).
   *
   * @return {string} The generated ID.
   */
  function uniqueid(length = 12) {
    let id = '';

    do {
      id = Math.random()
        .toString(36)
        .substr(2, length);
    } while (id.length < length);

    return id;
  }

  /**
   * Fallback popup function.
   *
   * @param {string} content The popup content.
   * @param {?string} title The popup title (optional).
   */
  function fallbackPopUp(content, title = null) {
    let id = uniqueid();
    const alStart = Drupal.t('Begins %string.', {
      '%string': Drupal.t('popup'),
    });
    const alEnd = Drupal.t('Ends %string.', {
      '%string': Drupal.t('popup'),
    });
    const alClose = Drupal.t('Click to close popup.');
    title = title !== null ? title : '&nbsp;';
    const overlays = document.querySelectorAll('.overlay.copy-to-clipboard');
    const template = `
<div class="popup">
  <span class="element-invisible">${alStart}</span>
  <h2>${title}</h2>
  <a class="close_popup" href="#" data-reference="${id}" aria-label="${alClose}">&times;</a>
  <div class="content">${content}</div>
  <span class="element-invisible">${alEnd}</span>
</div>
`;
    let overlay = null;

    if (overlays.length === 0) {
      overlay = document.createElement('div');
      overlay.id = id;
      overlay.classList.add('overlay');
      overlay.classList.add('copy-to-clipboard');
      overlay.innerHTML = template;
      document.body.appendChild(overlay);
    }
    else {
      overlay = overlays.item(0);
      id = overlay.id;
      overlay.innerHTML = template;
    }

    const close = document.querySelectorAll(`[data-reference="${id}"]`);
    close.forEach(function(el) {
      el.addEventListener('click', () => {
        document.getElementById(id).style.visibility = 'hidden';
      });
    });
    overlay.addEventListener('click', () => {
      document.getElementById(id).remove();
    });

    overlay.style.visibility = 'visible';
    overlay.style.opacity = '0.85';
  }

  /**
   * Copy text to clipboard.
   *
   * @param {string} text Text to copy.
   * @param {Function} _callback Callback function to show notification.
   */
  function copyTextToClipboard(text, _callback = null) {
    if (_callback === null || !(typeof _callback === 'function')) {
      _callback = fallbackPopUp;
    }

    if (!navigator.clipboard) {
      fallbackCopyTextToClipboard(text, _callback);
      return;
    }

    navigator.clipboard.writeText(text).then(
      () => {
        _callback(Drupal.t('Copied!'));
      },
      err => {
        _callback(
          Drupal.t(`Error copying text to clipboard: ${err}`),
          Drupal.t('Error'),
        );
        console.error('Error copying text to clipboard: ', err);
      },
    );
  }

  /**
   * Plugin implementation allow to copy element text/value on click.
   */
  $.fn.clickToCopy = function() {
    const $this = $(this);
    $this.on('click', e => {
      const $target = $(e.target);

      if ($target.is($this)) {
        const isInput = $target.is('input:text') || $target.is('textarea');
        const value = isInput ? $target.val() : $target.text();
        copyTextToClipboard(value);
      }
    });
  };
})(jQuery, Drupal, drupalSettings);
