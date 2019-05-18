Source Plugins
==============

The Source and Destination of config data is provided by a "Source Plugin".
They are specified using the ``source`` and ``dest`` options respectively.
The provided plugins are:

**id**
  The specifier is a string value that points to a specific config
  item id within the active config storage.

**file**
  The specifier is a `*.yml` file along with optional path. If no path
  is given, the config/templates directory of the current module is used.
  The file does not need to contain a full config item. The config data from
  the file will be merged with any existinf configuration data.

**list**
  The specifier is a sequential array of other sources. The first valid
  source from the array is used. Useful when specifying both a config ``id``
  as well as a template ``file``. If the config id already exists, it is used,
  otherwise the template file is used to create new config.

**array**
  The specifier is a raw array of configuration data. When saved, this data
  is merged with the existing configuration data.

Normally the source plugin type is determined by the string specifier itself.
For example, if the string ends in ``.yml`` then the ``file`` plugin is used.

To override the plugin type, use the ``*_type`` option (``source_type`` or
``dest_type``) with the name of the plugin.

The source can also be a raw array of configuration data. This data is passed
to the plugin system in case a complex plugin needs to parse the additional
data.  But since the ``id`` and ``file`` plugins both expect a string value, any
array value is currently passed through as raw data.
