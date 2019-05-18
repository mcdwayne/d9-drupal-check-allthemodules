-- SUMMARY --

Helpfulness Feedback provides a block to gather feedback from the user.

The provided block will display a simple yes/no radio button with a question if
the information on the website was useful. Upon selection of the radio button a
section with a text area will expand, so the user can leave additional feedback.

Features:
* The text above and below the comments area can be customized in the
configuration page for the block
* Email notification upon receipt of new feedback
* Customization of data displayed in the report of submitted feedbacks:
-- Name of User
-- Helpfulness Rating
-- Message
-- Base URL
-- System Path
-- Alias
-- Date
-- Time
-- Browser Info
* Integration with MOLLOM

For a full description of the module, visit the project page:
  https://www.drupal.org/project/helpfulness

To submit bug reports and feature suggestions, or to track changes:
  https://www.drupal.org/project/issues/2243485

-- INSTALLATION --

* Install as usual
* Remember to set permissions for the module (otherwise the helpfulness block
  will not show up for the anonymous user)!

-- CONFIGURATION --

* Configure user permissions in
Administration » People » Permissions » Helpfulness Feedback.


-- USAGE --

* To view submitted feedback messages, go to:
 /admin/reports/helpfulness


-- CONTACT --

Current maintainer:
* heymo - https://www.drupal.org/u/heymo
