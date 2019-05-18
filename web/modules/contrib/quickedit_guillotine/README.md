### Installation

1) Install module quickedit_guillotine;
2) Setup crop type and image style for image (if they are not exist);
3) Create new view mode for your media image bundle and select format 'image' for image field;
4) Ddd reference to your media bundle in some content type. Format - 'rendered entity';
5) Setup patch to allow contextual links for media entity
`"drupal/media_entity": {
"Add contectual links": "https://www.drupal.org/files/issues/add_contextual_links-2775131-2.patch"
}`
6) Add contextual links to media template like
`<article{{ attributes }}>
   {{ title_suffix.contextual_links }}...`
7) Add wrapper to your image field in 'field-image' template like
`<div{{ attributes.addClass(classes, 'field__item') }}><picture>{{ item.content }}</picture></div>...`