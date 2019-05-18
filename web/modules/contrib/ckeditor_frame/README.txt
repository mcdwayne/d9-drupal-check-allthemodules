README.txt
==========

Allows users to create & display content in a frame.
------------------------
Requires - Drupal 8

Overview:
--------
Adds a new button to Drupal 8's CKEditor which allows the user
to create & display framed content.

INSTALLATION:
--------
1. Install & Enable the module
2. Open Administration > Configuration > Content authoring >
   Text formats and editors (admin/config/content/formats)
3. Edit a text format's settings (usually Basic HTML)
4. Scroll down to the bottom to "Limit allowed HTML tags"
   (will only appear if the "Limit allowed HTML tags" filter is enabled)
5. Add <div> with <div class style>
   It ensures CKEditor doesn't remove the class name which framed content uses.
6. Open Administration > Configuration > Content authoring >
   CKEditor (admin/config/content/ckeditor)
7. Edit the relevant profile's settings
8. Scroll to "Editor appearance"
9. Under "Plugins" -> Enable "CKEditor Frame - 
   A plugin to easily create framed content"
10. Under "Toolbar" -> Drag n Drop the Add Frame button to the toolbar to show
    it to the users
