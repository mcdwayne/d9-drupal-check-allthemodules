[![Build Status](https://travis-ci.org/brainsum/filefield_sources_jsonapi.svg?branch=8.x-1.x)](https://travis-ci.org/brainsum/filefield_sources_jsonapi)


CONTENTS OF THIS FILE
---------------------
   
 * Introduction
 * Restrictions
 * Installation
 * Configuration
 * Examples


INTRODUCTION
------------

Defines 'JSON API remote URL' file field source.


RESTRICTIONS
------------

Widget/browser doesn't support multiple selecting. This means: You can set the
cardinality to more than 1, but you can select remote images by one.


INSTALLATION
------------
 
Install as you would normally install a contributed Drupal module. Visit:
https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
for further information.

  
CONFIGURATION
-------------

- Enable 'JSON API remote URL' on form display for image field widget.
- Configure JSON API form widget settings.
- Add TML Rest API basic auth credentials to your settings.php:

      /**
       * Filefield sources JSON API browser credentials settings.
       */
      $config['filefield_sources_jsonapi']['username'] = 'USERNAME';
      $config['filefield_sources_jsonapi']['password'] = 'PASSWORD';
      
## Manage JSON API settings
Configuration » Web services » JSON API file field sources
- Label/machine name
  - Label for the JSON API file field sources - selectable at field widget
  settings.
- JSON Api URL
   - request URL, e.g. example.com/jsonapi/media/image
- Params
  - JSON query params per line in key|value format for getting/filtering all
  needed data.
- URL attribute path
  - This is used as remote image url.
- Thumbnail URL attribute path
  - Displayed in modal browser. On empty the 'URL attribute path' will be used.
- Alt attribute path
  - If alt field is enabled on the image field, this value will be set as
  default value after selection.
- Title attribute path
  - Displayed in the lister under image.
  - Image field: if title field is enabled, this value will be set as default
  value after selection.
  - File field: ff description field is enabled, this value will be set as
  default value after selection.
- Sorting option list
  - Option list per line in key|label format.
- Search filter attribute name
- Items to display
  - Item number per page.

## Widget settings
- JSON API settings
  - enable defined JSON API settings/sources.
- Image style
  - Transform remote images to selected image style before saving it.
- Modal window width
  - Modal window initial width.
  
## Info, requirements
- URLs (URL, Thumbnail URL) must be relative to the remote server, shouldn't
 contains domain/base url. Base url is parsed from 'JSON API URL'.
- Sorting: You can add multiple sorting, e.g. 

      name,-created|Name

- Attribute path to 'data' property - if the needed information is in the 'data'
 property of the response, e.g.:

      data->attribute->title

- Attribute path to 'included' property - if the needed information is coming
from relationship, e.g.: from field_image field, than you have to include it as
request params:
  
      include|field_image
   
   and getting data (filename from referenced image):

      data->relationships->field_image->included->attributes->filename


EXAMPLES
--------

#### 1. Getting files from media image entities, field_image field

We have media image entities ('image' bundle). Image (file) is stored in
field_image (core image field type). We would like to get all image urls for
published media image, searching in media name, sorting by media name
(ascending/alphabetic) and by created date (descending).

    - Api URL: example.com/media/image
    - Params:
        - include|field_image
        - fields[file--file]|url
        - fields[media--image]|name,field_image
        - filter[statusFilter][condition][path]|status
        - filter[statusFilter][condition][operator]|=
        - filter[statusFilter][condition][value]|1
    - URL attribute path: data->relationships->field_image->included->attributes->url
    - Thumbnail URL attribute path:
    - Alt attribute path: data->relationships->field_image->data->meta->alt
    - Title attribute path: data->attributes->name
    - Sorting option list:
      - -created|Newest first
      - name|By name
    - Search filter attribute name: field_category.name

#### 2. Getting images from managed files:

We would like to get all image (drupal managed files) file urls, searching in
file name, sorting by created date (descending).

    - Api URL: example.com/file/file
    - Params:
        - fields[file--file]|filename,url
        - filter[mimeFilter][condition][path]|filemime
        - filter[mimeFilter][condition][operator]|CONTAINS
        - filter[mimeFilter][condition][value]|image/
    - URL attribute path: data->attributes->url
    - Thumbnail URL attribute path:
    - Alt attribute path: data->attributes->filename
    - Title attribute path: data->attributes->filename
    - Sorting option list:
        - -created|Newest first
    - Search filter attribute name: filename
    
#### 3. Sorting

Sorting by created date (DESC) and name together, using 'Newest first' label:

    -created,name|Newest first
    
#### 4. Searching in media image bundle and in taxonomy term

First, we have to include referenced taxonomy using include param:

    include|field_category

Now we can add it to search field:

    name,field_category.name

Multiple fields are grouped with 'OR' conjunction. 

#### 5. Thumbnail in browser
It's better to use thumbnail size in lister instead of rendering original
images - could be unnecessary big for listing (slower rendering = worse UX).

##### 5.1. Thumbnail in browser - solution #1
You can use Consumer Image Styles module.
https://www.drupal.org/project/consumer_image_styles

##### 5.2. Thumbnail in browser - solution #2
To provide custom URL for thumbnail you need develop on JSON server side. One
possible solution is to add thumbnail_url computed field for every file entities
of image type via hook_entity_base_field_info() in your custom module - based on
json_api's 'Download URL' defined with jsonapi_entity_base_field_info():

    use Drupal\Core\Field\BaseFieldDefinition;
    use Drupal\Core\Entity\EntityTypeInterface;

    /**
     * Implements hook_entity_base_field_info().
     *
     * Provide thumbnail_url for json api - The relative image style url of the
     * image uri.
     */
    function [MODULE_NAME]_entity_base_field_info(EntityTypeInterface $entity_type) {
      $fields = [];
      if ($entity_type->id() === 'file') {
        $fields['thumbnail_url'] = BaseFieldDefinition::create('string')
          ->setLabel(t('Thumbnail image style URL'))
          ->setDescription(t('The download URL of the thumbnail image style of the image.'))
          ->setComputed(TRUE)
          ->setQueryable(FALSE)
          ->setClass('\Drupal\[MODULE_NAME]\Field\ThumbnailJsonApiDownloadUrl');
      }
      return $fields;
    }

Then you have define specified ThumbnailJsonApiDownloadUrl class at:

    src/Field/ThumbnailJsonApiDownloadUrl.php

with content:

    <?php

    namespace Drupal\tieto_media_library\Field;

    use Drupal\Core\Field\FieldItemList;
    use Drupal\Core\Session\AccountInterface;
    use Drupal\image\Entity\ImageStyle;

    /**
     * Field definition to provide relative image style url for file entities.
     *
     * For 'image/*' filemime return relative image style url of the uri.
     */
    class ImageStyleDownloadUrl extends FieldItemList {

      const IMAGE_STYLE = 'medium';

      /**
       * Creates a relative thumbnail image style URL from file's URI.
       *
       * @param string $uri
       *   The URI to transform.
       *
       * @return string
       *   The transformed relative URL.
       */
      protected function fileCreateThumbnailUrl($uri) {
        $style = ImageStyle::load(self::IMAGE_STYLE);
        $url = $style->buildUrl($uri);
        return file_url_transform_relative(file_create_url($url));
      }

      /**
       * {@inheritdoc}
       */
      public function getValue($include_computed = FALSE) {
        $this->initList();

        return parent::getValue($include_computed);
      }

      /**
       * {@inheritdoc}
       */
      public function access($operation = 'view', AccountInterface $account = NULL, $return_as_object = FALSE) {
        return $this->getEntity()
          ->get('uri')
          ->access($operation, $account, $return_as_object);
      }

      /**
       * {@inheritdoc}
       */
      public function isEmpty() {
        return $this->getEntity()->get('uri')->isEmpty();
      }

      /**
       * {@inheritdoc}
       */
      public function getIterator() {
        $this->initList();

        return parent::getIterator();
      }

      /**
       * {@inheritdoc}
       */
      public function get($index) {
        $this->initList();

        return parent::get($index);
      }

      /**
       * Initialize the internal field list with the modified items.
       */
      protected function initList() {
        if ($this->list) {
          return;
        }
        $url_list = [];
        foreach ($this->getEntity()->get('uri') as $delta => $uri_item) {
          if (preg_match('/image/', $this->getEntity()->get('filemime')[$delta]->value)) {
            $path = $this->fileCreateThumbnailUrl($uri_item->value);
            $url_list[$delta] = $this->createItem($delta, $path);
          }
        }
        $this->list = $url_list;
      }

    }

After that you can set for 'Thumbnail URL attribute path':
- for files (in examples #2 Getting images from managed files):

      data->attributes->thumbnail_url

- for media images (in examples #1 Getting files from media image entities,
 field_image field):

      data->relationships->field_image->included->attributes->thumbnail_url
