(function ($, Drupal) {
  Drupal.SecurityGroup = Drupal.SecurityGroup || {};

  Drupal.SecurityGroup.autoPopulate = {
    populateAllPorts: function(protocol, field, key) {
      if (protocol == '-1') {
        $('.form-item-' + field + '-' + key + '-from-port input').val("0");
        $('.form-item-' + field + '-' + key + '-to-port input').val("65535");
      }
    },
  };

  Drupal.SecurityGroup.showHide = {
    showIp4: function (field, key) {
      // Hide cidr_ipv6 and group.
      $('.form-item-' + field + '-' + key + '-cidr-ip-v6').hide();
      // Hide group attributes.
      $('.form-item-' + field + '-' + key + '-group-id').hide();
      $('.form-item-' + field + '-' + key + '-group-name').hide();
      $('.form-item-' + field + '-' + key + '-peering-status').hide();
      $('.form-item-' + field + '-' + key + '-user-id').hide();
      $('.form-item-' + field + '-' + key + '-vpc-id').hide();
      $('.form-item-' + field + '-' + key + '-peering-connection-id').hide();

      $('.form-item-' + field + '-' + key + '-cidr-ip').show();
    },
    showIp6: function (field, key) {
      // Hide cidr ip field.
      $('.form-item-' + field + '-' + key + '-cidr-ip').hide();
      // Hide group attributes.
      $('.form-item-' + field + '-' + key + '-group-id').hide();
      $('.form-item-' + field + '-' + key + '-group-name').hide();
      $('.form-item-' + field + '-' + key + '-peering-status').hide();
      $('.form-item-' + field + '-' + key + '-user-id').hide();
      $('.form-item-' + field + '-' + key + '-vpc-id').hide();
      $('.form-item-' + field + '-' + key + '-peering-connection-id').hide();

      // Show ip6.
      $('.form-item-' + field + '-' + key + '-cidr-ip-v6').show();
    },
    showGroup: function (field, key) {
      // Hide cidr and cidr_ipv6
      $('.form-item-' + field + '-' + key + '-cidr-ip').hide();
      $('.form-item-' + field + '-' + key + '-cidr-ip-v6').hide();

      // Show group attributes.
      $('.form-item-' + field + '-' + key + '-group-id').show();
      $('.form-item-' + field + '-' + key + '-group-name').show();
      $('.form-item-' + field + '-' + key + '-peering-status').show();
      $('.form-item-' + field + '-' + key + '-user-id').show();
      $('.form-item-' + field + '-' + key + '-vpc-id').show();
      $('.form-item-' + field + '-' + key + '-peering-connection-id').show();
    },
    hideRow: function (field, row_count, table_id) {
      var from_port = $('.' + table_id + ' tr.row-' + row_count + ' .form-item-' + field + '-' + row_count + '-from-port input').val();
      var to_port = $('.' + table_id + ' tr.row-' + row_count + ' .form-item-' + field + '-' + row_count + '-to-port input').val();

      // If these fields are blank, they qualify as an empty row. Remove them
      if (from_port !== '' && to_port !== '') {
        $('.' + table_id + ' tr.row-' + row_count + ' .form-item-' + field + '-' + row_count + '-from-port input').val('');
        $('.' + table_id + ' tr.row-' + row_count + ' .form-item-' + field + '-' + row_count + '-to-port input').val('');
        $('.' + table_id + ' tr.row-' + row_count).addClass('hide');

        // Display a message telling user to save the page before the rule change is applied.
        if (!$('.messages--warning').length) {
          $('<div class="messages messages--warning" role="alert" style=""><abbr class="warning">*</abbr> Click "Save" to apply rule changes.</div>').prependTo('#edit-rules .details-wrapper');
        }
      }
    },
    showFields: function (type, field, key) {
      if (type == 'ip4') {
        Drupal.SecurityGroup.showHide.showIp4(field, key);
      }
      else if (type == 'ip6') {
        Drupal.SecurityGroup.showHide.showIp6(field, key);
      }
      else {
        Drupal.SecurityGroup.showHide.showGroup(field, key);
      }
    }
  };

  Drupal.behaviors.securityPermissions = {
    attach: function (context, settings) {
      // Initialize inbound permissions.
      $.each($('.field--name-ip-permission .ip-permission-select', context), function (k, el) {
        // Initialize default behavior.
        Drupal.SecurityGroup.showHide.showFields($(this).val(), 'ip-permission', k);

        $(el).change(function () {
          Drupal.SecurityGroup.showHide.showFields($(this).val(), 'ip-permission', k);
        })
      });

      // Initialize outbound permissions.
      $.each($('.field--name-outbound-permission .ip-permission-select', context), function (k, el) {
        // Initialize default behavior.
        Drupal.SecurityGroup.showHide.showFields($(this).val(), 'outbound-permission', k);

        $(el).change(function () {
          Drupal.SecurityGroup.showHide.showFields($(this).val(), 'outbound-permission', k);
        })
      });


      $.each($('.field--name-ip-permission .ip-protocol-select', context), function (k, el) {
        $(el).change(function() {
          // Populate from-to ports for "all traffic" option.
          Drupal.SecurityGroup.autoPopulate.populateAllPorts($(this).val(), 'ip-permission', k);
        });
      });

      $.each($('.field--name-outbound-permission .ip-protocol-select', context), function (k, el) {
        $(el).change(function() {
          // Populate from-to ports for "all traffic" option.
          Drupal.SecurityGroup.autoPopulate.populateAllPorts($(this).val(), 'outbound-permission', k);
        });
      });

      // When the link is clicked, clear out the to and from port.
      // Hide the row, and add a message for the user.
      $.each($('.ip-permission-values .remove-rule', context), function (k, el) {
        $(el).click(function() {
          var row_count = $(this).attr('data-row');
          var table_id = $(this).attr('data-table-id');
          Drupal.SecurityGroup.showHide.hideRow('ip-permission', row_count, table_id);
        });
      });

      $.each($('.outbound-permission-values .remove-rule', context), function (k, el) {
        $(el).click(function() {
          var row_count = $(this).attr('data-row');
          var table_id = $(this).attr('data-table-id');
          Drupal.SecurityGroup.showHide.hideRow('outbound-permission', row_count, table_id);
        });
      });

    }
  };
})(jQuery, Drupal);
