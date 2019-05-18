
## SUMMARY

This module allows you to use Wysiwyg editors on legacy content that may not
already have HTML formatting applied, and also allows you to keep your nodes
free of extraneous HTML formatting (like `<p>` and `<br />` tags), instead
relying on Drupal's input formats to format your code properly.

There are two conversion methods that can be configured per-text-format:

  - **Force Linebreaks** (default): If have content with linebreaks instead of
    `<p>` and `<br />` tags, and/or would like to make sure that content is
    saved without `<p>` and `<br />` tags, choose this option.
  - **Convert Linebreaks**: If you have already-existing content with linebreaks
    instead of `<p>` and `<br />` tags, and would like to have this content
    render correctly in a Wysiwyg editor, but don't want the `<p>` and `<br />`
    tags stripped after content is saved, choose this option.


## INSTALLATION

Install as usual, see [Installing contributed modules](https://drupal.org/node/895232) for further information.


## CONFIGURATION

Go to the Text formats and editors page (at `admin/config/content/formats`) and
configure one of the text formats with CKEditor set as the text editor.

There should be a 'Force Linebreaks' section below the CKEditor toolbar
configuration, where you can choose the Force Linebreaks conversion method.


## CONTACT / MAINTAINERS

Current maintainer:

  - Jeff Geerling: http://drupal.org/user/389011
