Template Example
================

The following action data (placed in ``my_module/config/actions/whatever.yml``)
will load templates within the config/templates folder
(see the ``tests/modules/test_config_actions`` test module for these template
files)::

  # this contains any global variables that are available to any template
  replace:
    "@field_name@": "myproject_image"

  # Here are some sample actions
  actions:

  # *****
  # Example of "template" plugin
  # *****

  # Replace any tokens in a template to create a new config item
    field_storage:
      # name of yml file in config/templates folder
      source: "field.storage.node.image.yml"
      dest: "field.storage.node.@field_name@"

    field_instance:
      source: "field.field.node.image.yml"
      dest: "field.field.node.@bundle@.@field_name@"
      actions:
        article:
          replace:
            "@bundle@": article
        page:
          replace:
            "@bundle@": page

When your ``my_module`` is enabled, the actions stored in ``whatever.yml``
will be executed.

The top-level action has a ``replace`` option for the global ``@field_name@``
variable.  The @ characters are used in the template to specify a replaceable
variable, but any delimiter could be used as needed.  Avoid using [] or {} to
specify variables since those could be interpreted as YAML arrays.

Next, the top-level action uses the ``actions`` option to specify a list of
sub-actions.  These sub-actions will inherit the global ``@field_name@``
replacement.

The ``field_storage`` sub-action (where ``field_storage`` is just a unique value
within this array used to give a name to the sub-action) loads a source
template ``*.yml`` file and outputs the config to a config id that will create a
new field_storage entity in Drupal.  ``@field_name@`` will be replaced with
``myproject_image``.

The ``field_instance`` sub-action sets a source ``*.yml`` file and a destination
and then has it's own sub-action list for each bundle that needs to have a
field instance created. Each sub-action (``article`` and ``page``) has it's own
value of the ``@bundle@`` variable that is used in the template replacement.

If this action file is executed, a field called ``myproject_image`` will be
created and added to the ``page`` and ``article`` content types.

But you can also call this action from your own module and override the
``@field_name@`` variable to create other fields.  You could create different
template actions for different types of fields.

For example, you could create a "Location Feature" that has a template for how
to add geofield data to a content type. Rather than saving the specific
configuration for the content type, field storage, and field instances in a
feature that would still contain your hardcoded field names and content type
names, you can use Config Actions to create template "features" that can be
reused across your projects with different field names and content types.
