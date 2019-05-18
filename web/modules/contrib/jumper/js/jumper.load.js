/**
 * @file
 * Provides jumper loader for the Jumper block.
 */

(function (Drupal, drupalSettings, _db, _jump, window, document) {

  'use strict';

  /**
   * Jumper public methods.
   *
   * @namespace
   */
  Drupal.jumper = Drupal.jumper || {

    /**
     * Last scrollTop position.
     */
    lastPos: 0,

    /**
     * Trigger jumper visibility when scrolled to the designated position.
     *
     * We don't need requestAnimationFrame as nobody don't animate classes.
     */
    doScroll: function () {
      var me = this;
      var className = 'is-jumper-visible';
      var doc = document.documentElement || document.body;
      var yPos = window.pageYOffset;
      var pos = (yPos === 'undefined') ? doc.scrollTop : yPos;
      var visiblePoint = drupalSettings.jumper.visibility || 620;

      if (drupalSettings.jumper.autovlm && !doc.classList.contains('no-vlm')) {
        var vlmEl = document.querySelector('.pager--load-more');

        if (vlmEl !== null) {
          var vlmTop = vlmEl.getBoundingClientRect().top;
          var vlmPos = pos + window.innerHeight;
          var vlmBtn = vlmEl.querySelector('a');

          if (vlmBtn !== null && vlmPos > vlmTop) {
            vlmBtn.click();
          }
        }
        else {
          doc.classList.add('no-vlm');
        }
      }

      // Scrolls down: show the jumper.
      if (pos > me.lastPos) {
        if (pos > visiblePoint && !doc.classList.contains(className)) {
          doc.classList.add(className);
        }
      }

      // Scrolls up: hide the jumper.
      if (pos < me.lastPos) {
        if (pos < visiblePoint && doc.classList.contains(className)) {
          doc.classList.remove(className);

          // Cleans up the marker to prevent multiple clicks.
          var jumperBlock = document.querySelector('.jumper--block');
          if (jumperBlock.hasAttribute('data-jumper-hit')) {
            jumperBlock.removeAttribute('data-jumper-hit');
          }
        }
      }

      me.lastPos = pos;
    }
  };

  /**
   * Jumper utility functions.
   *
   * @param {HTMLElement} elm
   *   The jumper HTML element.
   */
  function doJumper(elm) {
    elm.classList.add('jumper--on');

    elm.addEventListener('click', function (e) {
      var btn = this;
      var data = btn.getAttribute('data-target');
      var target = data || e.target.hash;
      var isBlock = btn.classList.contains('jumper--block');

      // @todo: Not working, yet, although claimed to work with number.
      if (typeof data === 'number') {
        target = parseInt(data);
      }

      // Prevents unintended link hijacking when no target.
      if (!target) {
        return true;
      }

      e.preventDefault();

      if (isBlock && btn.hasAttribute('data-jumper-hit')) {
        return;
      }

      _jump(target, {
        duration: drupalSettings.jumper.duration,
        offset: drupalSettings.jumper.offset
      });

      // Adds a marker to prevent multiple clicks.
      if (isBlock) {
        btn.setAttribute('data-jumper-hit', 1);
      }
    });
  }

  /**
   * Attaches jumper behavior to HTML element identified by .jumper.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.jumper = {
    attach: function (context) {
      var me = Drupal.jumper;
      var jumper = context.querySelector('#jumper');

      // Nothing to work with without a jumper block, bail out.
      if (jumper === null) {
        return false;
      }

      // Makes jumper visible when scrolled to the designated position.
      var onScroll = Drupal.debounce(me.doScroll.bind(me), 250);
      window.addEventListener('scroll', onScroll);

      // Base CSS selector.
      var jumpers = '.jumper:not(.jumper--on)';

      // Custom CSS selectors for easy additions without template overrides.
      if (drupalSettings.jumper.selectors) {
        jumpers += ', ' + drupalSettings.jumper.selectors.trim();
      }

      var $jumpers = context.querySelectorAll(jumpers);
      _db.once(_db.forEach($jumpers, doJumper));
    }
  };

})(Drupal, drupalSettings, dBlazy, Jump, window, this.document);
