# Contributing

* Issues should be filed at http://drupal.org/project/issues/reference_swiper
* Pull requests can be made against https://github.com/

## Development

The module is providing a template which may be overridden for customizations.

In order to access a Swiper's instance in JS you will have to

- Make sure your module's library (JS) is depending on the library
reference_swiper/reference_swiper.field
- Figure out the instance key by concatenating the following information with
dots (which results in a string like 'node.article.field_machine_name.full'):
  1. Entity type of the entity that is rendering the field
  2. Bundle machine name of the entity that is rendering the field
  3. The field's machine name
  4. The view mode used for rendering
- Access the Swiper instance by using the instance key in the
Drupal.referenceSwiper.swiperInstances object, for example
Drupal.referenceSwiper.swiperInstances['node.article.field_machine_name.full']
