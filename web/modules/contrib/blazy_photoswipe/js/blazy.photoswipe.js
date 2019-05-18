/**
 * @file
 * Provides own PhotoSwipe loader for advanced contents with videos, etc.
 *
 * @see http://photoswipe.com/documentation/api.html
 */

(function (Drupal, drupalSettings, PhotoSwipe, PhotoSwipeUI_Default, _db, window, document) {

  'use strict';

  var pswpVideoClickTimer;

  /**
   * Blazy PhotoSwipe utility functions.
   *
   * @param {HTMLElement} gallery
   *   The gallery HTML element.
   * @param {Int} uid
   *   The index of the current element.
   */
  function blazyPhotoSwipe(gallery, uid) {
    var opts = drupalSettings.photoswipe.options || {};
    var pswpElm = document.querySelectorAll('.pswp')[0];
    var elms = gallery.querySelectorAll('[data-photoswipe-trigger]');
    var items = [];

    // Build items.
    _db.forEach(elms, buildItems, gallery);

    /**
     * Build the gallery items.
     *
     * @param {HTMLElement} elm
     *   The link HTML element.
     * @param {Int} i
     *   The index of the current element.
     */
    function buildItems(elm, i) {
      var caption = elm.nextElementSibling !== null ? elm.nextElementSibling : null;
      var media = !elm.getAttribute('data-media') ? {} : _db.parse(elm.getAttribute('data-media'));
      var url = elm.getAttribute('href');
      var tnUrl = !elm.getAttribute('data-thumb') ? '' : elm.getAttribute('data-thumb');
      var item = {};

      // Save link to element for getThumbBoundsFn.
      item.el = elm;
      item.w = media.width;
      item.h = media.height;
      item.i = i;
      item.type = media.type;

      if (caption !== null) {
        item.title = caption.innerHTML;
      }

      if (tnUrl) {
        item.msrc = tnUrl;
      }

      // @todo: Supports audio, inline, etc. if time permits.
      if (media.type === 'image') {
        item.src = url;
      }
      else {
        item.html = Drupal.theme('blazyPhotoSwipeMedia', {item: item});
      }

      items.push(item);
    }

    /**
     * Gets the current clicked item index.
     *
     * @param {HTMLElement} item
     *   The link item HTML element.
     *
     * @return {Int}
     *   The current clicked item index.
     */
    function getIndex(item) {
      var i = 0;
      _db.forEach(elms, function (elm, idx) {
        if (elm === item) {
          i = idx;
          return false;
        }
      });
      return i;
    }

    /**
     * Triggers when user clicks on thumbnail.
     *
     * @param {Event} event
     *   The event triggered by a `click` event.
     */
    function openBox(event) {
      event.preventDefault();

      // With a mix of (non-)lightboxed contents: image, video, Facebook,
      // Instagram, etc., some may not always be lightboxed, so filter em out.
      var t = event.target;
      var linkElm = !t.parentNode.getAttribute('href') ? _db.closest(t, '[data-photoswipe-trigger]') : t.parentNode;
      var index = getIndex(linkElm);

      box(index);
    }

    /**
     * Open the PhotoSwipe box.
     *
     * Code taken from http://photoswipe.com/documentation/getting-started.html
     *
     * @param {int} delta
     *   The start index of the slide, not the current slide index.
     */
    function box(delta) {
      delta = delta > 0 ? delta : 0;

      // Define options.
      opts.index = delta;

      // Prevent HTML or Video cover from triggering close.
      opts.clickToCloseNonZoomable = false;

      // Define gallery index (for URL).
      opts.galleryUID = gallery.getAttribute('data-pswp-uid');

      opts.getThumbBoundsFn = function (i) {
        // Gets window scroll Y.
        var pageYScroll = window.pageYOffset || document.documentElement.scrollTop;

        // Gets position of element relative to viewport.
        var rect = items[i].el.getBoundingClientRect();

        return {x: rect.left, y: rect.top + pageYScroll, w: rect.width};
      };

      // Pass data to PhotoSwipe and initialize it.
      var pswp = new PhotoSwipe(pswpElm, PhotoSwipeUI_Default, items, opts);

      // Listen to events.
      pswp.listen('beforeChange', function () {
        // PhotoSwipe items are always destroyed and recycled for 3, reattach.
        Drupal.attachBehaviors(pswpElm);
      });

      pswp.listen('afterChange', function () {
        // Stop any playing video after change.
        closeVideo();

        // Automatically play video on focus.
        window.clearTimeout(pswpVideoClickTimer);
        if (pswp.currItem.type === 'video') {
          var btn = pswp.currItem.container.querySelector('.media__icon--play');

          if (btn !== null) {
            pswpVideoClickTimer = window.setTimeout(function () {
              btn.click();
            }, 300);
          }
        }
      });

      pswp.listen('pointerDown', function () {
        pswpElm.className += ' pswp--dragging';
      });

      pswp.listen('pointerUp', function () {
        if (pswpElm.className.match('pswp--dragging')) {
          pswpElm.className = pswpElm.className.replace(/(\S+)pswp--dragging/, '');
        }
      });

      pswp.init();
    }

    /**
     * Parse picture index and gallery index from URL (#&pid=1&gid=2)
     *
     * Code taken from http://photoswipe.com/documentation/getting-started.html
     * and adjusted accordingly.
     *
     * @return {Array}
     *   Return array of URL params if available, else empty array.
     */
    function parseHash() {
      var hash = window.location.hash.substring(1);
      var params = {};

      if (hash.length < 5) {
        return params;
      }

      var vars = hash.split('&');
      for (var i = 0; i < vars.length; i++) {
        if (!vars[i]) {
          continue;
        }
        var pair = vars[i].split('=');
        if (pair.length < 2) {
          continue;
        }
        params[pair[0]] = pair[1];
      }

      if (!params.hasOwnProperty('pid')) {
        return params;
      }
      params.pid = parseInt(params.pid, 10);

      return params;
    }

    /**
     * Triggers when user clicks on buttons, or after changing slides.
     */
    function closeVideo() {
      var btn = pswpElm.querySelector('.is-playing .media__icon--close');
      if (btn !== null) {
        _db.trigger(btn, 'click');
      }
    }

    // Build PhotoSwipe gallery.
    gallery.setAttribute('data-pswp-uid', (uid + 1));
    _db.on(pswpElm, 'click', '.pswp__button', closeVideo);
    _db.on(gallery, 'click', '[data-photoswipe-trigger]', openBox);

    // Parse URL, and auto-open gallery if it contains #&pid=3&gid=1 params.
    var hashData = parseHash();
    if (hashData.pid > 0 && !pswpElm.className.match('pswp--open')) {
      box(hashData.pid - 1);
    }

    gallery.className += ' photoswipe--on';
  }

  /**
   * Theme function for a blazy PhotoSwipe video.
   *
   * @param {Object} settings
   *   An object with the following keys:
   * @param {Array} settings.item
   *   The array of item properties containing: el, w, h.
   *
   * @return {HTMLElement}
   *   Returns a HTMLElement object.
   */
  Drupal.theme.blazyPhotoSwipeMedia = function (settings) {
    var item = settings.item;
    var elm = item.el;
    var img = elm.querySelector('img');
    var alt = img !== null ? img.getAttribute('alt') : '';
    var pad = Math.round(((item.h / item.w) * 100), 2);
    var boxUrl = elm.getAttribute('data-box-url');
    var embedUrl = elm.getAttribute('href');
    var player = item.type === 'video' ? ' media--player' : '';
    var html;

    html = '<div class="pswp__item--html media-wrapper" style="width:' + item.w + 'px">';
    html += '<div class="media media--pswp media--switch' + player + ' media--ratio media--ratio--fluid" style="padding-bottom: ' + pad + '%">';

    if (item.type === 'video') {
      html += '<img src="' + boxUrl + '" class="media__image media__element pswp__img" alt="' + Drupal.t(alt) + '"/>';
      html += '<span class="media__icon media__icon--close"></span>';
      html += '<span class="media__icon media__icon--play" data-url="' + embedUrl + '" data-autoplay="' + embedUrl + '"></span>';
    }

    html += '<iframe src="about:blank" data-src="' + embedUrl + '" width="' + item.w + '" height="' + item.h + '" class="b-lazy media__iframe media__element" allowfullscreen></iframe>';
    html += '</div></div>';

    return html;
  };

  /**
   * Attaches PhotoSwipe behavior to HTML element [data-photoswipe-gallery].
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.blazyPhotoSwipe = {
    attach: function (context) {
      var pswpElm = document.querySelector('.pswp');

      // If body has no container for PhotoSwipe gallery, append it.
      if (drupalSettings.photoswipe.hasOwnProperty('container') && pswpElm === null) {
        // https://developer.mozilla.org/en-US/docs/Web/API/Element/insertAdjacentHTML
        document.body.insertAdjacentHTML('beforeend', drupalSettings.photoswipe.container);
      }

      // Ensures only executed once.
      var galleries = context.querySelectorAll('[data-photoswipe-gallery]:not(.photoswipe--on)');
      _db.once(_db.forEach(galleries, blazyPhotoSwipe, context));
    }
  };

}(Drupal, drupalSettings, PhotoSwipe, PhotoSwipeUI_Default, dBlazy, this, this.document));
