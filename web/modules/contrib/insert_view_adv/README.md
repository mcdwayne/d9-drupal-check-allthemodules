ADVANCED INSERT VIEW
=======================
This is the alternative version of insert view module that is originally posted on Drupal.org here http://www.drupal.org/project/insert_view . The reason for re-development was the desire to use all Drupal 8 utilities that can make the module more performant and user friendly.

First of all, this version consists of built-in CKEditor plugin that allows user to add the view into the editor without following the tag syntax (although this method is still available, refer to OVERVIEW section below). CKEditor plugin provides user friendly interface with a toolbar button and a dialog to choose the view you want to add to your page. Additionally, in the dialog you can see all available contextual filters that current view display has. Normally it even shows the human readable field label (by default it is machine name, but if the field is used only in one entity bundle, the dialog shows the label). And at last, all the entity reference contextual filters now have the autocomplete field input, so the user doesn't have to find the id of the entity.

Secondly, this version fixes the performance problem with entity caching. Now the inserted view uses Drupal placeholders feature, that allows the system to cache the entity and inserted view separately. Moreover, the placeholders are designed in the way that BigPipe module can be used, so now even the "heaviest" view will not frustrate your end customer, because the page will still load fast and the view will appear when it is ready.

SECURITY WARNING
----------------
The module provides the ability to be built into CKEditor as a filter. This means
that it could be controlled via admin UI.

This plugin is very powerful, therefore the filter that uses it should be granted to trusted users only.
This is easily done in "Text and formats" section of "Configuration".
If you allow this filter to untrusted users, then you have to make sure that
EVERY VIEW EVERY DISPLAY (default display also!) has correct views access
settings.

OVERVIEW
--------
**Old method, but still available:**  
Insert view filter allows to embed views using tags. The tag syntax is
relatively simple: `[view:name=display=args]`. The parameters are: view name, view
display id, view arguments. For example `[view:tracker=page=1]` says, embed a view
named "tracker", use the "page" display, and supply the argument "1". The
display and args parameters can be omitted. If the display is left empty, the
view's default display is used. Multiple arguments are separated with slash. The
args format is the same as used in the URL (or view preview screen).

Valid examples:

`[view:my_view]`  
`[view:my_view=my_display]`  
`[view:my_view=my_display=arg1/arg2/arg3]`   
`[view:my_view==arg1/arg2/arg3]`  

**New method:**  
use "Insert view" button from CKEditor toolbar with a dialog to add the view with contextual filters to the page.

HOW TO FIND A DISPLAY ID (Optional for new method)
------------------------
On the edit page for the view in question, you'll find a list of displays at the
left side of the control area. "Defaults" will be at the top of that list. Hover
your mouse pointer over the name of the display you want to use. A URL will
appear in the status bar of your browser.  This is usually at the bottom of the
window, in the chrome. Everything after #views-tab- is the display ID. For
example in http://localhost/admin/build/views/edit/tracker?destination=node%2F51#views-tab-page
the display ID would be "page".

INSTALLATION
------------
Extract and save the insert_view folder in your site's modules folder and enable
it at admin/build/modules. Obviously, it requires the Views module to do its
magic.

Once "Advanced Insert view" module is installed, visit the the input formats page at
/admin/settings/filters and click the "configure" link for the input format(s)
for which you wish to enable the Insert view filter. Then simply check the
checkbox for the filter. Also you need to add the "Insert view" button to CKEditor toolbar. This can be
easily done by drag and drop. 

Advanced Insert View CKEditor plugin has 1 setting: you can disable views live preview.  
Advanced Insert View Filter has 2 settings:
- Allowed views: you can select which view displays are allowed to be inserted into editor per format
- How to deal with the view with no results: display token or leave empty.

PERFORMANCE
-----------
The module uses Drupal text placeholders and lazy loading (if dynamic cache and BigPipe are enabled)
