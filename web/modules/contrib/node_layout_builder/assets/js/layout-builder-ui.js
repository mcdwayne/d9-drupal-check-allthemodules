/**
 * @file
 * File  layout-builder-ui.js.
 *
 * Event handler on layout builder editor.
 */

(function ($, Drupal, drupalSettings) {

  Drupal.behaviors.node_layout_builder = {
    attach: function (context, settings) {
      let data = JSON.stringify(drupalSettings.node_layout_builder.data);
      if ($('.add-templates').length > 0) {
        $('.add-templates').css('display', 'none');
      }

      // Show/hide button add section.
      $(".nlb-wrapper section").on({
        mouseenter: function () {
          $(this).find(".btn-add-section:eq(0)").css("opacity", 1);
        }, mouseleave: function () {
          $(this).find(".btn-add-section:eq(0)").css("opacity", 0);
        }
      });

      // Show / hide element bar button action.
      $(".element").on({
        mouseenter: function () {
          $(this).find(".nbl-bar-buttons-action:eq(0)").css("display", "block");
        }, mouseleave: function () {
          $(this).find(".nbl-bar-buttons-action:eq(0)").css("display", "none");
        }
      });

      // Hightlight / background color of element selected.
      $(".nbl-bar-buttons-action").on({
        mouseenter: function () {
          const type = $(this).attr('data-type');
          let bgColor = 'transparent';

          switch (type) {
            case 'section':
              bgColor = '#ffffe0';
              break;

            case 'row':
              bgColor = '#e4f5ff';
              break;

            case 'column':
              bgColor = '#f7e1e1';
              break;

            default:
              bgColor = '#FDDADA';
              break;
          }
          $(this).parent().addClass('hover-element-' + type);
        }, mouseleave: function () {
          const type = $(this).attr('data-type');
          $(this).parent().removeClass('hover-element-' + type);
        }
      });

      nlb_ui_init(context, settings);
    }
  };

  /**
   *
   * @param context
   * @param settings
   */
  function nlb_ui_init(context, settings) {
    // Get Data and id entity.
    const nid = drupalSettings.node_layout_builder.nid;

    $('.nlb-wrapper', context).each(function () {

      // Drag drop section.
      $('.nlb-wrapper').sortable({
        forcePlaceholderSize: true,
        handle: '.icon-move',
        placeholder: "ui-state-highlight",
        distance: 0.5,
        cursor: 'move',
        helper: "clone",
        tolerance: 'intersect',
        forceHelperSize: false,
        items: 'section',
        start: function (event, ui) {
        },
        update: function (event, ui) {
        },
        stop: function (event, ui) {
          nlb_data_sortable(ui, nid);
        }
      });

      // Drag drop row.
      $('.section .container-fluid').sortable({
        forcePlaceholderSize: true,
        handle: '.icon-move',
        placeholder: "ui-state-highlight",
        distance: 0.5,
        cursor: 'move',
        items: '.element.row',
        start: function () {
        },
        over: function (event, ui) {
        },
        update: function (event, ui) {
        },
        stop: function (event, ui) {
          nlb_data_sortable(ui, nid);
        }
      });

      // Drag drop columns.
      $('.element.row').sortable({
        forcePlaceholderSize: true,
        handle: '.icon-move',
        placeholder: "ui-state-highlight",
        cursor: 'move',
        items: '.element.column',
        connectWith: ".element.row",
        start: function () {
        },
        over: function (event, ui) {
          ui.placeholder.css({maxWidth: ui.item.width()});
          ui.placeholder.css({height: ui.item.height()});
          ui.placeholder.css({float: 'left'});
        },
        update: function (event, ui) {
        },
        stop: function (event, ui) {
          nlb_data_sortable(ui, nid);
        }
      });

      // Drag drop other element children.
      $('.element.column').sortable({
        forcePlaceholderSize: true,
        handle: '.icon-move',
        placeholder: "ui-state-highlight",
        cursor: 'move',
        items: '.element',
        connectWith: ".element.column",
        start: function () {
        },
        over: function (event, ui) {
          ui.placeholder.css({maxWidth: ui.item.width()});
          ui.placeholder.css({height: ui.item.height()});
          ui.placeholder.css({float: 'left'});
        },
        update: function (event, ui) {
        },
        stop: function (event, ui) {
          nlb_data_sortable(ui, nid);
        }
      });

      $('.nlb-save-data').on('click', function (e) {
        e.preventDefault();
        nlb_data_save(nid);
      });
    });
  }

  /**
   * Update order element in data node entity.
   *
   * @param ui
   * @param nid
   */
  function nlb_data_sortable(ui, nid) {
    const type = ui.item.attr('data-type');
    const index = $(ui.item).index();
    const from = ui.item.attr('id');
    let to = ui.item.parent().attr('id');

    if (type === 'nlb_row') {
      to = ui.item.parent().parent().attr('id');
    }

    $.ajax({
      type: 'POST',
      url: '/node-layout-builder/sortable/' + nid + '/' + from + '/' + to + '/' + index
    }).done(function (res) {
      toastr.success('Change position');
    });

  }

  /**
   * Save data.
   *
   * @param nid
   */
  function nlb_data_save(nid) {
    $.ajax({
      type: 'GET',
      url: '/node-layout-builder/element/save/' + nid
    }).done(function (res) {
      if (res.msg) {
        toastr.success(res.msg);
      }
    });
  }

})(jQuery, Drupal, drupalSettings);
