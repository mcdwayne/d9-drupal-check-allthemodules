Global Options
==============

Actions consist of a simple array of key/value options.  The following global
option keys are recognized:

**plugin**
  Specifies the name of the Config Actions Plugin to be used to
  execute the action.  If omitted, the "Change" plugin is used.

**source**
  The `source data <source_plugins.html>`_ specifier. Can be a config id,
  a ``*.yml`` file, or a raw data array.
  Contains the source config data to be manipulated.
  If not specified, the filename of the YAML file containing the action will
  be used (without the yml extension).

**dest**
  The `destination specifier <source_plugins.html>`_.
  Can be a config id or a ``*.yml`` file.
  The modified data will be stored to this location. If omitted, the source is
  used as the destination.

**replace**
  An optional key/value array that contains `string replacement <var_replace.html>`_
  patterns and values. Can be used to replace patterns in the source data or
  in any other option value.

**replace_in**
  An optional array listing the options that the ``replace`` is
  performed in. The default value of this depends on the specific plugin
  being used. The array given here replaces the default list for the current
  action.

**auto**
  Normally, all actions in a module are executed automatically when the module
  is enabled. To prevent an action from being automatically executed, set the
  ``auto`` property to false. This is used when creating template actions to
  be included from other modules.
