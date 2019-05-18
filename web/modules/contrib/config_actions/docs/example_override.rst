Override Example
================

The following action data (placed in ``my_module/config/actions/whatever.yml``)
will override the help text configuration for various fields and
content types::

  plugin: "change"
  path: ["description"]
  source: "@id@"
  actions:
    article_content:
      actions:
        node.type.article:
          value: "This is the description of the article content type"
        field.field.node.article.body:
          value: "This is the help text for the article body field"
        field.field.node.article.image:
          value: "This is the help text for the article image field"

    page_content:
      actions:
        node.type.page:
          value: "This is the description of the page content type"
        field.field.node.page.body:
          value: "This is the help text for the page body field"
        field.field.node.page.image:
          value: "This is the help text for the page image field"

When your ``my_module`` is enabled, the actions stored in ``whatever.yml``
will be executed.

The ``change`` plugin is used for overriding existing configuration.  The
``source`` specifies the configuration name to be changed, and the ``@id@``
replacement variable is used to allow each action id to specify the config
name.

The description values for the **Article** and **Page** content types are
split into two sub-actions to help with readability and organization, but
this isn't required in this case.

Within each bundle, the different description text values are specified.

Note that this allows you to organize all of the description text for the
project into a single file, rather than having it spread across six different
Features modules.
