## INTRODUCTION

The entity content export allows site administrators to export  content based entities. The module utilizes the serializer, so it's possible to expand to different export types with ease. The entity view display modes can be used to structure the exported content to the desired output.

## INSTALLATION

 * Install as usual, see
   https://www.drupal.org/docs/8/extending-drupal-8/installing-contributed-modules-find-import-enable-configure-drupal-8 for further
   information.
 
 * Navigate to `/admin/config/content/entity-content-export/settings`.

 * Select the entity type bundles you want to be able to export to various formats. 
 
 * Update the entity view display (e.g. admin/structure/types/manage/ENTITY_TYPE/display/DISPLAY_MODE) to structure the rendering of the exported content. Use the field formatters to select how field values are rendered. For more granular export options, scroll to the bottom of the display form.
 
 * Navigate to `/admin/content/entity-content-export` and select the desired options, then click export. A batch process will begin, and will prompt a save dialog box when completed.
 
 ## RECOMMEND MODULES
 
 - [CSV Serialization](https://www.drupal.org/project/csv_serialization)
