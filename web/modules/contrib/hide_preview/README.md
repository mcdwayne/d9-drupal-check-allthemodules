# Drupal 8 - Hide Preview

A Drupal8 module to hide the preview button on some forms.

Use regexp to select the forms where you want to hide the preview button.

## Installation

Place the module in your `modules` folder and enable it as usual with drush `drush en hide_preview` or from the `/admin/modules` page.

## Configuration

Go to the config page of the module `/admin/config/hide_preview` and fill in the form names for which you want to hide the button.

You can either use a string that represent the beginning of the the `form_id` or a regular expression that the `form_id` must match.  

### Example

For the contact form, you can use the following regexp.

`/contact_message_*/`