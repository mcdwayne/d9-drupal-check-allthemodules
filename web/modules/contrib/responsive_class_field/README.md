RESPONSIVE CLASS FIELD
======================

INTRODUCTION
------------

The Responsive Class Field module provides a field type that allows content
editors to define breakpoint dependent styling options (CSS classes) for the
entity view display of the entity it is attached to.  

Rather than using general display settings for an entity type/bundle, the
styling can be configured individually for each entity.  

The module has been created to improve content editing in projects that use
a responsive front-end theme as [Bootstrap]
(https://www.drupal.org/project/bootstrap) and the
[Paragraphs](https://www.drupal.org/project/paragraphs) module for content
editing. It isn't limited to Bootstrap or Paragraphs, but comes with a set of
breakpoints best suitable for this use. You can alternatively use any other
breakpoints group defined by any of your modules or themes, and also use the
responsive class field with regular content or other custom (fieldable)
entities.  


FEATURES
--------

  * Define CSS class patterns that depend on breakpoints and a list of given
    options  
    Configure a set of options for each field instance. These options can be
    chosen for every enabled breakpoint. The module will automatically
    generate the CSS classes and add them to the entity view display.
  * Automatically attach the CSS classes to certain entity view displays only  
    When using different entity view displays for an entity, the CSS classes
    will be attached to the view display only, where the responsive class
    field(s) are configured to be visible.  
  * Limit automatically attached classes to certain themes  
    Selectively define the theme(s) where generated CSS classes should be added
    to your entity view displays.  


REQUIREMENTS
------------

  * Drupal 8  
    There is no Drupal 7 version of this module.  


DEPENDENCIES
------------

There are no other dependencies than the Drupal core entity and field
features.  


INSTALLATION
------------

  * Install the module and all its dependencies as you would do with any other
    Drupal module.  
    If using [Composer](https://getcomposer.org/) for dependency management,
    you can use  
    `composer require "drupal/responsive_class_field"`  
  * Enable the module.  


CONFIGURATION
-------------

  * Before adding responsive class fields to any entity, you may wish to
    configure a default breakpoint group and which theme(s) to use when adding
    responsive classes to entities. These general configuration options are
    available at  
    `Configuration > Content authoring > Responsive class fields`  
  * Add a responsive class field to the entity within its `Manage fields`
    configuration page.  
  * Define a CSS class pattern using the tokens `{breakpoint}` and `{value}`.  
    Example: For Bootstrap 4's padding utility classes, the pattern could look
    like `p{breakpoint}-{value}`.  
  * Enable the breakpoints you want to use and configure a label and
    breakpoint token replacement for each breakpoint.  
    Example: For Bootstrap's `Medium` screen width breakpoint, the token
    replacement would be `-md`.  
  * Define the list of allowed values. Its keys will be used to replace
    the `{value}` token of the CSS class pattern.  
    Example: For the above Bootstrap 4 padding utility classes, the values
    would be  
    `0|None`  
    `1|0.25 rem`  
    `2|0.5 rem`  
    `3|1 rem`  
    `4|2 rem`  
    `5|3 rem`  
  * Within your entity's `Manage form display`, ensure the field is enabled
    and uses the `Responsive class` form widget.
  * Within your entity's `Manage display`, ensure the field is enabled for all
    displays where the classes should be added.  
    Note: The `Responsive class` formatter won't produce any output on its own,
    so the label position does not affect your output.  


SIMILAR PROJECTS
----------------

As to our knowledge, there is no other module available, that allows adding
responsive classes by a content editor on a per-entity base. (Feel free to
suggest your module for addition here.)  

If you are seeking for a general back-end configurable approach to style your
entity view displays, you may find the following modules useful:  

  * [Display Suite](https://www.drupal.org/project/ds)  
    This module allows to use various predefined layouts for your entity
    views.  
  * [Field Group](https://www.drupal.org/project/field_group)  
    This module allows to wrap your entity's fields into custom HTML elements
    that can have their own CSS classes.  
  * [Field Formatter Class]
    (https://www.drupal.org/project/field_formatter_class)  
    For a predefined set of classes on your entity's fields, consider using
    the Field Formatter Class module.  


TROUBLESHOOTING & FAQ
---------------------

**Q: I configured the responsive class field, made some choices while editing
the content, but the classes don't show up in my content's output.**  
A: Ensure your theme is configured within the module settings, the responsive
class field enabled within the `Manage display` of your current entity display,
and the theme's template prints the CSS classes of its `{{ attributes }}`
variable.

**Q: I'd like to use additional breakpoints, but can't find a suitable
breakpoint group.**  
A: The default breakpoints provided by this module should suffice most of your
use cases. As its media queries aren't used for generating the CSS classes,
you may just rename its labels to your requirements. If the amount of
breakpoints is not enough and your Drupal installation doesn't feature any
other breakpoint groups, you may need to add a `*.breakpoints.yml` file to any
of your custom modules or themes.  
See [Working with breakpoints](https://www.drupal.org/docs/8/theming-drupal-8/working-with-breakpoints-in-drupal-8)
for more information.  

**Q: I'd like to add responsive classes to different fields of my entity. How
can I do this?**  
A: The responsive class field is intended to automatically add classes to its
parent entity's attributes only. If you don't want to wrap your fields into
dedicated child entities (e.g. using
[Paragraphs](https://www.drupal.org/project/paragraphs)), you can however add
multiple responsive class fields to your entity and access their generated
classes within your template's preprocess hook or in your templates using the
field's `classes` property.  


ISSUES & FEATURE REQUESTS
-------------------------

The module is considered feature complete by the maintainers. If you find a
bug or are missing really important features, please use the module's issue
queue.


MAINTAINERS
-----------
  * [Mario Steinitz](https://www.drupal.org/u/mario-steinitz)


SUPPORTING ORGANIZATIONS
------------------------
[SHORELESS Limited](https://www.drupal.org/shoreless-limited)  
SHORELESS Limited is an IT consulting and software solutions provider. The
development of this module has been funded by SHORELESS.  
