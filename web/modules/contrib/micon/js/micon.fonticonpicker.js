/**
 * @file
 * Vendor jQuery fontIconPicker - v2.0.0.
 *
 * An icon picker built on top of font icons and jQuery.
 *
 *  http://codeb.it/fontIconPicker
 *
 *  Made by Alessandro Benoit & Swashata
 *  Under MIT License
 *
 * {@link https://github.com/micc83/fontIconPicker}
 */

/* eslint-disable */

(function ($) {
  'use strict';
  var defaults = {
    theme: 'fip-grey',
    source: false,
    emptyIcon: true,
    emptyIconValue: '',
    iconsPerPage: 36,
    hasSearch: true,
    searchSource: false,
    allCategoryText: 'From all categories',
    unCategorizedText: 'Uncategorized'
  };

  function Plugin(element, options) {
    this.element = $(element);
    this.settings = $.extend({}, defaults, options);
    if (this.settings.emptyIcon) {
      this.settings.iconsPerPage--;
    }
    this.iconPicker = $('<div/>', {
      class: 'icons-selector',
      style: 'position: relative',
      html: '<div class="selector">' + '<span class="selected-icon">' + '<i class="fip-icon-block"></i>' + '</span>' + '<span class="selector-button">' + '<i class="fip-icon-down-dir"></i>' + '</span>' + '</div>' + '<div class="selector-popup" style="display: none;">' + ((this.settings.hasSearch) ? '<div class="selector-search">' + '<input type="text" name="" value="" placeholder="Search icon" class="icons-search-input"/>' + '<i class="fip-icon-search"></i>' + '</div>' : '') + '<div class="selector-category">' + '<select name="" class="icon-category-select" style="display: none">' + '</select>' + '</div>' + '<div class="fip-icons-container"></div>' + '<div class="selector-footer" style="display:none;">' + '<span class="selector-pages">1/2</span>' + '<span class="selector-arrows">' + '<span class="selector-arrow-left" style="display:none;">' + '<i class="fip-icon-left-dir"></i>' + '</span>' + '<span class="selector-arrow-right">' + '<i class="fip-icon-right-dir"></i>' + '</span>' + '</span>' + '</div>' + '</div>'
    });
    this.iconContainer = this.iconPicker.find('.fip-icons-container');
    this.searchIcon = this.iconPicker.find('.selector-search i');
    this.iconsSearched = [];
    this.isSearch = false;
    this.totalPage = 1;
    this.currentPage = 1;
    this.currentIcon = false;
    this.iconsCount = 0;
    this.open = false;
    this.searchValues = [];
    this.availableCategoriesSearch = [];
    this.triggerEvent = null;
    this.backupSource = [];
    this.backupSearch = [];
    this.isCategorized = false;
    this.selectCategory = this.iconPicker.find('.icon-category-select');
    this.selectedCategory = false;
    this.availableCategories = [];
    this.unCategorizedKey = null;
    this.init();
  }
  Plugin.prototype = {

    init: function () {
      this.iconPicker.addClass(this.settings.theme);
      this.iconPicker.css({left: -9999}).appendTo('body');
      var iconPickerHeight = this.iconPicker.outerHeight();
      var iconPickerWidth = this.iconPicker.outerWidth();
      this.iconPicker.css({left: ''});
      this.element.before(this.iconPicker);
      this.element.css({
        visibility: 'hidden',
        top: 0,
        position: 'relative',
        zIndex: '-1',
        left: '-' + iconPickerWidth + 'px',
        display: 'inline-block',
        height: iconPickerHeight + 'px',
        width: iconPickerWidth + 'px',
        padding: '0',
        margin: '0 -' + iconPickerWidth + 'px 0 0',
        border: '0 none',
        verticalAlign: 'top'
      });
      if (!this.element.is('select')) {
        var ieVersion = (function () {
          var v = 3;
          var div = document.createElement('div');
          var a = div.all || [];
          while (div.innerHTML = '<!--[if gt IE ' + (++v) + ']><br><![endif]-->', a[0]) {
          }
          return v > 4 ? v : !v;
        }());
        var el = document.createElement('div');
        this.triggerEvent = (ieVersion === 9 || !('oninput' in el)) ? ['keyup'] : ['input', 'keyup'];
      }
      if (!this.settings.source && this.element.is('select')) {
        this.settings.source = [];
        this.settings.searchSource = [];
        if (this.element.find('optgroup')
          .length) {
          this.isCategorized = true;
          this.element.find('optgroup')
            .each($.proxy(function (i, el) {
              var thisCategoryKey = this.availableCategories.length,
                categoryOption = $('<option />');
              categoryOption.attr('value', thisCategoryKey);
              categoryOption.html($(el)
                .attr('label'));
              this.selectCategory.append(categoryOption);
              this.availableCategories[thisCategoryKey] = [];
              this.availableCategoriesSearch[thisCategoryKey] = [];
              $(el)
                .find('option')
                .each($.proxy(function (i, cel) {
                  var newIconValue = $(cel)
                    .val(),
                    newIconLabel = $(cel)
                    .html();
                  if (newIconValue && newIconValue !== this.settings.emptyIconValue) {
                    this.settings.source.push(newIconValue);
                    this.availableCategories[thisCategoryKey].push(newIconValue);
                    this.searchValues.push(newIconLabel);
                    this.availableCategoriesSearch[thisCategoryKey].push(newIconLabel);
                  }
                }, this));
            }, this));
          if (this.element.find('> option')
            .length) {
            this.element.find('> option')
              .each($.proxy(function (i, el) {
                var newIconValue = $(el)
                  .val(),
                  newIconLabel = $(el)
                  .html();
                if (!newIconValue || newIconValue === '' || newIconValue == this.settings.emptyIconValue) {
                  return true;
                }
                if (this.unCategorizedKey === null) {
                  this.unCategorizedKey = this.availableCategories.length;
                  this.availableCategories[this.unCategorizedKey] = [];
                  this.availableCategoriesSearch[this.unCategorizedKey] = [];
                  $('<option />')
                    .attr('value', this.unCategorizedKey)
                    .html(this.settings.unCategorizedText)
                    .appendTo(this.selectCategory);
                }
                this.settings.source.push(newIconValue);
                this.availableCategories[this.unCategorizedKey].push(newIconValue);
                this.searchValues.push(newIconLabel);
                this.availableCategoriesSearch[this.unCategorizedKey].push(newIconLabel);
              }, this));
          }
        }
        else {
          this.element.find('option')
            .each($.proxy(function (i, el) {
              var newIconValue = $(el)
                .val(),
                newIconLabel = $(el)
                .html();
              if (newIconValue) {
                this.settings.source.push(newIconValue);
                this.searchValues.push(newIconLabel);
              }
            }, this));
        }
        this.backupSource = this.settings.source.slice(0);
        this.backupSearch = this.searchValues.slice(0);
        this.loadCategories();
      }
      else {
        this.initSourceIndex();
      }
      this.loadIcons();
      this.selectCategory.on('change keyup', $.proxy(function (e) {
        if (this.isCategorized === false) {
          return false;
        }
        var targetSelect = $(e.currentTarget),
          currentCategory = targetSelect.val();
        if (targetSelect.val() === 'all') {
          this.settings.source = this.backupSource;
          this.searchValues = this.backupSearch;
        }
        else {
          var key = parseInt(currentCategory, 10);
          if (this.availableCategories[key]) {
            this.settings.source = this.availableCategories[key];
            this.searchValues = this.availableCategoriesSearch[key];
          }
        }
        this.resetSearch();
        this.loadIcons();
      }, this));
      this.iconPicker.find('.selector-button')
        .click($.proxy(function () {
          this.toggleIconSelector();
        }, this));
      this.iconPicker.find('.selector-arrow-right')
        .click($.proxy(function (e) {
          if (this.currentPage < this.totalPage) {
            this.iconPicker.find('.selector-arrow-left')
              .show();
            this.currentPage = this.currentPage + 1;
            this.renderIconContainer();
          }
          if (this.currentPage === this.totalPage) {
            $(e.currentTarget)
              .hide();
          }
        }, this));
      this.iconPicker.find('.selector-arrow-left')
        .click($.proxy(function (e) {
          if (this.currentPage > 1) {
            this.iconPicker.find('.selector-arrow-right')
              .show();
            this.currentPage = this.currentPage - 1;
            this.renderIconContainer();
          }
          if (this.currentPage === 1) {
            $(e.currentTarget)
              .hide();
          }
        }, this));
      this.iconPicker.find('.icons-search-input')
        .keyup($.proxy(function (e) {
          var searchString = $(e.currentTarget)
            .val();
          if (searchString === '') {
            this.resetSearch();
            return;
          }
          this.searchIcon.removeClass('fip-icon-search');
          this.searchIcon.addClass('fip-icon-cancel');
          this.isSearch = true;
          this.currentPage = 1;
          this.iconsSearched = [];
          $.grep(this.searchValues, $.proxy(function (n, i) {
            if (n.toLowerCase()
              .search(searchString.toLowerCase()) >= 0) {
              this.iconsSearched[this.iconsSearched.length] = this.settings.source[i];
              return true;
            }
          }, this));
          this.renderIconContainer();
        }, this));
      this.iconPicker.find('.selector-search')
        .on('click', '.fip-icon-cancel', $.proxy(function () {
          this.iconPicker.find('.icons-search-input')
            .focus();
          this.resetSearch();
        }, this));
      this.iconContainer.on('click', '.fip-box', $.proxy(function (e) {
        this.setSelectedIcon($(e.currentTarget)
          .find('.micon')
          .attr('data-fip-value'));
        this.toggleIconSelector();
      }, this));
      this.iconPicker.click(function (event) {
        event.stopPropagation();
        return false;
      });
      $('html')
        .click($.proxy(function () {
          if (this.open) {
            this.toggleIconSelector();
          }
        }, this));
    },

    initSourceIndex: function () {
      if (typeof (this.settings.source) !== 'object') {
        return;
      }
      if ($.isArray(this.settings.source)) {
        this.isCategorized = false;
        this.selectCategory.html('')
          .hide();
        this.settings.source = $.map(this.settings.source, function (e, i) {
          if (typeof (e.toString) == 'function') {
            return e.toString();
          }
          else {
            return e;
          }
        });
        if ($.isArray(this.settings.searchSource)) {
          this.searchValues = $.map(this.settings.searchSource, function (e, i) {
            if (typeof (e.toString) == 'function') {
              return e.toString();
            }
            else {
              return e;
            }
          });
        }
        else {
          this.searchValues = this.settings.source.slice(0);
        }
      }
      else {
        var originalSource = $.extend(true, {}, this.settings.source);
        this.settings.source = [];
        this.searchValues = [];
        this.availableCategoriesSearch = [];
        this.selectedCategory = false;
        this.availableCategories = [];
        this.unCategorizedKey = null;
        this.isCategorized = true;
        this.selectCategory.html('');
        for (var categoryLabel in originalSource) {
          var thisCategoryKey = this.availableCategories.length,
            categoryOption = $('<option />');
          categoryOption.attr('value', thisCategoryKey);
          categoryOption.html(categoryLabel);
          this.selectCategory.append(categoryOption);
          this.availableCategories[thisCategoryKey] = [];
          this.availableCategoriesSearch[thisCategoryKey] = [];
          for (var newIconKey in originalSource[categoryLabel]) {
            var newIconValue = originalSource[categoryLabel][newIconKey];
            var newIconLabel = (this.settings.searchSource && this.settings.searchSource[categoryLabel] && this.settings.searchSource[categoryLabel][newIconKey]) ? this.settings.searchSource[categoryLabel][newIconKey] : newIconValue;
            if (typeof (newIconValue.toString) == 'function') {
              newIconValue = newIconValue.toString();
            }
            if (newIconValue && newIconValue !== this.settings.emptyIconValue) {
              this.settings.source.push(newIconValue);
              this.availableCategories[thisCategoryKey].push(newIconValue);
              this.searchValues.push(newIconLabel);
              this.availableCategoriesSearch[thisCategoryKey].push(newIconLabel);
            }
          }
        }
      }
      this.backupSource = this.settings.source.slice(0);
      this.backupSearch = this.searchValues.slice(0);
      this.loadCategories();
    },

    loadCategories: function () {
      if (this.isCategorized === false) {
        return;
      }
      $('<option value="all">' + this.settings.allCategoryText + '</option>')
        .prependTo(this.selectCategory);
      this.selectCategory.show()
        .val('all')
        .trigger('change');
    },

    loadIcons: function () {
      this.iconContainer.html('<i class="fip-icon-spin3 animate-spin loading"></i>');
      if (this.settings.source instanceof Array) {
        this.renderIconContainer();
      }
    },

    renderIconContainer: function () {
      var offset, iconsPaged = [];
      if (this.isSearch) {
        iconsPaged = this.iconsSearched;
      }
      else {
        iconsPaged = this.settings.source;
      }
      this.iconsCount = iconsPaged.length;
      this.totalPage = Math.ceil(this.iconsCount / this.settings.iconsPerPage);
      if (this.totalPage > 1) {
        this.iconPicker.find('.selector-footer')
          .show();
      }
      else {
        this.iconPicker.find('.selector-footer')
          .hide();
      }
      this.iconPicker.find('.selector-pages')
        .html(this.currentPage + '/' + this.totalPage + ' <em>(' + this.iconsCount + ')</em>');
      offset = (this.currentPage - 1) * this.settings.iconsPerPage;
      if (this.settings.emptyIcon) {
        this.iconContainer.html('<span class="fip-box"><i class="fip-icon-block" data-fip-value="fip-icon-block"></i></span>');
      }
      else if (iconsPaged.length < 1) {
        this.iconContainer.html('<span class="icons-picker-error"><i class="fip-icon-block" data-fip-value="fip-icon-block"></i></span>');
        return;
      }
      else {
        this.iconContainer.html('');
      }
      iconsPaged = iconsPaged.slice(offset, offset + this.settings.iconsPerPage);
      for (var i = 0, item; item = iconsPaged[i++];) {
        $('<span/>', {
            html: this.getIconTitle(item),
            'class': 'fip-box',
            title: item
          })
          .appendTo(this.iconContainer)
          .children(':first')
          .attr('data-fip-value', item);
      }
      if (!this.settings.emptyIcon && (!this.element.val() || $.inArray(this.element.val(), this.settings.source) === -1)) {
        this.setSelectedIcon(iconsPaged[0]);
      }
      else if ($.inArray(this.element.val(), this.settings.source) === -1) {
        this.setSelectedIcon();
      }
      else {
        this.setSelectedIcon(this.element.val());
      }
    },

    getIconTitle: function (theIcon) {
      var iconTitle = '';
      $.grep(this.settings.source, $.proxy(function (e, i) {
        if (e === theIcon) {
          iconTitle = this.searchValues[i];
          return true;
        }
        return false;
      }, this));
      return $('<div/>')
        .html(JSON.parse(iconTitle))
        .text();
    },

    setHighlightedIcon: function () {
      this.iconContainer.find('.current-icon')
        .removeClass('current-icon');
      if (this.currentIcon) {
        this.iconContainer.find('[data-fip-value="' + this.currentIcon + '"]')
          .parent('span')
          .addClass('current-icon');
      }
    },

    setSelectedIcon: function (theIcon) {
      if (theIcon === 'fip-icon-block') {
        theIcon = '';
      }
      if (theIcon) {
        this.iconPicker.find('.selected-icon')
          .html(this.getIconTitle(theIcon));
      }
      else {
        this.iconPicker.find('.selected-icon')
          .html('<i class="fip-icon-block micon"></i>');
      }
      this.element.val((theIcon === '' ? this.settings.emptyIconValue : theIcon))
        .trigger('change');
      if (this.triggerEvent !== null) {
        for (var eventKey in this.triggerEvent) {
          this.element.trigger(this.triggerEvent[eventKey]);
        }
      }
      this.currentIcon = theIcon;
      this.setHighlightedIcon();
    },

    toggleIconSelector: function () {
      this.open = (!this.open) ? 1 : 0;
      this.iconPicker.find('.selector-popup')
        .slideToggle(300);
      this.iconPicker.find('.selector-button i')
        .toggleClass('fip-icon-down-dir');
      this.iconPicker.find('.selector-button i')
        .toggleClass('fip-icon-up-dir');
      if (this.open) {
        this.iconPicker.find('.icons-search-input')
          .focus()
          .select();
      }
    },

    resetSearch: function () {
      this.iconPicker.find('.icons-search-input')
        .val('');
      this.searchIcon.removeClass('fip-icon-cancel');
      this.searchIcon.addClass('fip-icon-search');
      this.iconPicker.find('.selector-arrow-left')
        .hide();
      this.currentPage = 1;
      this.isSearch = false;
      this.renderIconContainer();
      if (this.totalPage > 1) {
        this.iconPicker.find('.selector-arrow-right')
          .show();
      }
    }
  };

  $.fn.fontIconPicker = function (options) {
    this.each(function () {
      if (!$.data(this, 'fontIconPicker')) {
        $.data(this, 'fontIconPicker', new Plugin(this, options));
      }
    });

    this.setIcons = $.proxy(function (newIcons, iconSearch) {
      if (undefined === newIcons) {
        newIcons = false;
      }
      if (undefined === iconSearch) {
        iconSearch = false;
      }
      this.each(function () {
        $.data(this, 'fontIconPicker')
          .settings.source = newIcons;
        $.data(this, 'fontIconPicker')
          .settings.searchSource = iconSearch;
        $.data(this, 'fontIconPicker')
          .initSourceIndex();
        $.data(this, 'fontIconPicker')
          .resetSearch();
        $.data(this, 'fontIconPicker')
          .loadIcons();
      });
    }, this);

    this.destroyPicker = $.proxy(function () {
      this.each(function () {
        if (!$.data(this, 'fontIconPicker')) {
          return;
        }
        $.data(this, 'fontIconPicker')
          .iconPicker.remove();
        $.data(this, 'fontIconPicker')
          .element.css({
            visibility: '',
            top: '',
            position: '',
            zIndex: '',
            left: '',
            display: '',
            height: '',
            width: '',
            padding: '',
            margin: '',
            border: '',
            verticalAlign: ''
          });
        $.removeData(this, 'fontIconPicker');
      });
    }, this);

    this.refreshPicker = $.proxy(function (newOptions) {
      if (!newOptions) {
        newOptions = options;
      }
      this.destroyPicker();
      this.each(function () {
        if (!$.data(this, 'fontIconPicker')) {
          $.data(this, 'fontIconPicker', new Plugin(this, newOptions));
        }
      });
    }, this);
    return this;
  };
})(jQuery);
