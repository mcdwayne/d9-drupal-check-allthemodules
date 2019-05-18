(function ($, Drupal) {

  'use strict';

  /**
   * Attach the library Reveal.js beahviours to the specific view.
   *
   * @type {Drupal~behavior}
   */
    Drupal.behaviors.revealjs = {

      /**
       * {@inheritDoc}
       */
      attach: function (context, settings) {
        var revealConfig = settings.revealConfig || {};
        this.revealjs(revealConfig);
      },

      revealjs: function (config) {
        var options = config.options || {};

        // Handle dependencies.
        var dependencies = options.dependencies || {};
        options.dependencies = [];

        if (dependencies.classlist && dependencies.classlist[0]) {
          options.dependencies.push({
            src: dependencies.classlist[0], condition: function () {
              return !document.body.classList;
            }

          });
        }

        if (dependencies.highlight && dependencies.highlight[0]) {
          options.dependencies.push({
            src: dependencies.highlight[0],
            async: true,
            callback: function () {
              hljs.initHighlightingOnLoad();
            }
          });
        }

        if (dependencies.markdown && dependencies.markdown.length) {
          for (var i in dependencies.markdown) {
            options.dependencies.push({
              src: dependencies.markdown[i], condition: function () {
                return !!document.querySelector('[data-markdown]');
              }
            });
          }
        }

        if (dependencies.math && dependencies.math[0]) {
          if (config.math_config !== "none") {
            options.math = {
              mathjax: options.math_path,
              config: options.math_config
            };
          }
          options.dependencies.push({src: dependencies.math[0]});
        }

        if (dependencies.notes && dependencies.notes[0]) {
          options.dependencies.push({src: dependencies.notes[0], async: true});
        }

        if (dependencies.zoom && dependencies.zoom[0]) {
          options.dependencies.push({src: dependencies.zoom[0], async: true});
        }

        //Instanciate Reveal.js with built options.
        Reveal.initialize(options);
      }

    };

})(jQuery, Drupal);
