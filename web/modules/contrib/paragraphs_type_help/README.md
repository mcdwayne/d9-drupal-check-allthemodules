Paragraphs Type Help
=======

[Paragraphs Type Help](https://www.drupal.org/project/paragraphs_help_type)
module provides a help entity that renders on Paragraphs type displays.

## Requirements

* Drupal 8
* [Paragraphs](https://www.drupal.org/project/paragraphs) module

## Recommended

* [Field Group](https://www.drupal.org/project/field_group) module: This module
  provides a way to tidy up the Paragraphs edit form with collapsible fieldsets
  and tabs.  Example: "Need Help?" details field group that contains the
  Paragraphs Type Help module's extra field.

## Installation

Entity Embed can be installed via the
[standard Drupal installation process](http://drupal.org/node/895232).

## Configuration

* Install and enable [Paragraphs](https://www.drupal.org/project/paragraphs)
  module.
* Install and enable
  [Paragraphs Type Help](https://www.drupal.org/project/paragraphs_help_type)
  module.
* Create / edit a Paragraphs bundle with fields at
  `/admin/structure/paragraphs_type`.
  * Configure the *form display*. By default, the extra field
    "Paragraphs Type Help: Rendered as Default" is added to the form displays.
    This extra field is rendered only if there is help content created for
    this Paragraphs type and form mode.
  * Optional. Configure the *view display*. The Paragraphs Type Help's extra
    fields are *NOT* added to the view display by default. This extra field is
    rendered only if there is help content created for this Paragraphs type and
    view mode.
* Optional. Customize the view display for the help entity type at
  `/admin/structure/paragraphs-type-help/display`.


## Usage

### Creating Help

* Create help content at `/admin/content/paragraphs-type-help`.
* *Required: Select a "Paragraph Type"* to set where the help will be displayed.
* *Optional: Set an "Admin label".* If not provided, this defaults to
  "PARAGRAPH_TYPE help".
* *Optional: Enter help text.* This is a long text field so that WYSIWYG editors
  can be used
  if enabled on the site.
* *Optional: Upload an image.* Example: A screenshot of the front end view of
  paragraph with
  annotations detailing how the form inputs are used in the display.
* *Optional: Select an "Active Paragraph Form mode".* This limits the help
  display to only the selected form mode. Defaults to 'default'. *Note:* The
  Paragraphs Type Help's extra fields *are enabled* by default on every
  Paragraph Type's *form modes*. The extra field provides conditional rendering
  as new help content is created.
* *Optional: Select an "Active Paragraph View mode".* This limits the help
  display to only the selected view mode. There is no default for the view
  modes. Example usage: "preview" view mode with a shorter help text.
  *Note:* The Paragraphs Type Help's extra fields are *NOT enabled* by default
  on the Paragraph Type's view modes. The view mode will need configured to set
  the Paragraphs Type Help's extra field to display.
* *Optional: Set a weight.*  The weight controls the order of the help when
  rendered on the Paragraph's display and in the admin list at
  `/admin/content/paragraphs-type-help`.
* *Publishing status* - If enabled then the help will be displayed.
* *Save* the help entity.

### Viewing Help

* Create a node that has a Paragraph field.
* Add a Paragraph bundle that has help created for the bundle and form mode.
* *Expected:* The help is displayed on the edit form.
