#Entity Collector
Collections are sets of entities that are gathered for a specific purpose.
You can create entity collection types at ```admin/structure/collection-types``` and manage the settings. There
will be automatically a field generated for the collection type based on the entity where you want to create the 
collection for.

For example you could create a Entity Collection Type `Media` and add all sorts of media entities to it and create a 
collection. Foreach entity collection type there will be two fields exposed a `add` and a `remove` field. This is done 
by the  [Extra Field](https://www.drupal.org/project/extra_field) module. These fields make it possible to add/remove the 
entities to/from the collection through the interface to you active collection bar (see `Collection Bar` below). 

You can for example create a view mode containing a thumbnail and the add and remove field, so you can create a view 
with a list of entities with that view mode and manage your collection.

A collections can be shared with different users by adding them as a participant to the entity collection.

## Collection Bar
On each page in the collection bar region the active collection is shown. In the block settings at 
`/admin/structure/block/manage/entitycollectionblock` you will be able to select:
 - Entity collection type
    - Which entity collection type should be shown.
 - Entity Collection view Mode
    - Which view mode should be used to render the entities.
    
Besides collecting entities it is also possible to switch between active
collections or create new ones. The collection bar on the bottom of
many pages has the option to trigger this through a modal popup.

Selecting an existing collection in the modal will go through an AJAX
request and will replace the collection bar on the bottom with the
selected collection. While the creation of a new collection will be
a normal page request form submission and will reload the page with
the newly created collection as the active one.

## TODO:
- The modal triggers are currently based on the bootstrap dialogs, this is going to be refactored so it works with the
drupal dialogs.
- Finishing up the download page to download the complete collection as a zip. 