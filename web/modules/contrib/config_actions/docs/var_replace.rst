Variable Replacement
====================

A key feature of Config Actions is the ability to perform variable
substitutions within the configuration data.

Variables
---------

Variables have the syntax: ``@variable_name@`` where "variable_name" is the
alphanumeric name of the variable (with underscores or hyphens allowed)

The value of a variable is specified in the action file using the form::

  "@variable_name@": value

Anywhere else in the action or included template files, a reference to
``@variable_name@`` will be replaced with the ``value``.

String Replacements
-------------------

The ``replace`` option is an array of string replacement patterns and values.
The above syntax for variables actually causes an entry to be made into the
string replacement array. But you can actually replace any string value.
For example::

  replace:
    foo: bar

will cause any text matching "foo" to be replaced with the value of "bar".

Inherited Variables
-------------------

Variables and string replacement values are passed from action to sub-action.
Typically, global values for variables are set at the top of the action file,
allowing any other modules that "include" the action to override the value.

For example, if ``ActionA.yml`` had the contents::

  "@bundle@": article
  "@name@": "Article"
  source: node.type.@bundle@
  value:
    name: "@name@"

then another action could "include" it and change the variable data that is
passed::

  plugin: include
  file: "ActionA.yml"
  "@name@": "My Article Content"

Sub-actions can also override variable values.  For example::

  "@name@": "Article"
  source: node.type.@bundle@
  actions:
    action1:
      "@bundle@": article
      value:
        name: "My article"
    action1:
      "@bundle@": page
      value:
        name: "My page"

Each sub-action sets its own value of "@bundle@" and the value of ``source``
is automatically recomputed for each action.

NOTE: When specifying variables directly within a sub-action, any other module
including this action will not be able to override it. External actions will
normally only override the global value of the variables in the file. To force
a sub-action variable to be overridden, you can specify the exact ``action`` id
(such as "action1") you want to include rather than including the entire file.

ADVANCED Usage
--------------

The following topics are for more advanced usage and understanding.

Option Variables
~~~~~~~~~~~~~~~~

Each "option" in your action is available as a pre-defined variable.  For
example::

  source: [ "@dest@", node.type.template.yml ]
  dest: node.type.article

will automatically expand to::

  source: [ node.type.article, node.type.template.yml ]
  dest: node.type.article

The special "id" variable
~~~~~~~~~~~~~~~~~~~~~~~~~

In addition to the pre-defined option variables, the "id" of each action is
available via the ``@id@`` variable. For example::

  source: "node.type.@id@"
  path: [ "description" ]
  actions:
    article:
      value: "This is the article description"
    page:
      value: "This is the page description"

This would execute two sub-actions, and the key value of each sub-action is
used as the ``@id@`` variable, which is then replaced in the ``source`` option.
Thus, the description of the "article" and "page" content types can be easily
changed without needing to specify the ``source`` directly in each sub-action.

The special "key" option
~~~~~~~~~~~~~~~~~~~~~~~~

To parse the action "id" into other user-defined variables, use the ``key``
option to specify the pattern of the action id. For example, here we load
different template files based on the name and type of fields being created::

  source: "field.field.node.@type@.yml"
  key: "@bundle@.@name@.@type@"
  path: [ "description" ]
  actions:
    article.my_text_field.text:
      value: "Description of my_text_field
    page.my_image_field.image:
      value: "Description of my_image_field

This would first load the "field.field.node.text.yml" template and define the
variables: ``@bundle@: article``, ``@name@: my_text_field``, ``@type@: text``
and next it would load the "field.field.node.image.yml" template and define
the variables: ``@bundle@: page``, ``@name@: my_image_field``, ``@type@: image``

Replacement Locations
~~~~~~~~~~~~~~~~~~~~~

You can control which options perform string replacement, and whether the
patterns are replaced in just the array values within config data, or also
in the keys.

The ``replace_in`` option overrides the array of options that perform string
(and variable) replacements. The default of this array varies from plugin to
plugin. Override it to specify only the exact options to replace.  For example::

  source: "foo.bar"
  dest: "bar.foo"
  replace:
    foo: bar
  replace_in: [ 'dest' ]

will only replace "foo" in the ``dest`` option but not in the ``source`` option.

