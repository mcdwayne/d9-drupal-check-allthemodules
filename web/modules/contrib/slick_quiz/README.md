ABOUT
------
This adds a new display style to views called "SlickQuiz". Similar to how
you select "HTML List" or "Unformatted List" as display styles.

This module doesn't require Views UI to be enabled but it is required if you
want to configure your Views display using SlickQuiz through the web
interface. This ensures you can leave Views UI off once everything is setup.

DEPENDENCIES
-------------
o Views (in core)
o Field collection (contrib)

INSTALLATION:
-------------
1. Install the module as normal, see link for instructions.
   Link: https://www.drupal.org/documentation/install/modules-themes/modules-8
2. Download SlickQuiz javascript library.
   (https://github.com/jewlofthelotus/SlickQuiz) into Drupal's libraries
   directory (Create one, if libraries directory is not present). Verify the
   file permission is web servable. Make sure the path to the plugin is
   libraries/SlickQuiz/js/slickQuiz.js, /libraries/SlickQuiz/css/slickQuiz.css,
   /libraries/SlickQuiz/css/reset.css.
3. A new format named as SlickQuiz will be available in views.

USAGE
------
Go to Views UI "admin/structure/views", add a new view, and a block.

Usage #1
---------
For using this module a Quiz Entity needs to be defined. Quiz entity can either
be a custom entity or Content Type.
Currently SlickQuiz module assumes that the options (answers) are added by
fieldcollection (https://www.drupal.org/project/field_collection).

Example Usage:
--------------
- Create a content type named as Quiz.
  - Add a optional question field (the node title can also act as question
    field).
  - Add correct answer feedback field.
  - Add wrong answer feedback field.
  - Add a field collection - unlimited Values.
- Go to structure -> field_collections, Add the following fields to the
  fieldcollection.
  - Add text field for storing options (answers).
  - a boolean field to mark the option as correct/wrong in the field
    collection.
- Go to Views UI "admin/structure/views", add a new viewblock with contents
  from content type Quiz.
- Choose "SlickQuiz" under the Format.
- Click settings button near to the selected format.
  - In general settings tab, you can add the texts for quiz title, descriptions
    and result summary texts.
  - In field mappings tab add the machine names of the fields you created.
    Click on apply.
- Save the view and assign the block in any region.
