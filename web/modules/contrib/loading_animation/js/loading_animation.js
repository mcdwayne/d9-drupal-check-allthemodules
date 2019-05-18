/**
 * @file
 * Attaches behavior for loading animation.
 */

(function(Drupal, String) {

  'use strict';

  /**
   * Initialization
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the loading animation functionality to the page.
   */
  Drupal.behaviors.loading_animation = {
    attach : function(context, settings) {
      // Initialize general loading animation.
      Drupal.behaviors.loading_animation.loadingAnimation = new LoadingAnimation(context, settings.loading_animation);
    }
  };


  /**
   * Represents the loading animation.
   */
  var LoadingAnimation = function(context, settings) {
    this.settings = settings;
    var my_state = this;

    this._init = function(context, settings) {
      // Show loading animation on href click.
      if (settings.show_on_href) {
        var links = document.querySelectorAll('a[href]' + settings.subselector + ':not([href*="javascript:"]):not([href^="#"]):not(.noLoadingAnimation):not([target="_blank"])');

        // Other modules might want to modify the list of available links.
        links = my_state.callAlterLinksCallbacks(links);

        for (var i = 0; i < links.length; i++) {
          links[i].addEventListener('click', function(eventObject) {
            // Loading animation should not be shown if link is opened in a
            // new tab.
            if (!eventObject.ctrlKey) {
              Drupal.behaviors.loading_animation.loadingAnimation.show();
            }
          });
        }
      }

      // Show loading animation on form submit.
      if (settings.show_on_form_submit) {
        var forms = document.querySelectorAll('form' + settings.subselector);
        for (var i = 0; i < forms.length; i++) {
          forms[i].addEventListener('submit', function() {
            Drupal.behaviors.loading_animation.loadingAnimation.show();
          });
        }
      }

      // Hide on ESC press.
      if (settings.close_on_esc) {
        document.addEventListener('keydown', function(event) {
          var keycode = event.which;
          if (keycode == 27) { // escape, close box
            Drupal.behaviors.loading_animation.loadingAnimation.hide();
          }
        });
      }
    };

    /**
     * Attaches an event listener to close the animation on click.
     */
    this.closeOnClick = function () {
      // Hide on animation click!
      if (settings.close_on_click) {
        var loading_animation = document.querySelectorAll('.loading-animation');
        for (var i = 0; i < loading_animation.length; i++) {
          loading_animation[i].addEventListener('click', function () {
            Drupal.behaviors.loading_animation.loadingAnimation.hide();
          });
        }
      }
    }

    /**
     * Displays the loading animation.
     */
    this.show = function() {
      // Only show if not already shown.
      if (document.querySelectorAll('.loading-animation').length === 0) {
        var html = Drupal.theme.loadingAnimationLoader();
        document.body.appendChild(html.toDOM());

        // Allow closing loading animation on click.
        my_state.closeOnClick();
      }
    };

    /**
     * Hides the loading animation.
     */
    this.hide = function() {
      var loading_animation = document.querySelectorAll('.loading-animation');
      for (var i = 0; i < loading_animation.length; i++) {
        loading_animation[i].parentNode.removeChild(loading_animation[i]);
      }
    };

    /**
     * Executes given callbacks with data.
     */
    this.callAlterLinksCallbacks = function(links) {
      var callbacks = Drupal.loading_animation.linkListAlterCallbacks;
      for (var key in callbacks) {
        if (typeof callbacks[key] == 'function') {
          links = callbacks[key](links);
        }
      }

      return links;
    };

    // Initialize!
    this._init(context, settings);

    // Register global Drupal.behaviors.loading_animation.loadingAnimation
    // object after init.
    Drupal.behaviors.loading_animation.loadingAnimation = this;

    // Change behavior of back button so that loading animation won't be visible
    // after using back to previous page functionality.
    // Firefox:
    function UnloadHandler() { window.removeEventListener('unload', UnloadHandler, false); }
    window.addEventListener('unload', UnloadHandler, false);

    // iOS Safari:
    window.addEventListener('pageshow', function (event) {
      if (event.persisted) {
        window.location.reload()
      }
    }, false);
  };

  /**
   * Tests is object is empty
   *
   * @param object
   *    Object to be tested.
   *
   * @returns {boolean}
   */
  function isEmpty(object) {
    for (var property in object) {
      return false;
    }
    return true;
  }

  /**
   * Converts string object to DOM object.
   *
   * @returns {DocumentFragment}
   */
  String.prototype.toDOM = function(){
    var item;
    var element = document.createElement('div')
    var fragment = document.createDocumentFragment();
    element.innerHTML = this;

    // Loop through the elements till the object is created.
    while(item = element.firstChild)fragment.appendChild(item);

    return fragment;
  };

  /**
   * @namespace
   */
  Drupal.loading_animation = {

    /**
     * Callbacks to modify the list of links.
     */
    linkListAlterCallbacks: {}

  };

})(Drupal, String);

