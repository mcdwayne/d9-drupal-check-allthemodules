------------------------------------
INSTALLATION
------------------------------------

1. Enable the module. You will need to install these module dependencies as
well: Libraries, Chosen, Inline Entity Form and Field Group. Make sure to
follow the instruction for installing Chosen.

2. Copy the "stacks" directory from the stacks module in your current theme's
root directory. This directory contains the templates for your widgets. Do not
update the module's directory twig files because Drupal will use only the theme
directory ones. The widgets that are defined in the directory are default
widgets present in the module.

3. Add a “Stacks” type field to a content type and make sure it allows for
unlimited rows. After you add the field to a content type, go to the “Manage
Form display” area and click the cogwheel to select the widgets that will be
available for that field. This is done in the “Enabled widget types” checkboxes.
Each new widget type bundle you create will be available here for selection.

4. In the configuration form indicated on #3, you will also have options to
select “Required widget bundles: Position locked” widgets. These are widgets
that are auto added to nodes that include this stacks field.  These widgets also
have a fixed position on the rendered page and cannot be moved by content
editors. The “Required widget bundles: Position optional” are also automatically
added when you create a node, but the position for these widgets can be changed
in the edit form. If you are just getting started, only worry about the "Enabled
Widgets" section and check at least one option.

5. After selecting options for #4 and #5, click Update and then Save the form.

6. Clear Drupal cache to make Drupal aware of any new template file / option.

Each widget can have an unlimited number of variations. These are called
templates. These templates are created in your theme and allow for full control
over the html of each widget.


------------------------------------
CREATING A NEW BASIC WIDGET
------------------------------------

A basic widget is a widget that has a set of defined fields.

Example: You have a widget called featured image that displays an image, title
and description. You create a new widget bundle with those fields and create a
default variation that outputs the html you want for that widget.


1. Go to Structure -> Stacks -> Manage widget bundles. Click on “Add Widget
Entity Type” button.

2. Make a note of the machine name given to the Widget bundle (for a later
step). Add the fields you would like in your new widget bundle. In the case of
this example, you would add Image file, Title textfield and Description
textarea.

3. Edit the content types having a Stacks field and go to Manage Form Display.
Enable the newly created widget for that field using the “Enabled widget types”
checkboxes. Update & Save.

4. Create a directory in your theme under the stacks directory. The name should
be the machine name of the widget bundle (replace _ with -). Example: text-
widget

5. For each variation (template) you want to add for this widget, create a file
with this name: stacks/[WIDGET MACHINE NAME]/templates/[WIDGET MACHINE NAME]--[VARIATION NAME].html.twig.
You can check the bundled example twig files to get a better idea about the
contents of this template file.

Example: text-widget--default.html.twig

At a minimum, you need to define a "default" variation. In this file you can
access the values for all the fields attached to the widget under the fields
variable. Example: {{ fields.field_default_widget_text }} It will always be the
field's machine name.

Note: If you want to see all available field variables in a widget template,
enable the Devel Kint module, and then use this twig code:
{{ kint(fields) }}

6. Create an images directory inside of this widget directory (same level as the
“templates” directory). This contains all of the preview images for each variation,
that shows up in the admin: 

  [VARIATION NAME].jpg

  For each one of the template options previews and

  [VARIATION NAME]--[THEME OPTION NAME].jpg

  For each one of the theme options available on each variation, if any.

Examples: 
  default.jpg
  default--white-on-blue.jpg

7. Clear Drupal cache.


------------------------------------
WIDGET TYPE GROUPS
------------------------------------
It is possible to group widgets together. You would do this for organizational 
purposes. An example of this is Content Feed.

By default there is a “contentfeed” widget type group. Any widget that
starts with “contentfeed” in its machine name is added to this group
automatically. What this means is that there is one option at the top level for
this widget type group (Content Feed). When this is selected, the widgets and
all of the template variations are added as options in the next step.

The idea is that when a "widget type group" is defined, all widgets that match
that type group will be bundled under that Widget Type in the Widgets field
dropdown. All widgets + variations will then appear under that selection...just
like template variations for a normal widget.

An example of this is the Content Feed widget type. It is defined as
"contentfeed". Any widgets that are added where the machine name starts with
"contentfeed_" will be considered under that group.

To add a new widget type group, go to the settings config file
stacks.settings.yml and add a new row under "widget_type_groups". This is how
the content feed widget type group looks under that config:

widget_type_groups:
   contentfeed: ‘Content Feed'

This feature can be helpful in organizing a large site.

Note: In order to have this settings file available for modifications, you need
to export configuration (or execute drush cex command), modify it, and then
import configuration (or execute drush cim command).


------------------------------------
TEMPLATE THEMES
------------------------------------
For each widget, there are template variations that are used to give different 
visual looks of the same data. 
For each template variation, there can be defined theme options. The "theme" is
another variable that is sent to the template file..

Examples might include:
  - Adding a class to the div wrapper.
  - Outputting html when a certain theme is selected.

The way the template uses the theme is completely customizable.

In order to define themes for a template, you will need to update the config
file "stacks.settings.yml" then import the config (drush cim). The basic
structure looks like this:

template_themes_config:
   [widget machine name]
      [template machine name]
        [theme value]: [theme label]
        [theme value]: [theme label]
      [template machine name]
        [theme value]: [theme label]

Below are some example themes that are set by default for the default Content
Feed.

template_themes_config:
   contentfeed_default:
        default:
            blue: Blue
            red: Red
        new_template:
            green: Green
            pink: Pink


------------------------------------
REQUIRED WIDGETS
------------------------------------
When you go to the form display tab for a content type that has a widgets field 
and click on settings for that widget field, you will notice a few sections:

1. Enable Widget Types: This is where you enable widgets to be available as
options in the drop down for this widget field in the node edit field.

2. Required Widget Bundles: Position Locked: These widgets are automatically
added to nodes that have this widget field. They are added to the top and the
position for these widgets cannot be changed or deleted.

3. Required Widget Bundles: Position Optional: These widgets are automatically
added to nodes that have this widget field. They are added to the top and the
position for these widgets can be changed or deleted.


------------------------------------
TEMPLATE DEBUGGING
------------------------------------
If you want to see all available field variables in a widget template,
enable the Devel Kint module, and then use this twig code:
{{ kint(fields) }}

This will only work if you have twig debug mode. See here in how to enable this
(read the original post and this specific comment):
https://www.drupal.org/node/2605652#comment-11052015

That will print out all variables and stop page loading. Only use this for
debugging and comment out or delete when finished.


------------------------------------
TROUBLESHOOTING
------------------------------------

1- Reinstall

After uninstall this module, if you want to install it again you might get this 
error message:

"Unable to install Stacks, core.entity_view_mode.node.admin_preview already 
exists in active configuration."

To fix this, you have to go to /admin/structure/display-modes/view and delete 
the "Admin Preview" view mode. After that, you will be able to install it 
normally.

2- Uninstall

If Drupal prevents uninstalling Stacks with a message "Fields pending deletion" 
go to Reports > Status Report and check "Entity / field definitions" entry.

If it isn't up to date, then you can try running drush entity-updates command.