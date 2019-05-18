Config Actions Project
======================

`Config Actions <http://drupal.org/project/config_actions>`_
provides a pluggable framework for easily manipulating
configuration data via simple YAML files with the goal of creating truly
reusable software components in Drupal.

Example use cases include:

* **Templates**
    the ability to provide a configuration template file containing
    variables that can be reused and replaced to create new configuration. For
    example, a template for adding a certain field to a content type where the
    content type isn't yet known.

* **Override**
    the ability to easily "override" configuration provided
    by core or other modules. These is not a "live" overrides system but simply a
    method to import changes into the config system.

**NOTE**: This is a Developers module and requires creating custom modules
containing YAML files that contain the config actions to be performed.

.. toctree::
:maxdepth: 2

  getting_started
  options
  plugins
  path_validation
  source_plugins
  example_template
  example_override
