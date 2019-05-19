SORTABLEVIEWS

This is an alternative for the popular DraggableViews module.
The difference lies in that this module stores weights directly
on entity fields.

Here is how it works:

1. Create a view of any entity and have its format be any of
"Sortable HTML list", "Sortable Unformatted list" or
"Sortable table". Make sure the entity type has a spare integer
field or base field to store the weight.

2. In the view format settings, specify such field for storing
weight.

3. Add the field "Sortableviews: Drag and drop handle." to the
view. This is the actual "handle" users will use to perform the
drag and drop.

4. Add your weight field as a sort criteria as well. It can be
either in asc or desc fashion.

5. Finally, add the "Save Sortableviews changes" handler to
either your view header of footer. This is the button users will
use to save changes and will only appear when there are changes
to be saved.

6. Your view should now be sortable.

Be aware that the sorting process will always overwrite whatever
weight an entity had. Also, weight conflicts may occur if using
multiple sortableviews for the same entity type and bundle.
