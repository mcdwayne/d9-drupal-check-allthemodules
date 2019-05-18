/**
 * @file
 * Role inheritance mapping page behaviors.
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
  }

  /**
   * Update direct mapping and rebuild effected data.
   *
   * Update the role_inheritance.map and role_inheritance.providers maps, with
   * the change, and rebuild the effected portions of the collapsed mapping.
   * The mapping changes will be carried over to the UI.
   */
  function locallyUpdateMap(role, inherited, remove) {
    if (remove === undefined) {
      remove = false;
    }
    var roleMapping = drupalSettings.role_inheritance.map;
    var providerMap = drupalSettings.role_inheritance.providers;
    var roleMappingCollapsed = drupalSettings.role_inheritance.map_collapsed;
    var providerMapCollapsed = drupalSettings.role_inheritance.providers_collapsed;

    if (!(role in roleMapping)) {
      roleMapping[role] = [];
    }
    if (!(inherited in providerMap)) {
      providerMap[inherited] = [];
    }

    // Update map if inherited is in the map for role.
    var mapIndex = $.inArray(inherited, roleMapping[role]);
    if (remove && mapIndex >= 0) {
      // Remove if in list.
      roleMapping[role].splice(mapIndex, 1);
    } else if (!remove && mapIndex === -1) {
      // Add if not in list.
      roleMapping[role].push(inherited);
    }

    // Update the provider mapping with the change
    var providerIndex = $.inArray(role, providerMap[inherited]);
    if (remove && providerIndex >= 0) {
      providerMap[inherited].splice(providerIndex, 1);
    } else if (!remove && providerIndex === -1) {
      providerMap[inherited].push(role);
    }

    // Inheritance mapping for roles inherted by `role` need to be rebuilt.
    // Invalidate inheritance mapping for `role` and effected roles.
    delete roleMappingCollapsed[role];
    $.each(
      providerMapCollapsed[role],
      function (ind, providedTo) {
        delete roleMappingCollapsed[providedTo];
      });
    // Rebuild mapping for effected roles.
    _role_inheritance_role_map_collpase(roleMapping, roleMappingCollapsed, role);
    $.each(
      providerMapCollapsed[role],
      function (ind, providedTo) {
        _role_inheritance_role_map_collpase(roleMapping, roleMappingCollapsed, providedTo);
        $("td.js-rid-" + providedTo).closest("tr").find("input[type=checkbox]:not(.js-ri-disabled)").each(setState);
      });

    // Provider mapping for roles inherted by `inherited` need to be rebuilt.
    // Invalidate provider mapping for `inherited` and effected roles.
    delete providerMapCollapsed[inherited];
    $.each(
      roleMappingCollapsed[inherited],
      function (ind, providedTo) {
        delete providerMapCollapsed[providedTo];
      });
    // Rebuild collapsed mapping for effected roles.
    _role_inheritance_role_map_collpase(providerMap, providerMapCollapsed, inherited);
    $.each(
      roleMappingCollapsed[inherited],
      function (ind, providedBy) {
        _role_inheritance_role_map_collpase(providerMap, providerMapCollapsed, providedBy);
      });
  }

  /**
   * Collapse role mapping to show all inherited.
   *
   * @see php function _role_inheritance_role_map_collpase().
   */
  function _role_inheritance_role_map_collpase(map, collapse, role) {
    if (collapse === undefined) {
      collapse = {};
    }
    if (role !== undefined) {
      // If we are building the mapping for a role, clear the data.
      collapse[role] = [];
      if (role in map) {
        // copy data from the map.
        for (var r in map[role]) {
          collapse[role].push(map[role][r]);
        }
      }

      // Copy any inherited data.
      for (var id in map[role]) {
        var inherit = map[role][id];
        if (inherit in map) {
          if (!(inherit in collapse)) {
            // If collpased mapping for inherted role does not exist, build it.
            collapse = _role_inheritance_role_map_collpase(map, collapse, inherit);
          }
          // Copy collapsed role mapping for inherited roles.
          for (var ind in collapse[inherit]) {
            var ir = collapse[inherit][ind];
            if ($.inArray(ir, collapse[role]) == -1) {
              collapse[role].push(ir);
            }
          }
        }
      }
    } else {
      // If we are not rebuilding for a sepcific role, rebuild for all roles.
      $.each(
        map,
        function (id, inherit) {
          if (!(id in collapse)) {
            collapse = _role_inheritance_role_map_collpase(map, collapse, id);
          }
        });
    }
    return collapse;
  }

  /**
   * Check inheritance state and managed inherited class.
   */
  function setState() {
    var checkbox = this;
    var $checkbox = $(checkbox);

    // Determine new state.
    var role = $checkbox.data("ri-role");
    var inherited = $checkbox.data("ri-inherits");

    var mapping = drupalSettings.role_inheritance.map;
    var mapping_collapsed = drupalSettings.role_inheritance.map_collapsed;

    if ($.inArray(inherited, mapping[role]) < 0 && $.inArray(inherited, mapping_collapsed[role]) >= 0) {
      $checkbox.addClass("js-ri-inherited");
    } else {
      $checkbox.removeClass("js-ri-inherited");
    }

    $checkbox.each(updateDisplay);
  }

  /**
   * Update the display state of the field to reflect inherited permissions.
   *
   * If the permission is inherited from one or more roles, then the checkbox
   * should be replaced with the dummy checkbox and the label should be set.
   * Otherwise, only the original checkbox should be visible.
   */
  function updateDisplay() {
    var checkbox = this;
    var $checkbox = $(checkbox);

    var isInherited = false;
    if ($checkbox.hasClass("js-ri-inherited")) {
      isInherited = true;
    }
    checkbox.style.display = (isInherited) ? 'none' : '';
    console.log($checkbox.siblings(".js-ri-disabled"));
    $checkbox.siblings(".js-ri-disabled").each(function () {
      this.style.display = (isInherited) ? '' : 'none';
    });
  }

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
  Drupal.behaviors.ri_mappping = {
    attach: function (context) {
      var self = this;
      $('table#role-inheritance-all').once('ri_mappping').each(function () {
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
        var $dummy = $('<input type="checkbox"/>')
          .addClass("ri-disabled")
          .addClass("js-ri-disabled")
          .attr('title', 'This Role is indirectly inherited.')
          .attr("disabled", true)
          .attr("checked", true)
          .hide();

        $table
          .find('input[type="checkbox"]')
          .addClass('ri-original js-ri-original')
          .after($dummy)
          .on('click', self.toggle)
          .filter(".js-ri-inherited")
          .each(updateDisplay);

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
      var isChecked = this.checked;
      var $clickedCheckbox = $(this);

      var role = $clickedCheckbox.data("ri-role");
      var inherited = $clickedCheckbox.data("ri-inherits");

      locallyUpdateMap(role, inherited, !isChecked);

      $clickedCheckbox.closest("tr").find("input[type=checkbox]:not(.js-ri-disabled)").each(setState);
    }
  };

})(jQuery, Drupal);
