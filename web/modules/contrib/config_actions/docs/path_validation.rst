Path Validation
===============

Some plugins specify a ``path`` within the source data. A path is simply an array
of keys to be traversed within the source tree.  The following options are
added by this trait:

**path**
  The array of keys used to specify the path in the source data.

**current_value**
  The optional current value in the config data path. Used to
  ensure that the specified value exists in the data before manipulating it.
  This must be specified to enable path validation.

**value_path**
  An optional path to be used instead of the normal ``path`` for
  validating the ``current_value``. Used when changing the value in ``path`` but
  testing ``current_value`` in a different ``value_path``.
