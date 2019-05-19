This Module extends the Viewfield Module by adding a view field filter based on Views Tags.
Sometimes Developers dont want to give the full right to all views inside of a ViewField, so this little helper Module provides a Tag based Filtering for Views.

1. In order to use this Module install it via composer:
<code>composer require drupal/viewfield_tags</code>
2. Select a new Field of type Viewfield.
3. Enter a Tag name or multiple ( separated by commas )
4. Create or Edit a View, under "Edit view Name/Description" on the upper right corner of the view enter the desired Tag name ( Future Views will have a autocomplete for the Tagging )


TODOS:
- Implement a autocomplete for the Tagging Option on the Field Settings
- A better README / Installation and Usage instruction.