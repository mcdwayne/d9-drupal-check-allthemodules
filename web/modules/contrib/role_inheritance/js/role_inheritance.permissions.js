/**
 * @file
 * User permission page behaviors.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Construct html id from permission checkboxes.
   *
   * Ids are built from the role and permission ids, prefiexed by "edit".
   */
  function buildId(role, permission) {
    var role_css = role.replace(/_/g, "-");
    var permission_css = permission.replace(/[_ ]/g, "-");
    var id_string = "#edit-" + role_css + "-" + permission_css;
    return id_string;
  };

  /**
   * Update the display state of the field to reflect inherited permissions.
   *
   * If the permission is inherited from one or more roles, then the checkbox
   * should be replaced with the dummy checkbox and the label should be set.
   * Otherwise, only the original checkbox should be visible.
   */
  function setState() {
    var inheritedPermissions = drupalSettings.role_inheritance.inherited;

    var checkbox = this;
    var $checkbox = $(checkbox);
    var role = $checkbox.data("ri-role");
    var permission = $checkbox.data("ri-permission");

    var providers = [];
    if (role in inheritedPermissions && permission in inheritedPermissions[role]) {
      providers = inheritedPermissions[role][permission];
    }
    var title = "";
    var inherited = true;
    if (providers.length <= 0) {
      // This permission is not inherited from any roles.
      inherited = false;
    } else {
      // This permission is inherited from 1+ roles.
      inherited = true;
      title = "This permission is inherited from ";
      if (providers.length == 1) {
        title += providers[0];
      } else {
        var last = providers.pop();
        title += providers.join(", ") + " and " + last;
        providers.push(last);
      }
    }

    // Only disable the checkbox if its not checked.
    var disabled = inherited && !checkbox.checked;

    // jQuery performs too many layout calculations for .hide() and .show(),
    // leading to a major page rendering lag on sites with many roles and
    // permissions. Therefore, we toggle visibility directly.
    checkbox.style.display = (disabled) ? 'none' : '';
    $checkbox.siblings(".js-ri-inherited").each(function () {
      this.style.display = (disabled) ? '' : 'none';
      this.title = title;
    });
  };

  /**
   * Shows checked and disabled checkboxes for inherited permissions.
   *
   * This replaces and extends functionality from the core uses module.
   *
   * @see user.permissions.js
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches functionality to the permissions table.
   */
  Drupal.behaviors.ri_permissions = {
    attach: function (context) {
      var self = this;
      $('table#permissions').once('ri_permissions').each(function () {
        // On a site with many roles and permissions, this behavior initially
        // has to perform thousands of DOM manipulations to inject checkboxes
        // and hide them. By detaching the table from the DOM, all operations
        // can be performed without triggering internal layout and re-rendering
        // processes in the browser.
        var $table = $(this);
        var $ancestor;
        var method;
        if ($table.prev().length) {
          $ancestor = $table.prev();
          method = 'after';
        }
        else if ($table.next().length) {
          $ancestor = $table.next();
          method = 'before';
        }
        else {
          $ancestor = $table.parent();
          method = 'append';
        }
        $table.detach();

        // Create dummy checkboxes. We use dummy checkboxes instead of reusing
        // the existing checkboxes here because new checkboxes don't alter the
        // submitted form. If we'd automatically check existing checkboxes, the
        // permission table would be polluted with redundant entries. This
        // is deliberate, but desirable when we automatically check them.
        var $dummy = $('<input type="checkbox" class="ri-inherited js-ri-inherited" disabled="disabled" checked="checked" />')
          .hide();

        $table
          .find('input[type="checkbox"]')
          .addClass('ri-original js-ri-original')
          .after($dummy)
          .each(function() {
            var open_pos = this.name.indexOf('[');
            var close_pos = this.name.indexOf(']',open_pos);
            var role_id = this.name.substring(0,open_pos);
            var permission = this.name.substring(open_pos+1,close_pos);

            // Set data attributes for role and permission ids.
            this.setAttribute("data-ri-role", role_id);
            this.setAttribute("data-ri-permission", permission);
          })
          .on('click.permissions', self.toggle)
          .each(setState);

        // Re-insert the table into the DOM.
        $ancestor[method]($table);
      });
    },

    /**
     * Call back for onclick to update inheritance.
     *
     * When a checkbox is changed, the inheritance mapping gets updated and
     * relevant checkboxes get updated to reflect the change. Each inherited
     * permission checkbox gets passed to setState so it can be updated.
     */
    toggle: function () {
      var inheritedPermissions = drupalSettings.role_inheritance.inherited;
      var providerMap = drupalSettings.role_inheritance.providers;

      var $clickedCheckbox = $(this);
      var $row = $clickedCheckbox.closest('tr');

      var isChecked = this.checked;

      var role = $clickedCheckbox.data("ri-role");
      if (role in providerMap) {
        var permission = $clickedCheckbox.data("ri-permission");
        for (var i = 0; i < providerMap[role].length; i++ ) {
          var inherited = providerMap[role][i];

          // Add array property if it does not exist.
          if (!(inherited in inheritedPermissions)) {
            inheritedPermissions[inherited] = {};
          }
          if (!(permission in inheritedPermissions[inherited])) {
            inheritedPermissions[inherited][permission] = [];
          }

          // Add or remove this role from the inheritance map.
          if (isChecked && inheritedPermissions[inherited][permission].indexOf(role) < 0) {
            inheritedPermissions[inherited][permission].push(role);
          } else {
            inheritedPermissions[inherited][permission].splice(inheritedPermissions[inherited][permission].indexOf(role), 1);
          }

          // Update all the inherited fields.
          $row.find(buildId(inherited, permission)).each(setState);
        }
      }

    }
  };

})(jQuery, Drupal);
