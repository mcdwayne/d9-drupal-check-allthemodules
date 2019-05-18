Flag anonymous
---------------------

About
---------------------
Module provides ability:

 * Show configurable message "Login or Register to use this flag" to anonymous
   users instead of flag link

 * Show original flag label, on click: Display popin with message
   "Login or Register to use this flag"

 * Open Login and Registration form in popup (with ability to tune Login and
   Registration popups settings)

Basic usage
---------------------
Flag anonymous module helps you to show existing "flagging" ability to
anonymous users and motivate them to login or register in a simple way
(on the same page using popup).

Requirements
---------------------
 * Flag

Installation
---------------------
 1. Install the module to modules/contrib or modules folder
 2. Enable Flag anonymous module

Configuration
---------------------
 * Go to a Flags list page /admin/structure/flags

 * Click "Edit" link on Flag which you'd like to configure
    * Or click "Add flag" button if you'd like to add a new Flag

 * Scroll down flag edit page to "Anonymous settings" section
 * Check "Show this flag to anonymous users even if they don't have permission
   to use it." checkbox to enable module functionality.
    * IMPORTANT NOTE: Anonymous users shouldn't have Flag/Un-flag permission for
      currently edited flag (shouldn't be able to use this flag). If anonymous
      users have Flag/Un-flag permission remove it using
      /admin/people/permissions#module-flag page, otherwise module
      functionality will be blocked.

 * Choose "Label display" option:
    * "Keep original flag label (show Message on click)" - in this case
      original flag label will be displayed. On click by it: Popin with
      "Login or Register to use this flag" message will be displayed.
       * Set "Dialog message title" - it's a title of Popin with
         "Login or Register to use this flag" message. Leave blank to have
         empty Popin title.
    * "Show Message instead of flag label" - in this case flag label will be
      replaced with "Login or Register to use this flag" message.

 * Set "Message" - it's a "Login or Register to use this flag" message. You can
   use: @login - Login link, @register - Registration link placeholders there.

 * Set "Login link label" - a label for Login link placeholder.

 * Set "Register link label" - a label for Registration link placeholder.

 * Check "Show Login and Registration form in popup." checkbox if you'd like
   to show Login and Registration forms in Popup on click by
   "Login" or "Register" links
    * "Login popup settings" - Login popup settings for data-dialog-options
      attribute. Valid JSON should be used like: {"width": "auto"}
    * "Register popup settings" - Registration popup settings for
      data-dialog-options attribute. Valid JSON should be used like:
      {"width": "auto"}

 * Fill or change other needed fields and click "Save Flag" button.
