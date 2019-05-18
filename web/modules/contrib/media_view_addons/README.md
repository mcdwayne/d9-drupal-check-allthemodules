# Media View Add-ons Module

Enhances media View and functionality.

Media View Add-ons provides a Views Plugin which adds an additional column to the Media View (/admin/content/media) 
that displays the top level nodes each media image belongs to.

The plugin basically provides an additional "dummy" field to the default media View and makes uses of the media image ID to 
generate a list of links to node edit pages for each media image row.

The module scans all the image fields on your current installation as well as the entity reference revision fields 
(sites commonly use this type to reference paragraphs on nodes). Then, entities referencing media images through these 
fields are retrieved recursively until a top level node entity is found for each initial image. 

A list of node edit links is added to the newly introduced View column. Multiple nodes can reference directly or indirectly 
- through an entity reference revision field - the same media image. In this case, more than one links will be added 
(as operation drop downs) to the new column.

The module can be expanded to include more entity types and a wider range of possible scenarios.

A hook is also offered to alter the links (title and url) this module provides. A possible scenario for that would be a need to 
display additional information in the link text for each top level node (e.g. if domain access module is enabled on the site, 
the hook provided could be used to add node domain information to the link text).

## Requirements

* Media module
* Entity Reference Revisions module

## Configuration

Enable the module as usual.

Edit the media view (/admin/structure/views/view/media/edit/media_page_list) and add a media ID field ("Exclude from display"), 
then add the "Media view add-ons top level node" field (under "Media View Add-ons").

This will provide you with a new column with links to edit the top level node each media image belongs to.
