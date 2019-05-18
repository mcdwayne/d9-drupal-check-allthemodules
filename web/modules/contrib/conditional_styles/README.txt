ABOUT CONDITIONAL STYLESHEETS
-----------------------------

Internet Explorer implements a proprietary technology called Conditional
Comments. While web developers frown upon technologies that aren't cross-browser
supported, many CSS developers have found Conditional Comments very useful since
they can be used to fix the rendering of CSS in IE by placing IE-only CSS inside
conditional comments.

This module allows themes to easily add conditional stylesheets to the theme's
.info file.


THEME USERS
-----------

You only need to enable this module if a theme requires that you use it. Once it
is enabled, the module automatically performs all of its work for any theme
requiring it. You don't need to configure anything.


THEME DEVELOPERS
----------------

Without this module, the only way to have IE conditional stylesheets was to add
37 lines of code (more if you want to add more than one stylesheet) in two
horribly-difficult-to-remember function calls to your theme's template.php file:

  /**
   * Implements hook_preprocess_html().
   */
  function MYTHEME_preprocess_html(&$variables) {
    // Add conditional stylesheets for IE.
    drupal_add_css(
      drupal_get_path('theme', 'mytheme') . '/css/ie.css',
      array(
        'group' => CSS_THEME,
        'browsers' => array(
          'IE' => 'lte IE 7',
          '!IE' => FALSE,
        ),
        'weight' => 999,
        'every_page' => TRUE,
      )
    );
  }

  /**
   * Implements hook_preprocess_maintenance_page().
   */
  function MYTHEME_preprocess_maintenance_page(&$variables) {
    // Add conditional stylesheets for IE.
    drupal_add_css(
      drupal_get_path('theme', 'mytheme') . '/css/ie.css',
      array(
        'group' => CSS_THEME,
        'browsers' => array(
          'IE' => 'lte IE 7',
          '!IE' => FALSE,
        ),
        'weight' => 999,
        'every_page' => TRUE,
      )
    );
  }

Blech. Who wants to do that?

This module allows you to add "conditional-stylesheets" in a much simpler
manner, by adding lines to your theme's.info.yml file. The syntax for that is:

  conditional-stylesheets:
    EXPRESSION:
      MEDIA:
        css/style.css

  where
    EXPRESSION can be any of the "downlevel-hidden" expressions specified in:
      http://msdn.microsoft.com/en-us/library/ms537512.aspx
    MEDIA can be any of the normal CSS media keywords.

For example, to add a stylesheet that only targets IE 6 and below, use:
  conditional-stylesheets:
    lt IE 7:
      all:
        css/ie6-and-below.css

To add a print stylesheet for IE9 only, use:
  conditional-stylesheets:
    IE 9:
      print:
        css/ie9.css

And to add a print stylesheet for all version of IE, use:
  conditional-stylesheets:
    IE:
      print:
        css/ie9.css

*** IMPORTANT ***

Drupal 8 stores a cache of the data in .info.yml files. If you modify any lines in
your theme's .info.yml file, you MUST rebuild Drupal 8's cache.
