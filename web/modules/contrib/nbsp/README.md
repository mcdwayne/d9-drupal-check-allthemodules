# CKEditor Non-breaking space Plugin

Minimal module to insert a non-breaking space (`&nbsp;`)
into the content by pressing Ctrl+Space or using the provided button.

## Uses

During content creation the author may add a non-breaking space (`&nbsp;`)
to prevent an automatic line break.
To avoid that a company’s 2-word name is split onto 2 separate lines.

As the non-breaking space is an invisible character,
they are highlighted in blue on the CKEditor.

## Installation

Install the module then follow the instructions
for installing the CKEditor plugins below.

## Configuration

Go to the [Text formats and editors](/admin/config/content/formats)
configuration page:, and for each text format/editor combo
where you want to use NBSP, do the following:

* Drag and drop the 'NBSP' button into the Active toolbar.
* Enable filter "Cleanup NBSP markup".
* if the "Limit allowed HTML tags and correct faulty HTML" filter is disabled
you dont have anything to do with this text format.
Otherwise, add the `class` attribute to `<span>` in the "allowed HTML tags"
field (Eg. `<span class>`).

## NBSP versions

NBSP is only available for Drupal 8 !
The module is ready to be used in Drupal 8, there are no known issues.

This version should work with all Drupal 8 releases, though it is always
recommended to keep Drupal core installations up to date.

## Dependencies

The Drupal 8 version of NBSP requires
[Editor](https://www.drupal.org/project/editor) and
[CKEditor](https://www.drupal.org/project/ckeditor).

## Supporting organizations

This project is sponsored by Antistatique. We are a Swiss Web Agency,
Visit us at [www.antistatique.net](https://www.antistatique.net) or
[Contact us](mailto:info@antistatique.net).
