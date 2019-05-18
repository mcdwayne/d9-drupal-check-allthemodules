CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Usage
 * Features
 * Installation
 * Notes
 * Similar Modules
 * Issues

Introduction
------------

The jQueryUI filter converts static HTML to a jQuery UI accordion or tabs widget.

For example, this module converts the below HTML code into a collapsed jQueryUI
accordion widget.

    <p>[accordion collapsed]</p>
    
      <h3>Section I</h3>
      <p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas.</p>
    
      <h3>Section II</h3>
      <p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas.</p>
    
      <h3>Section III</h3>
      <p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas.</p>
    
    <p>[/accordion]</p>

Learn more about jQueryUI's [accordion](http://jqueryui.com/demos/accordion/) 
and [tabs](http://jqueryui.com/demos/tabs/) widget.


Usage
-----

### Accordion

Use `[accordion]` and `[/accordion]` to create a jQuery UI
accordion. Using `[accordion collapsed]` will start with the accordion
closed.

### Tabs

Use `[tabs]` and `[/tabs]` to create a jQuery UI tabs.


Features
--------

- Supports all jQuery UI accordion and tabs options.
- Adds bookmark support to accordions and tabs.
- Scrolls to bookmarked accordion or tabs.
- Gracefully degrades when JavaScript is disabled.
- Defaults to original markup when an accordion or tabs widget is printed.


Installation
------------

1. Copy/upload the jquery_ui_filter.module to the modules directory of your
   Drupal installation.

2. Enable the 'jQueryUI filter' modules in 'Extend'. (/admin/modules)

3. Visit the 'Configuration > Content authoring > Text formats and editors'
   (/admin/config/content/formats).

4. Enable (check) the jQueryUI filter under the list of filters and save
   the configuration.

5. IMPORTANT: In 'Filter processing order', the 'jQuery UI accordion and
   tabs widgets' filter must be after the 'Correct faulty and chopped off HTML' filter.

6. (optional) Visit the 'Configuration > Content authoring > Text formats and editors > jQuery UI filter'
   (/admin/config/content/formats/jquery_ui_filter).


Notes
-----

### General

  - Goal is to keep this module as simple as possible.
  - Make it easy for developers and site builders to provide custom configuration.
  - Allow accordion and tabs widgets to still be extended and enhanced with
    custom code.
  
### For Site Builders

- Any jQuery UI [accordion](http://api.jqueryui.com/accordion/) or 
  [tabs](http://api.jqueryui.com/tabs/) option is supported.

- The `[token]` options can contain valid JSON data which will be converted to
  JavaScript array and objects when a widget is rendered.
    - JSON data must be [valid](https://en.wikipedia.org/wiki/JSON#Example).       
    - JSON can be wrapped in single quote instead of double quotes.
    - JSON parsing errors will logged to the browser's console.
    - The below example would create sliding tabs.  
      `[tabs show='{"effect": "slideDown", "duration": 1000}' hide='{"effect": "slideUp", "duration": 1000}']`

###  For Developers

  - 75% of this module's core code is in jquery\_ui\_filter.js, which transforms
    HTML5 <div> tags containing data-ui-* attributes into jQuery UI accordion
    and/or tabs with customize options.

  - The actually filter `\Drupal\jquery_ui_filter\Plugin\Filter\jQueryUiFilter`
    just transforms `[tokens]` with options into `<div>` tags with
    `data-ui-*` attributes. Example:    
    `<p>[accordion collapsed customAttribute="some value"]</p>`  
    ...is transformed into...  
    `<div data-ui-role-"accordion" data-ui-collapsed="true" data-ui-custom-attributes="some value">`  

  - All camelCase options will be convert to lower case hyphen delimited
    attributes, which are support by HTML5. The hyphen delimited attributes will
    be converted back into camelCase options when the widget is rendered.

  - A [JsFiddle example](http://jsfiddle.net/jrockowitz/raLvc6hj/) has been 
    setup to allow cross-browser testing and debugging outside of Drupal.
    
  - The `tests` directory contains manual test scripts and static HTML5 examples 
    for testing the front end JavaScript behavior.


Similar Modules
---------------

- [Quick tabs](http://drupal.org/project/quicktabs)  
  Create blocks of tabbed views and blocks.
  

Author/Maintainer
-----------------

- [Jacob Rockowitz](http://drupal.org/user/371407)
