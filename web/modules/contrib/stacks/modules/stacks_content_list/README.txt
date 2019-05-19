-------------------------
INSTALLATION
-------------------------
1. Enable the stacks_content_list module

2. Make sure your Drupal database config matches the config files. Then update
stacks.settings.yml by adding (this also has content feed):

widget_type_groups:
   contentlist: 'Content List'
   contentfeed: 'Content Feed'

Make sure to import that config into your drupal database (drush cim).

3. Copy the "contentlist" directory from the "stacks" directory into the "stacks"
directory in your theme. These are the default templates included with the
module.

4. Go the form settings of the Stacks field and make sure to enable the Content
List widget. This works the same as it does for Basic Widgets.

5. Clear the Drupal cache.


------------------------------------
CREATING A NEW CONTENT LIST WIDGET
------------------------------------
Content list widgets provide a way to create manual lists of content that are 
not pulling from content types or other entities. This works great for content
that is not displayed differently on multiple pages. Some examples might include
a slider on the home page, a list of links on the home page, etc...

The content list widget can be advantageous on a large site to prevent content
type bloat.

Content list widgets allow making multiple options available for each row in the
content list. For example, you might have a content list widget that you want to
have two options: Link and Media. Each of these would be separate bundles under
the Widget Extends entity type, and each would have whatever fields you want to
collect for that option.

If you want to create a new content list widget, follow these steps.

1. First, create the bundles you want as options to be used in your content list
widget. You can create these bundles with fields by going to Structure ->
Widgets -> Manage Extend Bundles. Keep in mind that whatever bundles you select,
your templates for this widget will have to account for any of these bundles
being used for each row.

2. Create the Widget bundle by going to Structure -> Manage Widget Bundles and
clicking on the add new button. You can call the widget whatever you want, leave
the widget behavior as "Default Widget" under "Advanced Configuration".

3. Add fields to the new Content List widget that you want (similar to a basic
widget). The only field you will want to make sure is in your new Content List
widget is an entity reference field that points to the Widget Extends entity.
Similar to other widgets, you can also add additional fields to the bundle and
they will be sent as variables to your template file, like a text fields, image
fields, etc…

For the form settings on the entityreference field, make sure to select Inline
inline entity form - complex.

4. Next, create template files based on the machine name of the content list
widget you created. At a minimum, you need to define a "default" variation,
which would look like this. [widget_bundle]–default.html.twig.

Review the "contentlist" directory under stacks directory inside
stacks_content_list as an example. The image in the images directory is what is
displayed in the admin for each template variation. Refer to the readme file in
the main module.

5. Clear Drupal cache.