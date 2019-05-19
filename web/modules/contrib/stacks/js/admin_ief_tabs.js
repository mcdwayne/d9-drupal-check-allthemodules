(function ($, Drupal, drupalSettings) {

  var _clist_bundles = [];
  var clist_cancelButtonHit = false;
  var clist_selectedPanel;
  var clist_tabChange = false;
  var clist_adding = false;
  var clist_saving = false;
  var _clist_reference_field = "";

  function ief_dropdown_change(bundle) {
    var $select = $('select[data-drupal-selector="edit-inline-entity-form-' + _clist_reference_field + '-actions-bundle"]');
    if ($select.length) {
      $select.val(bundle);
      if (clist_tabChange == true) {
        clist_tabChange = false;
        $('input[data-drupal-selector="edit-inline-entity-form-' + _clist_reference_field + '-actions-ief-add"]').trigger('mousedown');
      }
      else {
        $('#tabs-clist-bundles').hide();
        $('#clist-add-content').show();
      }
    }
  }

  $(document).ajaxSuccess(function (event, xhr, settings) {
    var $operations = $('.type_ief_tabs .ief-entity-operations'),
      $clist = $('#clist-add-content');
    if (clist_saving == true) {
      clist_saving = false;
      $clist.show();
    }

    if (clist_cancelButtonHit == true) {
      clist_cancelButtonHit = false;
      ief_dropdown_change(clist_selectedPanel);
      return;
    }

    if (clist_adding == true) {
      clist_adding = false;
      var el = $('fieldset[data-drupal-selector="edit-inline-entity-form-' + _clist_reference_field + '-form"]');
      if (el.length > 0) {
        var _posTopVar;
        if ($('fieldset[data-drupal-selector="edit-inline-entity-form-' + _clist_reference_field + '"] tr.ief-row-entity').length == 0) {
          _posTopVar = 16;
        }
        else {
          _posTopVar = 50;
        }

        $('#tabs-clist-bundles').css("top", parseInt(el.position().top, 10) + _posTopVar + "px").show();
      }
    }

    if ($clist.length && $clist.is(":visible")) {
      $operations.show();
    }
    else {
      $operations.hide();
    }

  });

  Drupal.behaviors.stacks_clist_tabs = {
    attach: function (context, settings) {
      if (!drupalSettings.stacks_clist_settings.reference_field_name) return;
      _clist_reference_field = drupalSettings.stacks_clist_settings.reference_field_name;

      var $tabs_bundles = $('input[data-drupal-selector="edit-ief-tabs-bundles"]'),
        $actions_ief_add = $('input[data-drupal-selector="edit-inline-entity-form-' + _clist_reference_field + '-actions-ief-add"]');
      // Update the submit for adding content on content lists.
      $('.ief-entity-submit').val('Add Content');

      _clist_bundles = [];

      if ($tabs_bundles.length) {
        // drupalSettings are always merged. So we use a hidden field instead.
        var _bundles = $.parseJSON($tabs_bundles.val());

        for (var key in _bundles) {
          var o = {};
          o.value = key;
          o.text = _bundles[key];
          _clist_bundles.push(o);
        }
      }

      $('select[data-drupal-selector="edit-inline-entity-form-' + _clist_reference_field + '-actions-bundle"]').hide();
      $('div.form-item-inline-entity-form-' + _clist_reference_field + '-actions-bundle > .chosen-container').remove();

      $('fieldset[data-drupal-selector="edit-inline-entity-form-' + _clist_reference_field + '"] legend').hide();
      $actions_ief_add.hide();
      $('div[data-drupal-selector="edit-inline-entity-form-' + _clist_reference_field + '-wrapper"]').once('add_button_append', context).prepend($('<div>', {
        class: 'contentlist-add-button-wrapper',
        html: $('<input>', {
          type: 'submit',
          class: 'button js-form-submit form-submit',
          value: Drupal.t('Add new content'),
          id: 'contentlist-add-content',
        })
      }));

      $('#clist-add-content').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $actions_ief_add.trigger('mousedown');
      });

      $('input[data-drupal-selector="edit-inline-entity-form-' + _clist_reference_field + '-form-inline-entity-form-actions-ief-add-save"]').on('mousedown', function () {
        clist_saving = true;
      });

      // Put together the html for the tabs.
      // _clist_bundles.length > 1 ----> Normal behavior: If there's only 1 bundle, do not show tabs
      // _clist_bundles.length > 0 ----> Show tabs even if there's only 1 bundle. The rest of the script is not prepared for this
      if (_clist_bundles.length > 1) {
        $actions_ief_add.on('mousedown', function () {
          clist_adding = true;
          $('#clist-add-content').hide();
        });

        $('input[data-drupal-selector="edit-inline-entity-form-' + _clist_reference_field + '-form-inline-entity-form-actions-ief-add-save"]').on('mousedown', function () {
          $('#tabs-clist-bundles').hide();
        });

        $('input[data-drupal-selector="edit-inline-entity-form-' + _clist_reference_field + '-form-inline-entity-form-actions-ief-add-cancel"]').on('mousedown', function () {
          clist_cancelButtonHit = true;
        });

        $('div[data-drupal-selector="edit-inline-entity-form"]').once('createtabs').each(function () {
          if ($('#tabs-clist-bundles').length == 0) {

            var html = '<div id="tabs-clist-bundles" style="display: none; "><ul>';
            var html_tabs = '';
            clist_selectedPanel = '';
            for (var i = 0; i < _clist_bundles.length; i++) {
              if (clist_selectedPanel == '') clist_selectedPanel = _clist_bundles[i].value;
              html += '<li><a href="#' + _clist_bundles[i].value + '">' + _clist_bundles[i].text + '</a></li>';
              html_tabs += '<div id="' + _clist_bundles[i].value + '"></div>'; // Empty tab required by jQuery UI tabs
            }
            html += '</ul>' + html_tabs;

            $('div[data-drupal-selector="edit-inline-entity-form-' + _clist_reference_field + '-wrapper"]').append(html);

            $('#tabs-clist-bundles', context).once('tabs_clist').tabs();

            $('#tabs-clist-bundles').on('tabsactivate', function (event, ui) {
              clist_selectedPanel = ui.newPanel.attr('id');
              clist_cancelButtonHit = true;
              clist_tabChange = true;
              $('input[data-drupal-selector="edit-inline-entity-form-' + _clist_reference_field + '-form-inline-entity-form-actions-ief-add-cancel"]').trigger('mousedown');
            });
          }
        }); // end createtabs
      }
    }
  };

})(jQuery, Drupal, drupalSettings);
