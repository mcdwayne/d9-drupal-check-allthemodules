# Intelligent Tools

## How to use

- Place the module in modules folder of your drupal site.
- Enable the Intelligent agents module and sub-modules i.e. Auto tag, Text
  Summarize, Duplicacy Check.

- Instructions per submodule
  [Tagging]
  - Enable intelligent agent module and auto tag module in your drupal site.
  - Go to configuration -> Auto Tagging settings -> select fields and submit
    the form. Do make sure that the content type has field that is to be tagged
    in it, If not then add it in manage fields.
  - For example: Content Type - Article, field to be used - body,
    field to be tagged - field_tags.
  - Go to Content and add or edit the content form change body, Save it.
  - The Tags will be reflected in the node display.

  [Summarize]
  - Enable intelligent agent module and text summarize module in your drupal
    site.
  - Go to configuration -> Text Summarize settings -> select fields and
    submit the form.
  - For example: Content Type - Article, field to be used - body.
  - Go to Content and add or edit the content form change body, Save it.
  - The Summary Field will be reflected in the node display, which will be
    saved in database as field_summ field name.

  [Duplicity]
  - Enable intelligent agent module and duplicity rate module in your drupal
    site.
  - Go to configuration -> Duplicity Rate settings -> select fields and
    submit the form.
  - For example: Content Type - Article, field to be used - body.
  - Go to Content and add the content form change body, Save it.
  - The Percent Duplicate Field will be reflected in the node display, which
    will be saved in database as field_dupl field name.

- The changes will be reflected according to submodule(s) enabled.
