# Rokka: Drupal integration Module

This module integrates [Rokka.io](https://rokka.io) with Drupal: after setting up your credentials the module allows to:

 - Automatically upload images from fields to Rokka by using the `rokka://` stream wrapper
 - Synchronize Drupal's Image Styles to Rokka's ImageStacks
 - Display images from Rokka service



### Installation

```

composer require drupal/rokka
cd web
drush en rokka --yes
drush cset rokka.settings organization_name ${ROKKA_ORG} --yes
drush cset rokka.settings api_key    ${ROKKA_KEY} --yes
drush cset rokka.settings is_enabled true  --yes
drush cset field.storage.node.field_image settings.uri_scheme rokka  --yes
drush cset editor.editor.basic_html image_upload.scheme rokka --yes
drush cset editor.editor.full_html image_upload.scheme rokka --yes
drush cset image.settings preview_image 'rokka://rokka_default_image.jpg' --yes
# set stack prefix
```

### subjectarea support

```
composer require focal_point
cd web
drush en focal_point --yes
```