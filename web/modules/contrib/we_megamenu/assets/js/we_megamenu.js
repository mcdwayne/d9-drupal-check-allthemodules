Drupal.WeMegaMenu = Drupal.WeMegaMenu || {};

(function ($, Drupal, drupalSettings) {
  "use strict";
  var self = Drupal.WeMegaMenu;
  Drupal.WeMegaMenu.currentSelected = null;
  Drupal.WeMegaMenu.toolbar = $('.we-mega-menu-toolbar');
  Drupal.WeMegaMenu.menu = $('nav.navbar-we-mega-menu');
  

  Drupal.behaviors.kMegaMenuBackendAction = {
    attach: function (context) {
      Drupal.WeMegaMenu.clickActions();
      Drupal.WeMegaMenu.initBackend();
      Drupal.WeMegaMenu.loadToolbar();
      Drupal.WeMegaMenu.defineToolbarEvent();
      Drupal.WeMegaMenu.saveConfig();
      Drupal.WeMegaMenu.resetConfig();
      Drupal.WeMegaMenu.chosenConfig();
      Drupal.WeMegaMenu.loadDefaultMenuConfig();
      Drupal.WeMegaMenu.autocompleteIcons();
      Drupal.WeMegaMenu.backendStyle();
    }
  };

  $.fn.hasAttr = function(name) {  
    return this.attr(name) !== undefined;
  };

  Drupal.WeMegaMenu.clickActions = function() {
    $('li.we-mega-menu-li a').once('li-we-mega-menu-li-a').on('click', function(e) {
      e.preventDefault();
    });

    $('nav, li.we-mega-menu-li, div.we-mega-menu-submenu, div.we-mega-menu-row-old, div.we-mega-menu-col').hover(function(e) {
      $('nav, li.we-mega-menu-li, div.we-mega-menu-submenu, div.we-mega-menu-row-old, div.we-mega-menu-col').find('.hover').removeClass('hover');
      $(this).addClass('hover');
      e.stopPropagation();
    }, function(e) {
      $(this).removeClass('hover');
      if ($(this).hasClass('we-mega-menu-li')) {
        $(this).closest('.we-mega-menu-col').addClass('hover');  
      }
      if ($(this).hasClass('we-mega-menu-col')) {
        $(this).closest('.we-mega-menu-submenu').addClass('hover');  
      }
      e.stopPropagation();
    });

    $('span.close.icon-remove').once('span-close-icon-remove').on('click', function() {
      var $col_block = $(this).closest('.we-mega-menu-col');
      $col_block.removeAttr('data-block');
      $col_block.html('');
      self.toolbar.find('.cbx-select-block').eq(0).val('').trigger('chosen:updated');
    });

    $('nav, li.we-mega-menu-li, div.we-mega-menu-submenu, div.we-mega-menu-row-old, div.we-mega-menu-col').once('div.nav-item').on('click', function(e) {
      self.currentSelected = $(this);
      self.menu.find('.selected').removeClass('selected');
      self.currentSelected.addClass('selected');
      e.stopPropagation();

      var type = self.currentSelected.attr('data-element-type');
      switch (type) {
        case 'we-mega-menu-li':
          self.hideToolbar();
          self.showToolbar('.we-mega-menu-item-config');
          self.loadToolbarConfig();
          var li = self.currentSelected;
          if (li.hasClass('open')) {
            $(li).closest('ul').find('li').removeClass('open');
            $(li).closest('.we-mega-menu-row').find('li').removeClass('open');
            li.removeClass('open');
          } else {
            $(li).closest('ul').find('li').removeClass('open');
            $(li).closest('.we-mega-menu-row').find('li').removeClass('open');
            $(li).closest('ul').find('li').removeClass('open');
            $(li).addClass('open');
          }

          break;

        case 'we-mega-menu-submenu':
          self.hideToolbar();
          self.showToolbar('.we-mega-menu-submenu-config');
          self.loadToolbarConfig();
          break;

        case 'we-mega-menu-row':
          self.hideToolbar();
          self.showToolbar('.we-mega-menu-column-config');
          self.loadToolbarConfig();
          break;

        case 'we-mega-menu-col':
          self.hideToolbar();
          self.showToolbar('.we-mega-menu-column-config');
          self.loadToolbarConfig();
          break;

        default:
          self.hideToolbar();
          self.showToolbar('.we-mega-menu-config');
          Drupal.WeMegaMenu.resetMenu();
          Drupal.WeMegaMenu.loadDefaultMenuConfig();
      }
    });
  };

  Drupal.WeMegaMenu.initBackend = function() {
    Drupal.WeMegaMenu.rebuildMainToolbar();

    Drupal.WeMegaMenu.colMarkup = '';
    Drupal.WeMegaMenu.colMarkup += '<div class="we-mega-menu-col selected span12" data-width="12" data-element-type="we-mega-menu-col" data-blocktitle="1">';
      Drupal.WeMegaMenu.colMarkup += '<ul class="nav nav-tabs subul">';
      Drupal.WeMegaMenu.colMarkup += '</ul>';
    Drupal.WeMegaMenu.colMarkup += '</div>';
  };

  Drupal.WeMegaMenu.loadDefaultMenuConfig = function() {
    var theme = Drupal.WeMegaMenu.menu.attr('data-block-theme');
    var style = Drupal.WeMegaMenu.menu.attr('data-style').length ? Drupal.WeMegaMenu.menu.attr('data-style') : 'Default';
    var animation = Drupal.WeMegaMenu.menu.attr('data-animation').length ? Drupal.WeMegaMenu.menu.attr('data-animation') : 'None';
    var delay = Drupal.WeMegaMenu.menu.attr('data-delay');
    var duration = Drupal.WeMegaMenu.menu.attr('data-duration');
    var autoarrow = Drupal.WeMegaMenu.menu.attr('data-autoarrow');
    var alwayshowsubmenu = Drupal.WeMegaMenu.menu.attr('data-alwayshowsubmenu');
    var mobile_collapse = Drupal.WeMegaMenu.menu.attr('data-mobile-collapse');
    var data_action = Drupal.WeMegaMenu.menu.attr('data-action');
    Drupal.WeMegaMenu.toolbar.find('select.we-mega-menu-cbx-style').eq(0).val(style).trigger('chosen:updated');
    Drupal.WeMegaMenu.toolbar.find('select.we-mega-menu-cbx-animation').eq(0).val(animation).trigger('chosen:updated').trigger('change');
    Drupal.WeMegaMenu.toolbar.find('input.we-mega-menu-chx-auto-arrow').eq(0).prop('checked', autoarrow == 1 ? true : false);
    Drupal.WeMegaMenu.toolbar.find('input.we-mega-menu-chx-alway-show-submenu').eq(0).prop('checked', alwayshowsubmenu == 1 ? true : false);
    Drupal.WeMegaMenu.toolbar.find('input.we-mega-menu-txt-delay').eq(0).val(delay);
    Drupal.WeMegaMenu.toolbar.find('input.we-mega-menu-txt-duration').eq(0).val(duration);
    Drupal.WeMegaMenu.toolbar.find('input.we-mega-menu-chx-mobile-collapse').eq(0).prop('checked', mobile_collapse == 1 ? true : false);
    Drupal.WeMegaMenu.toolbar.find('select.we-mega-menu-cbx-action').eq(0).val(data_action).trigger('chosen:updated');
  };

  Drupal.WeMegaMenu.loadToolbarConfig = function() {
    if ($(self.currentSelected).find('div.we-mega-menu-submenu').length > 0) {
      self.toolbar.find('input.we-mega-menu.we-mega-menu-btn-submenu').prop('checked', true);
    } else {
      self.toolbar.find('input.we-mega-menu.we-mega-menu-btn-submenu').prop('checked', false);
    }

    if ($(self.currentSelected).find('ul.subul li.we-mega-menu-li').length == 0) {
      self.toolbar.find('input.we-mega-menu.we-mega-menu-btn-submenu').closest('.submenu-wrapper').show();
    } else {
      self.toolbar.find('input.we-mega-menu.we-mega-menu-btn-submenu').closest('.submenu-wrapper').hide();
    }

    if ($(self.currentSelected).attr('data-level') == '0') {
      self.toolbar.find('input.we-mega-menu-chx-group').closest('.group-menu-wrapper').hide();
    } else {
      self.toolbar.find('input.we-mega-menu-chx-group').closest('.group-menu-wrapper').show();
    }

    if (self.currentSelected.hasAttr('data-class')) {
      var class_val = self.currentSelected.attr('data-class');
      $(self.toolbar).find('.we-mega-menu-txt-extra-class').val(class_val);
    } else {
      $(self.toolbar).find('.we-mega-menu-txt-extra-class').val('');
    }

    if (self.currentSelected.hasAttr('data-icon')) {
      var icon = self.currentSelected.attr('data-icon');
      $(self.toolbar).find('.we-mega-menu-txt-icon').val(icon);
    } else {
      $(self.toolbar).find('.we-mega-menu-txt-icon').val('');
    }

    if (self.currentSelected.hasAttr('data-caption')) {
      var caption = self.currentSelected.attr('data-caption');
      $(self.toolbar).find('.we-mega-menu-txt-caption').val(caption);
    } else {
      $(self.toolbar).find('.we-mega-menu-txt-caption').val('');
    }

    if (self.currentSelected.hasAttr('data-target')) {
      var target = self.currentSelected.attr('data-target');
      $(self.toolbar).find('select.we-mega-menu-cbx-target').val(target).trigger('chosen:updated');;
    } else {
      $(self.toolbar).find('select.we-mega-menu-cbx-target').val('_self').trigger('chosen:updated');;
    }

    if (self.currentSelected.hasAttr('data-group')) {
      if (self.currentSelected.attr('data-group') == 0) {
        self.toolbar.find('.we-mega-menu-chx-group').prop('checked', false);
      } else {
        self.toolbar.find('.we-mega-menu-chx-group').prop('checked', true);
      }
    }

    if (self.currentSelected.hasClass('we-mega-menu-submenu')) {
      var $li = self.currentSelected.closest('li');
      if ($li.attr('hide-sub-when-collapse') == 0) {
        self.toolbar.find('.hide-sub-when-collapse').prop('checked', false);
      } else {
        self.toolbar.find('.hide-sub-when-collapse').prop('checked', true);
      }
    }

    if (self.currentSelected.hasClass('we-mega-menu-li')) {
      var level = self.currentSelected.attr('data-level');
      if (level == 0) {
        self.toolbar.find('.we-mega-menu-btn-break-col').eq(0).closest('.form-group').hide();
      } else {
        self.toolbar.find('.we-mega-menu-btn-break-col').eq(0).closest('.form-group').show();
      }
    }

    if (self.currentSelected.hasAttr('data-submenu-width')) {
      var value = self.currentSelected.attr('data-submenu-width');
      if (value.length) {
        self.toolbar.find('.we-mega-menu-txt-sub-menu-width').val(value);
      } else {
        self.toolbar.find('.we-mega-menu-txt-sub-menu-width').val('');
      }
    } else {
      self.toolbar.find('.we-mega-menu-txt-sub-menu-width').val('');
    }

    if (self.currentSelected.closest('li').hasAttr('data-alignsub')) {
      var align = self.currentSelected.closest('li').attr('data-alignsub');
      
      // Reset all
      self.toolbar.find('.we-mega-menu-align-btn-group button').removeClass('active');
      self.toolbar.find('.we-mega-menu-align-btn-group button[data-value="' + align + '"]').addClass('active');
    } else {
      self.toolbar.find('.we-mega-menu-align-btn-group button').removeClass('active');
    }

    if (self.currentSelected.hasClass('we-mega-menu-col')) {
      if (self.currentSelected.attr('data-hidewhencollapse') == 1) {
        self.toolbar.find('.we-mega-menu-btn-hide-when-collapse').prop('checked', true);
      } else {
        self.toolbar.find('.we-mega-menu-btn-hide-when-collapse').prop('checked', false);
      }
    }

    if (self.currentSelected.hasClass('we-mega-menu-col')) {
      if (self.currentSelected.hasAttr('data-width')) {
        var data_width = self.currentSelected.attr('data-width');
        self.toolbar.find('.cbx-we-mega-menu-col-width').eq(0).val(data_width).trigger('chosen:updated');
      } else {
        self.toolbar.find('.cbx-we-mega-menu-col-width').eq(0).val(12).trigger('chosen:updated');
      }
    }

    if (self.currentSelected.hasClass('we-mega-menu-col')) {
      if (self.currentSelected.hasAttr('data-block')) {
        var block_id = self.currentSelected.attr('data-block');
        self.toolbar.find('.cbx-select-block').eq(0).val(block_id).trigger('chosen:updated');
      } else {
        self.toolbar.find('.cbx-select-block').eq(0).val('');
      }
    }

    if (self.currentSelected.hasClass('we-mega-menu-col')) {
      if (self.currentSelected.hasAttr('data-blocktitle')) {
        var title = self.currentSelected.attr('data-blocktitle');
        if (title == 1) {
          self.toolbar.find('.btn-show-block-title').prop('checked', true);
        } else {
          self.toolbar.find('.btn-show-block-title').prop('checked', false);
        }
      }
    }

    if (self.currentSelected.hasClass('we-mega-menu-col')) {
      if (typeof self.currentSelected.find('li[data-element-type="we-mega-menu-li"]').length != 'undefined' && self.currentSelected.find('li[data-element-type="we-mega-menu-li"]').length) {
          self.toolbar.find('select.cbx-select-block').closest('div.form-group').hide();
        self.toolbar.find('input.btn-show-block-title').closest('div.form-group').hide();
      } else {
        self.toolbar.find('select.cbx-select-block').closest('div.form-group').show();
        self.toolbar.find('input.btn-show-block-title').closest('div.form-group').show();  
      }
    }
  };

  Drupal.WeMegaMenu.loadToolbar = function() {
    $('.we-mega-menu-toolbar .we-mega-menu-config').show();
    $('.we-mega-menu-toolbar .we-mega-menu-actions').show();
  };

  Drupal.WeMegaMenu.hideToolbar = function() {
    $('.we-mega-menu-toolbar .we-mega-menu-bar').each(function() {
      if (!$(this).hasClass('we-mega-menu-actions')) {
        $(this).hide();
      }
    }); 
  };

  Drupal.WeMegaMenu.showToolbar = function(name) {
    $('.we-mega-menu-toolbar ' + name).show();
  };

  Drupal.WeMegaMenu.resetMenu = function() {
    $('nav ul li.open').removeClass('open');
  };

  Drupal.WeMegaMenu.rebuildMainToolbar = function() {
    if (typeof self.toolbar.find('.we-mega-menu-cbx-animation').eq(0).val() != 'undefined') {
      var animation = self.toolbar.find('.we-mega-menu-cbx-animation').eq(0).val();
      if (animation.length && animation != 'None') {
        self.toolbar.find('.we-mega-menu-delay').show();
        self.toolbar.find('.we-mega-menu-duration').show();
      } else {
        self.toolbar.find('.we-mega-menu-delay').hide();
        self.toolbar.find('.we-mega-menu-duration').hide();
      }
    }
  };

  Drupal.WeMegaMenu.defineToolbarEvent = function() {
    // self.toolbar.find('.we-mega-menu-cbx-animation').once('we-mega-menu-cbx-animation').on('change', function() {
    //   Drupal.WeMegaMenu.rebuildMainToolbar();
    // });

    $('.we-mega-menu.we-mega-menu-btn-submenu').once('we-mega-menu-btn-submenu').on('change', function() {
      if ($(this).is(':checked')) {
        if (self.currentSelected.hasClass('we-mega-menu-li')) {
          var li = self.currentSelected;
          $(li).addClass('dropdown-menu open');
          $(li).attr('data-submenu', 1);
          var html = '';
          html += '<div class="we-mega-menu-submenu" data-element-type="we-mega-menu-submenu">';
            html += '<div class="we-mega-menu-submenu-inner">';
              html += '<div class="we-mega-menu-row" data-element-type="we-mega-menu-row">';
                html += '<div class="we-mega-menu-col span12" data-width="12" data-element-type="we-mega-menu-col" data-blocktitle="1">';
                html += '</div>';
              html += '</div>';
            html += '</div>';
          html += '</div>';
          $(li).append(html);
          Drupal.WeMegaMenu.clickActions();
        }
      } else {
        if (self.currentSelected.hasClass('we-mega-menu-li')) {
          var li = self.currentSelected.closest('li');
          $(li).removeClass('dropdown-menu open');
          $(li).attr('data-submenu', 0);
          $(li).find('div.we-mega-menu-submenu').remove();
          Drupal.WeMegaMenu.clickActions();
        }
      }

      // Rebind event
      Drupal.WeMegaMenu.clickActions();
    });

    $(self.toolbar).find('.we-mega-menu-btn-add-row').once('we-mega-menu-btn-add-row').on('click', function() {
      self.menu.find('.selected').removeClass('selected');
      var html = '';
      html += '<div class="we-mega-menu-row" data-element-type="we-mega-menu-row">';
        html += Drupal.WeMegaMenu.colMarkup;
      html += '</div>';
      self.currentSelected.find('.we-mega-menu-submenu-inner').eq(0).append(html);
      setTimeout(function() {
        self.menu.find('.selected').trigger('click');
      }, 100);

      // Rebind event
      Drupal.WeMegaMenu.clickActions();
    });

    $(self.toolbar).find('.we-mega-menu-btn-add-col').once('we-mega-menu-btn-add-col').on('click', function() {
      if (self.currentSelected.closest('.we-mega-menu-row').children('.we-mega-menu-col').length < 12) {
        self.menu.find('.selected').removeClass('selected');
        var html = Drupal.WeMegaMenu.colMarkup;
        self.currentSelected.after(html);

        // Rebind event
        Drupal.WeMegaMenu.clickActions();

        // Calc Width
        var curCol = self.currentSelected.closest('.we-mega-menu-col');

        // Set width
        Drupal.WeMegaMenu.calcColWidthLayout(curCol.closest('.we-mega-menu-row'));

        // Active current selected
        self.currentSelected.next().trigger('click');
      }
    });

    $(self.toolbar).find('.we-mega-menu-btn-remove-col').once('we-mega-menu-btn-remove-col').on('click', function() {
      if (self.currentSelected.closest('.we-mega-menu-row').children('.we-mega-menu-col').length <= 12) {
        var currentColNumber = self.currentSelected.closest('.we-mega-menu-row').children('.we-mega-menu-col').length;
        if (currentColNumber == 1) {
          var tmpCurrentSelected = self.currentSelected.closest('.we-mega-menu-row').parent();
          self.currentSelected.closest('.we-mega-menu-row').remove();
          self.currentSelected = tmpCurrentSelected;
        } else {
          var curCol = self.currentSelected.children('.we-mega-menu-col');
          var closest_row = self.currentSelected.closest('.we-mega-menu-row');
          var currentSelectedTmp = self.currentSelected.prev();
          self.currentSelected.remove();
          self.currentSelected = currentSelectedTmp;

          // Rebind event
          Drupal.WeMegaMenu.clickActions();

          // Calc Width
          Drupal.WeMegaMenu.calcColWidthLayout(closest_row);
        }

        // Active current selected
        self.currentSelected.trigger('click');
      }
    });

    var previous_class = null;
    $(self.toolbar).find('.we-mega-menu-txt-extra-class').once('we-mega-menu-txt-extra-class').on('focus', function () {
      previous_class = this.value;
    }).change(function() {
      var class_val = $(this).val();
      if (class_val.length) {
        self.currentSelected.removeClass(previous_class);
        self.currentSelected.attr('data-class', class_val);
        self.currentSelected.addClass(class_val);  
      } else {
        var class_val = self.currentSelected.attr('data-class');
        self.currentSelected.removeAttr('data-class');
        self.currentSelected.removeClass(class_val);
      }
      previous_class = this.value;
    });

    $(self.toolbar).find('.we-mega-menu-txt-icon').once('we-mega-menu-txt-icon').on('change', function() {
      var icon = $(this).val();
      if (icon.length) {
        if (self.currentSelected.find('i').length) {
          self.currentSelected.find('i').eq(0).attr('class', icon);
        } else {
          var html = '<i class="' + icon + '"></i>';
          self.currentSelected.find('a').eq(0).prepend(html);
        }
        self.currentSelected.attr('data-icon', icon);
      } else {
        self.currentSelected.removeAttr('data-icon');
        self.currentSelected.find('a i').eq(0).remove();
      }
    });

    $(self.toolbar).find('.we-mega-menu-txt-caption').once('we-mega-menu-txt-caption').on('change', function() {
      var caption = $(this).val();
      if (caption.length) {
        if (self.currentSelected.find('span.we-mega-menu-caption').length) {
          self.currentSelected.find('span.we-mega-menu-caption').eq(0).text(caption);
        } else {
          var html = '<span class="we-mega-menu-caption">' + caption + '</span>';
          self.currentSelected.find('a').eq(0).append(html);
        }
        self.currentSelected.attr('data-caption', caption);
      } else {
        self.currentSelected.removeAttr('data-caption');
        self.currentSelected.find('a span.we-mega-menu-caption').eq(0).remove();
      }
    });

    $(self.toolbar).find('select.we-mega-menu-cbx-target').once('we-mega-menu-cbx-target').on('change', function() {
      var target = $(this).val();
      if (target.length) {
        self.currentSelected.attr('data-target', target);
        self.currentSelected.attr('target', target);
        self.currentSelected.find('a.we-mega-menu-li').eq(0).attr('target', target);
      } else {
        self.currentSelected.attr('data-target', '_self');
        self.currentSelected.attr('target', '_self');
        self.currentSelected.find('a.we-mega-menu-li').eq(0).attr('target', '_self');
      }
    });

    // SUBMENU
    self.toolbar.find('.hide-sub-when-collapse').once('hide-sub-when-collapse').on('change', function() {
      if (self.currentSelected.hasClass('we-mega-menu-submenu')) {
        if ($(this).is(':checked')) {
          self.currentSelected.closest('li').attr('hide-sub-when-collapse', 1);
          self.currentSelected.closest('li').addClass('sub-hidden-collapse');
        } else {
          self.currentSelected.closest('li').attr('hide-sub-when-collapse', 0);
          self.currentSelected.closest('li').removeClass('sub-hidden-collapse');
        }
      }
    });


    self.toolbar.find('.we-mega-menu-txt-sub-menu-width').once('we-mega-menu-txt-sub-menu-width').on('change', function() {
      var width = $(this).val();
      if (self.currentSelected.hasClass('we-mega-menu-submenu')) {
        if (width.length) {
          width = parseInt(width);
          width = (width >= 5120) ? 5120 : width; // resolution 5k retina
          width = (width <= 0) ? '' : width;
          self.currentSelected.attr('data-submenu-width', width);
          self.currentSelected.css('width', width);
          $(this).val(width);
        } else {
          self.currentSelected.removeAttr('data-submenu-width');
          self.currentSelected.css('width', '');
        }
      }
    });

    self.toolbar.find('.we-mega-menu-align-btn-group button').once('we-mega-menu-align-btn-group').on('click', function() {
      var type = $(this).attr('data-value');
      if ($(this).hasClass('active')) {
        $(this).removeClass('active');
        self.currentSelected.closest('li').removeAttr('data-alignsub');
        self.currentSelected.closest('li').removeClass(type);
      } else {
        $(this).closest('.we-mega-menu-align-btn-group').find('button').removeClass('active');
        $(this).addClass('active');
        self.currentSelected.closest('li').removeClass('left');
        self.currentSelected.closest('li').removeClass('right');
        self.currentSelected.closest('li').removeClass('center');
        self.currentSelected.closest('li').removeClass('justify');
        self.currentSelected.closest('li').attr('data-alignsub', type);
        self.currentSelected.closest('li').addClass(type);
      }
    });

    self.toolbar.find('.we-mega-menu-btn-hide-when-collapse').once('we-mega-menu-btn-hide-when-collapse').on('change', function() {
      if (self.currentSelected.hasClass('we-mega-menu-col')) {
        if ($(this).is(':checked')) {
          self.currentSelected.addClass('hidden-collapse');
          self.currentSelected.attr('data-hidewhencollapse', 1);
        } else {
          self.currentSelected.removeClass('hidden-collapse');
          self.currentSelected.attr('data-hidewhencollapse', 0);
        }
      }
    });

    var previous_class;
    self.toolbar.find('.cbx-we-mega-menu-col-width').eq(0).once('cbx-we-mega-menu-col-width').on('focus', function () {
      var value = self.currentSelected.attr('data-width');
      previous_class = 'span' + value;
    }).change(function() {
      var value = self.currentSelected.attr('data-width');
      previous_class = 'span' + value;
      self.currentSelected.removeClass(previous_class);
      self.currentSelected.addClass('span' + this.value);
      self.currentSelected.attr('data-width', this.value);
      previous_class = 'span' + this.value;
    });

    self.toolbar.find('.cbx-select-block').once('cbx-select-block').on('change', function() {
      var bid = $(this).val();
      $.ajax({
        type: "POST",
        url: drupalSettings.path.baseUrl + 'we-mega-menu/ajax/block',
        data: {
          bid: bid,
          section: 'admin',
          title: self.currentSelected.attr('data-blocktitle')
        },
        success: function(response){
          self.currentSelected.html('');
          if (response.length) {
            self.currentSelected.append(response);
            self.currentSelected.attr('data-block', bid);
          } else {
            self.currentSelected.removeAttr('data-block');
          }
          Drupal.WeMegaMenu.clickActions();
        }
      });
    });

    self.toolbar.find('.btn-show-block-title').once('btn-show-block-title').on('change', function() {
      if (self.currentSelected.hasClass('we-mega-menu-col')) {
        if ($(this).is(':checked')) {
          self.currentSelected.attr('data-blocktitle', 1);  
        } else {
          self.currentSelected.attr('data-blocktitle', 0);
        }

        self.toolbar.find('.cbx-select-block').trigger('change');
      }
    });

    self.toolbar.find('.we-mega-menu-cbx-style').once('we-mega-menu-cbx-style').on('change', function() {
      var menu = $('nav.navbar-we-mega-menu');
      menu.attr('data-style', $(this).val());
    });

    self.toolbar.find('.we-mega-menu-cbx-animation').once('we-mega-menu-cbx-animation').on('change', function() {
      var menu = $('nav.navbar-we-mega-menu');
      menu.attr('data-animation', $(this).val());
    });

    self.toolbar.find('.we-mega-menu-txt-delay').once('we-mega-menu-txt-delay').on('change', function() {
      var menu = $('nav.navbar-we-mega-menu');
      menu.attr('data-delay', $(this).val());
    });

    self.toolbar.find('.we-mega-menu-txt-duration').once('we-mega-menu-txt-duration').on('change', function() {
      var menu = $('nav.navbar-we-mega-menu');
      menu.attr('data-duration', $(this).val());
    });

    self.toolbar.find('.we-mega-menu-chx-auto-arrow').once('we-mega-menu-chx-auto-arrow').on('change', function() {
      var menu = $('nav.navbar-we-mega-menu');
      if ($(this).is(':checked')) {
        menu.attr('data-autoarrow', 1);
      } else {
        menu.attr('data-autoarrow', 0);
      }
    });

    self.toolbar.find('.we-mega-menu-cbx-action').once('we-mega-menu-cbx-action').on('change', function() {
      var menu = $('nav.navbar-we-mega-menu');
      menu.attr('data-action', $(this).val());
    });

    self.toolbar.find('.we-mega-menu-chx-alway-show-submenu').once('we-mega-menu-chx-alway-show-submenu').on('change', function() {
      var menu = $('nav.navbar-we-mega-menu');
      if ($(this).is(':checked')) {
        menu.attr('data-alwayshowsubmenu', 1);
      } else {
        menu.attr('data-alwayshowsubmenu', 0);
      }
    });

    self.toolbar.find('.we-mega-menu-chx-group').once('we-mega-menu-chx-group').on('change', function() {
      if (self.currentSelected.hasClass('we-mega-menu-li')) {
        if ($(this).is(':checked')) {
          self.currentSelected.addClass('we-mega-menu-group');
          self.currentSelected.attr('data-group', 1);
        } else {
          self.currentSelected.removeClass('we-mega-menu-group');
          self.currentSelected.attr('data-group', 0);
        }
      }
    });

    self.toolbar.find('.we-mega-menu-chx-mobile-collapse').once('we-mega-menu-chx-mobile-collapse').on('change', function() {
      var menu = $('nav.navbar-we-mega-menu');
      if ($(this).is(':checked')) {
        menu.attr('data-mobile-collapse', 1);
        menu.addClass('mobile-collapse');
      } else {
        menu.attr('data-mobile-collapse', 0);
        menu.removeClass('mobile-collapse');
      }
    });;

    self.toolbar.find('.we-mega-menu-btn-break-col').once('we-mega-menu-btn-break-col').on('click', function() {
      if (self.currentSelected.hasClass('we-mega-menu-li')) {
        var type = $(this).val();
        var curCol = self.currentSelected.closest('.we-mega-menu-col');
        var li_count = self.currentSelected.closest('ul').children('li').length;
        var ul = curCol.find('ul > li');
        switch(type) {
          case 'left':
            if (self.currentSelected.closest('.we-mega-menu-row').children('.we-mega-menu-col').length < 12) {
              // Check next col is exists
              if (!curCol.prev().length || (typeof curCol.prev().attr('data-block') != 'undefined' && curCol.prev().attr('data-block').length > 0)) {
                curCol.before(Drupal.WeMegaMenu.colMarkup);
              }

              // Move item to left
              var currentSelectedCounter = self.currentSelected.index();
              var listItems = self.currentSelected.closest('ul').children('li');
              var html = '';
              for (var i = currentSelectedCounter; i >= 0; i--) {
                html = listItems.eq(i)[0].outerHTML + html;
                listItems.eq(i).remove();
              }

              var prev = curCol.prev('.we-mega-menu-col');
              if (typeof curCol.prev().find('ul').length != 'undefined' && curCol.prev().find('ul').length > 0) {
                while (typeof prev.attr('data-block') != 'undefined' && prev.attr('data-block').length > 0) {
                  prev = prev.prev();
                }

                if (typeof prev.find('ul').length != 'undefined' && prev.find('ul').length > 0) {
                  prev.find('ul').append(html);
                } else {
                  prev.prepend('<ul class="nav nav-tabs subul">' + html + '</ul>');  
                }

              } else {
                curCol.prev('.we-mega-menu-col').append('<ul class="nav nav-tabs subul">' + html + '</ul>');
              }
            }
            
            // Rebuild event click
            Drupal.WeMegaMenu.clickActions();
            break;

          case 'right':
            if (self.currentSelected.closest('.we-mega-menu-row').children('.we-mega-menu-col').length < 12) {
              // Check next col is exists
              if (!curCol.next().length || (typeof curCol.next().attr('data-block') != 'undefined' && curCol.next().attr('data-block').length > 0)) {
                curCol.after(Drupal.WeMegaMenu.colMarkup);
              }

              // Move item to right
              var currentSelectedCounter = self.currentSelected.index();
              var listItems = self.currentSelected.closest('ul').children('li');
              var html = '';
              for (var i = li_count - 1; i >= currentSelectedCounter; i--) {
                html = listItems.eq(i)[0].outerHTML + html;
                listItems.eq(i).remove();
              }

              var next = curCol.next('.we-mega-menu-col');
              if (typeof curCol.next().find('ul') != 'undefined' && curCol.next().find('ul').length > 0) {
                while (typeof next.attr('data-block') != 'undefined' && next.attr('data-block').length > 0) {
                  next = next.next();
                }

                if (typeof next.find('ul') != 'undefined' && next.find('ul').length > 0) {
                  next.find('ul').prepend(html);
                } else {
                  next.prepend('<ul class="nav nav-tabs subul">' + html + '</ul>');  
                }
              } else {
                curCol.next('.we-mega-menu-col').prepend('<ul class="nav nav-tabs subul">' + html + '</ul>');
              }
            }

            // Rebuild event click
            Drupal.WeMegaMenu.clickActions();
            break;
        }

        // Rebuild event click
        Drupal.WeMegaMenu.clickActions();
        Drupal.WeMegaMenu.calcColWidthLayout(curCol.closest('.we-mega-menu-row'));
        self.currentSelected.trigger('click');
      }
    });
  };

  Drupal.WeMegaMenu.calcColWidthLayout = function(row) {
    var row_counter = $(row).children('.we-mega-menu-col').length;
    if (row_counter > 12) {
      $.notify({
        icon: 'glyphicon glyphicon-ok-sign',
        message: 'Limit maximum 12 columns',
        target: '_blank'
      },{
        element: 'body',
        position: null,
        type: "warning",
        allow_dismiss: true,
        newest_on_top: false,
        showProgressbar: false,
        placement: {
          from: "top",
          align: "center"
        },
        offset: 300,
        spacing: 10,
        z_index: 1031,
        delay: 1000,
        timer: 1000,
        url_target: '_blank',
        mouse_over: null,
        animate: {
          enter: 'animated fadeInDown',
          exit: 'animated fadeOutUp'
        },
        icon_type: 'class'
      });
    } else {
      var other = 12 % row_counter;
      var sum = 12 - other;
      var unit = sum / row_counter;

      var arr = [];
      for (var i = 0; i < row_counter; i++) {
        if (i == row_counter - 1) {
          arr.push(unit + other);
        } else {
          arr.push(unit);
        }
      }

      $(row).children('.we-mega-menu-col').attr('class', $(row).children('.we-mega-menu-col').attr('class').replace(/\bspan.*?\b/g, ''));
      var class_data = $(row).children('.we-mega-menu-col').attr('class');
      $(row).children('.we-mega-menu-col').attr('class', class_data.replace(/  +/g, ' '));
      $(row).children('.we-mega-menu-col').find('li').removeClass('open');

      $(row).children('.we-mega-menu-col').each(function(k, e) {
        var class_str = 'span' + arr[k];
        $(this).addClass(class_str);
        $(this).attr('data-width', arr[k]);
      });
    };
  };
  
  Drupal.WeMegaMenu.saveConfig = function() {
    $('div.we-mega-menu button.btn-save').once('we-mega-menu-button-btn-save').on('click', function() {
      $('button.btn.btn-success.btn-save').prop('disabled', true);
      var menu = Drupal.WeMegaMenu.menu;
      var menu_name = menu.attr('data-menu-name');
      var theme = menu.attr('data-block-theme');
      var menu_config = {};
      var items = menu.find('ul > li');
      $(items).each(function(k, e) {
        var rows = [];
        var $this = $(this);
        var $submenu = $this.find('div.we-mega-menu-submenu:first');

        var submenu_config = {};
        submenu_config['width'] = typeof $submenu.attr('data-submenu-width') != 'undefined' ? $submenu.attr('data-submenu-width') : '';
        submenu_config['class'] = typeof $submenu.attr('data-class') != 'undefined' ? $submenu.attr('data-class') : '';
        submenu_config['type'] = typeof $submenu.attr('data-element-type') != 'undefined' ? $submenu.attr('data-element-type') : '';

        var item_config = {};
        item_config['level'] = typeof $this.attr('data-level') != 'undefined' ? parseInt($this.attr('data-level')) : 0;
        item_config['type'] = typeof $this.attr('data-element-type') != 'undefined' ? $this.attr('data-element-type') : '';
        item_config['id'] = typeof $this.attr('data-id') != 'undefined' ? $this.attr('data-id') : '';
        item_config['submenu'] = typeof $this.attr('data-submenu') != 'undefined' ? $this.attr('data-submenu') : '';
        item_config['hide_sub_when_collapse'] = typeof $this.attr('hide-sub-when-collapse') != 'undefined' ? $this.attr('hide-sub-when-collapse') : '';
        item_config['group'] = typeof $this.attr('data-group') != 'undefined' ? $this.attr('data-group') : '';
        item_config['class'] = typeof $this.attr('data-class') != 'undefined' ? $this.attr('data-class') : '';
        item_config['data-icon'] = typeof $this.attr('data-icon') != 'undefined' ? $this.attr('data-icon') : '';
        item_config['data-caption'] = typeof $this.attr('data-caption') != 'undefined' ? $this.attr('data-caption') : '';
        item_config['data-alignsub'] = typeof $this.attr('data-alignsub') != 'undefined' ? $this.attr('data-alignsub') : '';
        item_config['data-target'] = typeof $this.attr('data-target') != 'undefined' ? $this.attr('data-target') : '';
        
        var $rows = $submenu.find('[class="we-mega-menu-row"]:first').parent().children('[class="we-mega-menu-row"]');
        $rows.each(function(kk, ee) {
          var cols = [];
          var $cols = $(this).children('.we-mega-menu-col');
          $cols.each(function(k, e) {
            var col_config = {};
            col_config['hidewhencollapse'] = typeof $(e).attr('data-hidewhencollapse') != 'undefined' ? $(e).attr('data-hidewhencollapse') : '';
            col_config['type'] = typeof $(e).attr('data-element-type') != 'undefined' ? $(e).attr('data-element-type') : '';
            col_config['width'] = typeof $(e).attr('data-width') != 'undefined' ? $(e).attr('data-width') : 12;
            col_config['block'] = typeof $(e).attr('data-block') != 'undefined' ? $(e).attr('data-block') : '';
            col_config['class'] = typeof $(e).attr('data-class') != 'undefined' ? $(e).attr('data-class') : '';
            col_config['block_title'] = typeof $(e).attr('data-blocktitle') != 'undefined' ? $(e).attr('data-blocktitle') : 0;

            var col = {};
            col['col_config'] = col_config;

            if ($(e).children('div.type-of-block').length) {
              $(e).children('div.type-of-block').each(function(kk, ee) {
                if (kk == 0) {
                  var element = {};
                  element['block_id'] = $(e).closest('.we-mega-menu-col').attr('data-block');
                  element['type'] = $(e).closest('.we-mega-menu-col').attr('data-type');
                  element['item_config'] = {};
                  col['col_content'] = element;
                }
              });  
            } else {
              var li_list = [];
              var mlid_list = [];
              $(e).find('ul > li:not(.type-of-block)').each(function(k, e) {
                var sub_level = parseInt($(e).attr('data-level'));
                if (sub_level == item_config['level'] + 1) {
                  var element = {};
                  element['mlid'] = typeof $(e).attr('data-id') != 'undefined' ? $(e).attr('data-id') : '';
                  element['type'] = typeof $(e).attr('data-element-type') != 'undefined' ? $(e).attr('data-element-type') : '';
                  element['item_config'] = {};

                  if ($.inArray(element['mlid'], mlid_list) == -1) {
                    li_list.push(element);
                    mlid_list.push(element['mlid']);
                  }
                }
              });
              col['col_content'] = li_list;
            }
            cols.push(col);
          });
          if (cols.length) {
            rows.push(cols);
          }
        });

        var config = {'rows_content': rows, 'submenu_config': submenu_config, 'item_config': item_config};
        var data_id = $(this).attr('data-id');
        if (typeof data_id != 'undefined') {
          menu_config[data_id] = config;
        }
      });

      var block_config = {};
      block_config['style'] = typeof menu.attr('data-style') != 'undefined' ? menu.attr('data-style') : 'Default';
      block_config['animation'] = typeof menu.attr('data-animation') != 'undefined' ? menu.attr('data-animation') : '';
      block_config['delay'] = typeof menu.attr('data-delay') != 'undefined' ? menu.attr('data-delay') : 0;
      block_config['duration'] = typeof menu.attr('data-duration') != 'undefined' ? menu.attr('data-duration') : 0;
      block_config['auto-arrow'] = typeof menu.attr('data-autoarrow') != 'undefined' ? menu.attr('data-autoarrow') : 0;
      block_config['always-show-submenu'] = typeof menu.attr('data-alwayshowsubmenu') != 'undefined' ? menu.attr('data-alwayshowsubmenu') : 0;
      block_config['action'] = typeof menu.attr('data-action') != 'undefined' ? menu.attr('data-action') : 'hover';
      block_config['auto-mobile-collapse'] = typeof menu.attr('data-mobile-collapse') != 'undefined' ? menu.attr('data-mobile-collapse') : 0;

      var data_config = {};
      data_config['menu_config'] = menu_config;
      data_config['block_config'] = block_config;    

      // Call ajax to save
      $.ajax({
        type: "POST",
        url: drupalSettings.WeMegaMenu.saveConfigWeMegaMenuURL,
        data: {
          'action': 'save',
          'menu_name': menu_name,
          'theme': theme,
          'data_config': JSON.stringify(data_config)
        },
        complete: function(msg) {
          $.notify({
            icon: 'glyphicon glyphicon-ok-sign',
            message: 'Save config completed',
            url: 'http://weebpal.com',
            target: '_blank'
          },{
            element: 'body',
            position: null,
            type: "success",
            allow_dismiss: true,
            newest_on_top: false,
            showProgressbar: false,
            placement: {
              from: "top",
              align: "center"
            },
            offset: 300,
            spacing: 10,
            z_index: 1031,
            delay: 300,
            timer: 400,
            url_target: '_blank',
            mouse_over: null,
            animate: {
              enter: 'animated fadeInDown',
              exit: 'animated fadeOutUp'
            },
            onShow: null,
            onShown: null,
            onClose: null,
            onClosed: null,
            icon_type: 'class',
            onClosed: function() {
              $('button.btn.btn-success.btn-save').prop('disabled', false);
            }
          });
        }
      });  
    });
  };

  Drupal.WeMegaMenu.resetConfig = function() {
    $('button.btn.btn-danger.btn-reset').once('button-btn-btn-danger-btn-reset').on('click', function() {
      $('button.btn.btn-danger.btn-reset').prop('disabled', true);
      var menu = Drupal.WeMegaMenu.menu;
      var menu_name = menu.attr('data-menu-name');
      var theme = menu.attr('data-block-theme');
      $.ajax({
        type: "POST",
        url: drupalSettings.WeMegaMenu.resetConfigWeMegaMenuURL,
        data: {
          'action': 'reset',
          'menu_name': menu_name,
          'theme': theme
        },
        complete: function(data) {
          $('nav.navbar-we-mega-menu.admin').replaceWith(data.responseText);
          Drupal.WeMegaMenu.currentSelected = null;
          Drupal.WeMegaMenu.toolbar = $('.we-mega-menu-toolbar');
          Drupal.WeMegaMenu.menu = $('nav.navbar-we-mega-menu');
          Drupal.WeMegaMenu.clickActions();
          $.notify({
            icon: 'glyphicon glyphicon-ok-sign',
            message: 'Reset menu config completed',
            url: 'http://weebpal.com',
            target: '_blank'
          },{
            element: 'body',
            position: null,
            type: "success",
            allow_dismiss: true,
            newest_on_top: false,
            showProgressbar: false,
            placement: {
              from: "top",
              align: "center"
            },
            offset: 300,
            spacing: 10,
            z_index: 1031,
            delay: 1000,
            timer: 1000,
            url_target: '_blank',
            mouse_over: null,
            animate: {
              enter: 'animated fadeInDown',
              exit: 'animated fadeOutUp'
            },
            onShow: null,
            onShown: null,
            onClose: null,
            onClosed: null,
            icon_type: 'class',
            onClosed: function() {
              $('button.btn.btn-danger.btn-reset').prop('disabled', false);
            }
          });
        }
      });
    });

    $('button.btn.btn-danger.btn-reset-to-default').once('button-btn-btn-danger-btn-reset-to-default').on('click', function() {
      $('button.btn.btn-danger.btn-reset-to-default').prop('disabled', true);
      var menu = Drupal.WeMegaMenu.menu;
      var menu_name = menu.attr('data-menu-name');
      var theme = menu.attr('data-block-theme');
      $.ajax({
        type: "POST",
        url: drupalSettings.WeMegaMenu.resetConfigWeMegaMenuURL,
        data: {
          'action': 'reset-to-default',
          'menu_name': menu_name,
          'theme': theme
        },
        complete: function(data) {
          $('#resetToDefault').modal('toggle');
          $('nav.navbar-we-mega-menu.admin').replaceWith(data.responseText);
          Drupal.WeMegaMenu.currentSelected = null;
          Drupal.WeMegaMenu.toolbar = $('.we-mega-menu-toolbar');
          Drupal.WeMegaMenu.menu = $('nav.navbar-we-mega-menu');
          Drupal.WeMegaMenu.clickActions();
          $.notify({
            icon: 'glyphicon glyphicon-ok-sign',
            message: 'Reset menu config completed',
            url: 'http://weebpal.com',
            target: '_blank'
          },{
            element: 'body',
            position: null,
            type: "success",
            allow_dismiss: true,
            newest_on_top: false,
            showProgressbar: false,
            placement: {
              from: "top",
              align: "center"
            },
            offset: 300,
            spacing: 10,
            z_index: 1031,
            delay: 1000,
            timer: 1000,
            url_target: '_blank',
            mouse_over: null,
            animate: {
              enter: 'animated fadeInDown',
              exit: 'animated fadeOutUp'
            },
            onShow: null,
            onShown: null,
            onClose: null,
            onClosed: null,
            icon_type: 'class',
            onClosed: function() {
              $('button.btn.btn-danger.btn-reset-to-default').prop('disabled', false);
            }
          });
        }
      });
    });
  };

  Drupal.WeMegaMenu.chosenConfig = function() {
    self.toolbar.find('select').chosen();
  };

  Drupal.WeMegaMenu.autocompleteIcons = function() {
    $('input.we-mega-menu-txt-icon').autocomplete({
      source: function(request, response) {
        var results = $.ui.autocomplete.filter(Drupal.WeMegaMenuIcon, request.term);
        response(results.slice(0, 10));
      },
      select: function( event, ui ) {
        setTimeout(function() {
          $('input.we-mega-menu-txt-icon').trigger('change');
        }, 100);
      }
    });
  };

  Drupal.WeMegaMenu.backendStyle = function() {
    $('body.we-mega-menu-backend .we-mega-menu .ico-toolbar.ico-toolbar-horizontal').once('we-mega-menu-backend-toolbar-horizontal').on('click', function() {
      var class_data = '';
      if ($('body').hasClass('we-mega-menu-toolbar-horizontal')) {
        $('body').removeClass('we-mega-menu-toolbar-horizontal');
      } else {
        $('body').addClass('we-mega-menu-toolbar-horizontal');
        class_data = 'we-mega-menu-toolbar-horizontal';
      }

      $.ajax({
        type: "POST",
        url: drupalSettings.path.baseUrl + 'admin/structure/we-mega-menu/style',
        data: {
          type: class_data
        },
        success: function(response) {
        }
      });
    });
  };

})(jQuery, Drupal, drupalSettings);