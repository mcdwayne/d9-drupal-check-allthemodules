-- SUMMARY --

Creates a alertbox to display a message across the website.

This module came from the need to create alerts across the website (Homepage and
Inner pages) in a way that could be manageable by Content Managers without
giving them access to the "Block management" page.


-- REQUIREMENTS --

* This module requires only Drupal core modules:

  - block_content
  - options
  - text


-- INSTALLATION --

* Install and enable the module as usual. See
  http://drupal.org/documentation/install/modules-themes/modules-8 for further
  information.


-- CONFIGURATION --

* Site wide options:

  - /admin/structure/alertbox

    You can set some display options, which will be applied to all the
    alertboxes.

* Alertbox configuration:

  - /block/[BLOCK_ID]

    The configuration at block level is only regarding the visibility options.
    This is limited to show block on the "Homepage" or "Inner pages".
