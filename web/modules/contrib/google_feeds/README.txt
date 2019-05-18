-- SUMMARY --

The Google Feeds module adds Views feed styles and row styles for Google feeds.
This enables you to easily generate a feed/sitemap that meets all the
requirements for Google.

Currently this module only supports a Google News feed.

For a full description of the module, visit the project page:
  http://drupal.org/project/google_feeds
To submit bug reports and feature suggestions, or to track changes:
  http://drupal.org/project/issues/google_feeds


-- REQUIREMENTS --

None.


-- INSTALLATION --

* Install as usual, see http://drupal.org/node/895232 for further information.


-- CONFIGURATION --

* Add the field field_goo to the content types that you would like to use in
  your Google News feed view:

  - Go to 'Structure' (yoursite.com/admin/structure)

  - Continue to 'Content types'

  - 'Manage fields' on the content type that you would like in your Google
    News feed

  - 'Add field'

  - Under 'Re-use an existing field', select 'List (integer): field_goo'

  - Press 'Save and continue'

  - Press 'Save settings'

  - Repeat these steps for any additional content type that you would like to
    use in your Google News feed.

* Create or edit a view that you want to use for the Google News feed to use
  the Google News style:

  - Create (or edit) a view that you want to use for your Google News feed

  - Add a 'Feed' display

  - In the new 'Feed' display set the 'Format' -> 'Format' to 'Google News'

  - And set the 'Format' -> 'Show' to 'Google News Fields'

  - Add the content fields in the view/display for at least path (absolute),
    publication date and title and if you want or need to you can also add
    fields for Google News genres (the field_goo that you added earlier),
    keywords and stock tickers

  - Under 'Format' -> 'Show' -> 'Settings', set values to the previously added
    fields, set 'Name field' to the EXACT name of the site and set the optional
    options to the corresponding fields if you have them

  - Apply the settings, set a path (if you haven't done so already) and save the
    view

  - Customize any other setting on the view to your liking, for example the
    number of items to display


-- CUSTOMIZATION --

None.


-- TROUBLESHOOTING --

No known issues.


-- FAQ --

None.


-- CONTACT --

Current maintainers:
* Tim Kruijsen (timKruijsen) - https://www.drupal.org/u/timkruijsen
* Fido van den Bos (fidodido06) - https://www.drupal.org/u/fidodido06

This project has been sponsored by:
* Ordina Digital Services
  Specialized in Drupal development and maintenance.
