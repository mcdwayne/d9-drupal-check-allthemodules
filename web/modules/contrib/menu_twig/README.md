CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended Modules
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

Menu Twig is a Drupal module that provides text-area to render the HTML or Twig
extensions.

This module provides flexibility to exclude the menu item and
rewrite code in your own pattern.

 * For a full description of the module visit:
   https://www.drupal.org/project/menu_twig

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/menu_twig


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


RECOMMENDED MODULES
-------------------

To enhance the module capability you can enable the Twig tweak
module so blocks can be rendered inside the menu item.

 * Twig Tweak - https://www.drupal.org/project/twig_tweak


INSTALLATION
------------

From your drupal root run the following commands:
  drush en -y menu_twig

Or install the Menu Twig module as you would normally install a contributed
Drupal module.

Visit https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

To add text or an image after a link":

    1. Navigate to: Structure > Menu > Main navigation > Add Link.
    2. Open the "MENU TWIG" section.
    3. In the textarea choose the text editor and write some text or insert
       image inside the textarea.
    4. Save the form.


To Rewrite or add attributes to a link:

    1. Navigate to: Structure > Menu > Main navigation > Add Link
    2. Open the "MENU TWIG" section.
    3. Select the checkbox that says "Exclude the menu item or override the menu
       item with text editor".
    4. Add your Twig markup. Three examples are listed below.

        {{ link(title, url)}}
        {{ link(title, url, { 'class':['overwrite_link']}) }}
        <a href="{{url}}" >{{title}}</a>


To render a custom block or view block:

    1. To render the custom block or view block, you need to install and enable
       the "Twig tweak" module.
    2. Navigate to: Structure > Menu > Main navigation > Add Link.
    3. Open the "MENU TWIG" section.
    4. Write the Twig extension code inside the textarea to render the block
       within the link itself.

        {{ drupal_view('who_s_new', 'block_1') }}


How to use Twig extension filters and functions with Menu Twig:

    1. Navigate to: Structure > Menu > Main navigation > Add Link.
    2. Open the "MENU TWIG" section.
    3. You will find "filters" and "functions" links in the description section
       of the textarea. Click on the links and you will find all the available
       Twig functions and filters available in the system.
    4. Twig provides a number of handy functions that can be used directly
       within the templates.
    5. Filters in Twig can be used to modify variables. Filters are separated
       from the variable by a pipe symbol. They may have optional arguments in
       parentheses. Multiple filters can be chained. The output of one filter
       is applied to the next.

        <a href="{{ url('view.frontpage.page_1') }}">
        {{ 'View all content'|t }}</a>


To Optimize the HTML output render by adding a Twig extension:

    1. Navigate to: Structure > Menu > Main navigation > Add Link.
    2. Open the "MENU TWIG" section.
    3. There are two ways to optimize the output of Twig extension.
    4. First option: You can restrict the output of the Twig extension by
       choosing the textarea filter. Choose the text format 'Restricted HTML'
       to get clean content.
    5. Second option: You can use Twig extension filters to strip the HTML using
       filter "striptags".

  See the example below:

        {% set whonew = drupal_view('who_s_new', 'block_1')  %}
        {{ whonew|render|striptags('<a>') |raw }}


MAINTAINERS
-----------

 * aloknarwaria
