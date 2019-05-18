(function ($) {
  var field_subnet_default_values = drupalSettings.aws_cloud.field_subnet_default_values;
  $("select[name=field_subnet] option").each(function() {
    if (!field_subnet_default_values.includes($(this).val())) {
      $(this).remove();
    }
  });

  var field_security_group_default_values = drupalSettings.aws_cloud.field_security_group_default_values;
  $("select[name='field_security_group[]'] option").each(function() {
    if (!field_security_group_default_values.includes($(this).val())) {
      $(this).remove();
    }
  });

  // Remove "- Select a value -" option if there is only one ssh key.
  var field_ssh_key_options = $("select[name=field_ssh_key] option");
  if (field_ssh_key_options.get().length == 2) {
    field_ssh_key_options.each(function() {
      if ($(this).val() == '_none') {
        $(this).remove();
      }
    });
  }

  // Don't display cost information in the dropdown of instance type,
  // if the configuration is false.
  if (!drupalSettings.aws_cloud.aws_cloud_instance_type_cost) {
    return;
  }

  // Select2 custom results adapter.
  $.fn.select2.amd.define("CustomResultsAdapter", [
    "select2/utils",
    "select2/results",
  ],
  function(Utils, Results) {
    function CustomResultsAdapter ($element, options, dataAdapter) {
      this.$element = $element;
      this.data = dataAdapter;
      this.options = options;

      CustomResultsAdapter.__super__.constructor.call(this, $element, options, dataAdapter);
    }

    Utils.Extend(CustomResultsAdapter, Results);

    CustomResultsAdapter.prototype.render = function() {
      var $results = $(
        '<tbody class="select2-results__options" role="tree"></tbody>'
      );

      if (this.options.get('multiple')) {
        $results.attr('aria-multiselectable', 'true');
      }

      this.$results = $results;

      return $results;
    };

    CustomResultsAdapter.prototype.option = function (data) {
      let option = document.createElement('tr');

      option.className = 'select2-results__option';
      var attrs = {
        'role': 'treeitem',
        'aria-selected': 'false'
      };

      if (data.disabled) {
        delete attrs['aria-selected'];
        attrs['aria-disabled'] = 'true';
      }

      if (data.id == null) {
        delete attrs['aria-selected'];
      }

      if (data._resultId != null) {
        option.id = data._resultId;
      }

      if (data.title) {
        option.title = data.title;
      }

      if (data.children) {
        attrs.role = 'group';
        attrs['aria-label'] = data.text;
        delete attrs['aria-selected'];
      }

      for (var attr in attrs) {
        var val = attrs[attr];
        option.setAttribute(attr, val);
      }

      this.template(data, option);
      $.data(option, 'data', data);
      return option;
    };

    return CustomResultsAdapter;
  });

  // Select2 custom dropdown adapter.
  $.fn.select2.amd.define("CustomDropdownAdapter", [
    "select2/utils",
    "select2/dropdown",
    "select2/dropdown/attachBody",
    "select2/dropdown/attachContainer",
    "select2/dropdown/search",
  ],
  function(Utils, Dropdown, AttachBody, AttachContainer, Search) {
    function CustomDropdownAdapter ($element, options) {
      this.$element = $element;
      this.options = options;

      CustomDropdownAdapter.__super__.constructor.call(this, $element, options);
    }

    Utils.Extend(CustomDropdownAdapter, Dropdown);

    CustomDropdownAdapter.prototype.render = function() {
      var $dropdown = $(
        '<div class="select2-dropdown">'
        + '<table class="select2-results"><thead><tr>'
        + '<th>Type</th><th>vCPUs</th><th>ECUs</th><th>Memory (GiB)</th><th>Hourly Rate ($)</th>'
        + '</tr></thead></table>'
        + '</div>'
      );

      $dropdown.attr('dir', this.options.get('dir'));
      this.$dropdown = $dropdown;
      return $dropdown;
    };

    var adapter = Utils.Decorate(CustomDropdownAdapter, Search);
    adapter = Utils.Decorate(adapter, AttachContainer);
    adapter = Utils.Decorate(adapter, AttachBody);
    return adapter;
  });

  // Customize instance type select box.
  $("#edit-field-instance-type").select2({
    closeOnSelect: true,
    dropdownAdapter: $.fn.select2.amd.require("CustomDropdownAdapter"),
    resultsAdapter: $.fn.select2.amd.require("CustomResultsAdapter"),
    templateResult: function(state) {
      var parts = state.text.split(":").slice(0, 5);
      var html = "<td>" + parts.join("</td><td>") + "</td>";
      if (parts.length == 1) {
        html = "<td colspan=5>" + parts[0] + "</td>";
      }
      $state = $(html);
      return $state;
    },
    templateSelection: (state) => {
      if (!state.id) {
        return state.text;
      }
      var $state = $(
        '<span>' + state.text.split(':').shift() + '</span>'
      );
      return $state;
    },
  });
})(jQuery);
