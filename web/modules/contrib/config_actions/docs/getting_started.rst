Getting Started
===============

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

Actions
-------

An Action consists of three simple steps:

1. `LOAD <source_plugins.html>`_ config from a source
2. `TRANSFORM <plugins.html>`_ the config data
3. `SAVE <source_plugins.html>`_ config to a destination

Action Files
------------

Actions are listed within `*.yml` files stored in the ``config/actions`` folder
of your custom module.  When your module is enabled, the actions in this file
will be executed.  The `*.yml` files can have any unique name, but if you don't
specify a source id for your action, the name of this YAML file will be used
by default.

An *action* is a list of "option" keys and values. Various `global options <options.html>`_ are
available, and additional options can be added by specific plugins.

Nested Actions
--------------

Actions can be nested within each other.  Using the ``actions`` option you can
list additional sub-actions to be executed.  All options from the main parent
action are inherited in each sub-action but can be overridden by the sub-action.

For example, the top-level action can specify the ``source`` and ``dest`` options
then each sub-action could specify different plugins, or different ``replace``
option values, or even override with different ``source`` or ``dest`` values.
This allows related actions to be grouped and reduces the amount of repeated
text between similar actions.

When nesting or naming actions, each new action within the ``actions`` list
requires a unique id key.

For example::

  actions:
    myaction1:
      option1: value1
      option2: value2
    myaction2:
      option1: value1
      option2: value2
    ...

