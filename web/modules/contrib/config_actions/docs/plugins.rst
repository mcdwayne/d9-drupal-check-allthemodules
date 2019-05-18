Plugins
-------

Actions are executed by plugins. Each plugin performs a specific transformation
of the config data.

Some plugins implement the `ValidatePaths <path_validation.html>`_ trait which adds
additional options for specifying a path into the config data and validating
that path.

The plugins currently available are:

**change**
  Uses ValidatePaths. This is the default plugin. String replacement is
  performed in the ``source`` data.  Any given ``value`` will
  be saved to the specified ``path`` in the config data.

**add**
  Uses ValidatePaths. Adds a new ``value`` at the specified ``path`` in
  the config data. Typically used to add additional array items.

**delete**
  Uses ValidatePaths. Clears the data at the specified ``path`` in
  the config data, or completely deletes a specific configuration item if
  no ``path`` is given.

**include**
  Loads and runs a specific ``action`` from a different ``module`` and ``file``.
  Allows the ``replace`` values to override those specified in the other module.
  Used to create reusable actions templates in your module that can be used by
  other modules.
