(function ($, Drupal, drupalSettings) {
  'use strict';
  Drupal.behaviors.adChoicesLink = {
    // Initialize properties.
    protocol: 'http',
    basepath: '',
    pid: 0,
    ocid: 0,
    jsPath: '',
    ghost: {},
    icon: {},
    link: {},

    /**
     * Attach Drupal behavior.
     *
     * Adds the adChoicesLink icon to the AdChoices link and adds behaviour to the
     * link to open the adChoicesLink interface.
     */
    attach: function (context, settings) {
      // Variable used to pass this behavior into deeper scopes.
      var adChoicesLink = this;

      // Initialize properties and validate settings.
      if (!adChoicesLink.init(context, settings)) {
        return;
      }

      // Set the icon source path.
      adChoicesLink.icon.attr('src', adChoicesLink.basepath + 'icon1.png');

      // Bind click event to link.
      adChoicesLink.link.once('ad-choices-link-event').on('click', function (event) {
        var f = this;
        event.preventDefault();

        function d(i, l) {
          var j = document.getElementsByTagName("head")[0] || document.documentElement;
          var h = false;
          var g = document.createElement("script");

          function k() {
            g.onload = g.onreadystatechange = null;
            j.removeChild(g);
            l();
          }

          g.src = i;

          g.onreadystatechange = function () {
            if (!h && (this.readyState === "loaded" || this.readyState === "complete")) {
              h = true;
              k();
            }
          };

          g.onload = k;
          j.insertBefore(g, j.firstChild);
        }

        this.onclick = "return false";

        d(adChoicesLink.jsPath + "/bapw.js", function () {
          // Calls the JS included in the tag created above.
          /* globals BAPW */
          BAPW.i(f, {
            pid: adChoicesLink.pid,
            ocid: adChoicesLink.ocid
          }, false);
        });
      });

      adChoicesLink.ghost = new Image();
      adChoicesLink.ghost.src = adChoicesLink.protocol + "://l.betrad.com/pub/p.gif?pid=" + adChoicesLink.pid + "&ocid=" + adChoicesLink.ocid + "&ii=1&r=" + Math.random();
    },

    /**
     * Initialize properties.
     */
    init: function (context, settings) {
      this.protocol = document.location.protocol === "https:" ? "https" : "http";
      this.basepath = "//info.evidon.com/c/betrad/pub/";
      this.icon = $('#_bapw-icon');
      this.link = $('#_bapw-link');

      // If the necessary IDs are not set, hide the link and kick out.
      if (typeof drupalSettings.adChoicesLink.pid === 'undefined'
        || isNaN(parseInt(drupalSettings.adChoicesLink.pid))
        || typeof drupalSettings.adChoicesLink.ocid === 'undefined'
        || isNaN(parseInt(drupalSettings.adChoicesLink.ocid))) {
        this.link.hide();
        console.log('AdChoices pid and/or ocid are not properly set.');
        return false;
      }
      else {
        this.pid = parseInt(drupalSettings.adChoicesLink.pid);
        this.ocid = parseInt(drupalSettings.adChoicesLink.ocid);
        this.jsPath = drupalSettings.adChoicesLink.jsPath;
      }

      // If there are no adChoicesLink links, kick out.
      if (this.link.length === 0) {
        return false;
      }

      return this;
    }
  };
})(jQuery, Drupal, drupalSettings);
