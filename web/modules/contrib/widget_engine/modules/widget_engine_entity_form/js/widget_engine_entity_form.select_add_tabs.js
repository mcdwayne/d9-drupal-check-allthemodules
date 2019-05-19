/**
 * @file Entity_browser.admin.js.
 *
 * Defines the behavior of the entity browser's Select&Add tabs display.
 */

var EntityBrowser = EntityBrowser || {};

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Registers behaviours related to tabs display.
   */
  Drupal.behaviors.entityBrowserTabs = {
    attach: function (context) {
      var $form = $(context).find('.entity-browser-form').once('entity-browser-admin');
      if (!$form.length) {
        return;
      }
      // Creates basic DOM structure elements.
      var $nav = $('<nav class="eb-tabs"></nav>');
      var $tabs = $(Drupal.theme('entityTabs'));
      var $addOptions = $(Drupal.theme('entityAddList'));

      $form.find('.tab').each(function (index, element) {
        var $element = $(element);
        var classesArray = [];
        classesArray.push($element.attr('disabled') ? 'is-active active' : '');
        classesArray.push($element.attr('tab-class') ? $element.attr('tab-class') : '');
        var classes = classesArray.join(' '),
            tabSettings = {
              class: classes,
              id: $element.attr('id'),
              title: $(this)[0].value
            },
            specialClass = '';

        if ($element.hasClass('view-option-tab')) {
          specialClass = '.view-option-tab';
        } else if ($element.hasClass('add-option-tab')) {
          specialClass = '.add-option-tab';
        }

        if (specialClass) {
          $tabs.find(specialClass).each(function (index, linkElement) {
            var $linkElement = $(linkElement);
            $linkElement.attr('data-button-id', tabSettings.id);
            $linkElement.on('click', function (event) {
              var buttonID = $(event.currentTarget).data().buttonId;
              $form.find('#' + buttonID).trigger('click');
              event.preventDefault();
            });
            if (specialClass == '.view-option-tab') {
              $linkElement.append(tabSettings.title);
              if ($element.attr('disabled')) {
                $linkElement.addClass('is-active active');
              }
              else {
                $tabs.addClass('creation-process');
                $tabs.find('.add-option-tab').addClass('is-active active');
              }
            }
            else if (specialClass == '.add-option-tab' && $element.attr('disabled')) {
              $tabs.addClass('single-item-creation');
              $linkElement.on('click', function (event) {
                event.preventDefault();
                return false;
              });
            }
          });
        }
        else {
          var $tab = $(Drupal.theme('entityTab', tabSettings));
          $tab.appendTo($addOptions);
          // Add a click event handler that submits the hidden input buttons.
          $tab.find('a').on('click', function (event) {
            var buttonID = $(event.currentTarget).data().buttonId;
            $form.find('#' + buttonID).trigger('click');
            event.preventDefault();
          });
        }
      });
      $tabs.appendTo($nav);
      $addOptions.prependTo($form);
      $nav.prependTo($form);

      if ($('.add-tab-open').length > 0) {
        if ($('.add-option-tab-single').length > 0){
          $(".add-option-tab").click();
        } else {
          $('.entity-browser-form div').toggle();
          $('.add-options-list').toggle();
          $('.basic-tabs a').toggleClass('is-active active');
          $('.view-option-tab').on('click', function (event) {
            EntityBrowser.changeIFrameURL();
          });
          $form.show();
        }
      } else {
        $form.show();
      }

      // It will fix aligning for adaptive design.
      $('.add-options-list').append('<li></li><li></li><li></li>');
    }
  };

  /**
   * Registers behaviours related to Select and Add tabs click.
   */
  Drupal.behaviors.basicTabsControl = {
    attach: function (context) {
      var $tabs = $(context).find('.basic-tabs').once('entity-browser-admin');
      if (!$tabs.length || $tabs.hasClass('single-item-creation')) {
        var add_tab = EntityBrowser.getUrlParameter('add_tab');
        if (add_tab) {
          $('a.view-option-tab').on('click', function() {
            EntityBrowser.changeIFrameURL();
          });
        }
        return;
      }
      if ($tabs.hasClass('creation-process')) {
        $tabs.find('a').on('click', function() {
          if (!$(this).hasClass('view-option-tab')) {
            $('.entity-browser-form div').toggle();
            $('.add-options-list').toggle();
          } else {
            var add_tab = EntityBrowser.getUrlParameter('add_tab');
            if (add_tab) {
              EntityBrowser.changeIFrameURL()
            } else {
              $('.basic-tabs a').toggleClass('is-active active');
            }
          }

          return false;
        });
      } else {
        $tabs.find('a').on('click', function() {
          if (!$(this).hasClass('is-active')) {
            $('.entity-browser-form div').toggle();
            $('.add-options-list').toggle();
            $('.basic-tabs a').toggleClass('is-active active');
          }

          return false;
        });
      }
    }
  };

  /**
   * Theme function for entity browser Select&Add tabs.
   *
   * @return {object}
   *   This function returns a jQuery object.
   */
  Drupal.theme.entityTabs = function () {
    return $('<ul role="navigation" aria-label="Tabs" class="basic-tabs"></ul>')
        .append($('<li tabindex="-1"></li>')
            .append($('<a href="#" class="add-option-tab"></a>')
                .append(Drupal.t('Create new'))
            )
        )
        .append($('<li tabindex="-1"></li>')
            .append($('<a href="#" class="view-option-tab"></a>')));
  };

  /**
   * Theme function for an entity browser Add tab items.
   *
   * @param {object} settings
   *   An object with the following keys:
   * @param {string} settings.title
   *   The name of the tab.
   * @param {string} settings.class
   *   Classes for the tab.
   * @param {string} settings.id
   *   ID for the data- button ID.
   *
   * @return {object}
   *   This function returns a jQuery object.
   */
  Drupal.theme.entityTab = function (settings) {
    return $('<li tabindex="-1"></li>')
        .append($('<a href="#" class="tab-create"></a>').addClass(settings.class).attr('data-button-id', settings.id)
            .append(settings.title)
        );
  };

  /**
   * Theme function for entity browser Add tabs wrapper.
   *
   * @return {object}
   *   This function returns a jQuery object.
   */
  Drupal.theme.entityAddList = function () {
    return $('<ul class="add-options-list"></ul>')
        .css('display', 'none');
  };

  /**
   * Helper function for getting query parameters from URL.
   *
   * @param name
   *   Url query parameter to find.
   * @returns {string}
   */
  EntityBrowser.getUrlParameter = function (name) {
    name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
    var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
    var results = regex.exec(location.search);
    return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
  };

  /**
   * Helper function for changing parent entity browser iFrame src.
   */
  EntityBrowser.changeIFrameURL = function () {
    var $iframe = $('iframe.entity-browser-modal-iframe', top.document),
        src = $iframe.attr('src'),
        res_src = src.replace('add_tab=true', '');
    $iframe.attr('src', res_src);
  }

}(jQuery, Drupal, drupalSettings));
