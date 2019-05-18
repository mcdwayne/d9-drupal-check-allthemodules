<?php

/**
 * @file
 * Data for the ParadeParagraphsTypesTest class.
 */

namespace Drupal\Tests\parade\Functional;

/**
 * Return the array of paragraphs types defined by parade.
 *
 * @return array
 *   Array of paragraphs types.
 */
function get_parade_paragraphs_types_list() {
  return [
    ['', 'Chart box', 'chart_box', ''],
    ['', 'Header', 'header', ''],
    ['', 'Images', 'images', ''],
    ['', 'Locations', 'locations', ''],
    ['', 'Parallax', 'parallax', ''],
    ['', 'Simple', 'simple', ''],
    ['', 'Social links', 'social_links', ''],
    ['', 'Text & image', 'image_text', ''],
    ['', 'Text box', 'text_box', ''],
    ['', 'Text boxes', 'text_boxes', ''],
  ];
}

/**
 * Get data for the Header paragraphs type.
 *
 * @return array
 *   Paragraphs type data.
 */
function get_parade_header_paragraphs_type_data() {
  $fields = [
    ['Anchor', 'parade_anchor', 'Text (plain)'],
    ['Background', 'parade_background', 'File'],
    ['Call to action', 'parade_call_to_action', 'Link'],
    ['Color scheme', 'parade_color_scheme', 'Entity reference'],
    ['Enable parallax', 'parade_enable_parallax', 'Boolean'],
    ['Lead text', 'parade_lead_text', 'Text (plain, long)'],
    ['Slogan', 'parade_secondary_title', 'Text (plain)'],
    ['Title', 'parade_title', 'Text (plain)'],
  ];

  $views = [
    'default' => [
      ['Content'],
      ['Slogan', '- Hidden -', 'Plain text'],
      ['Title', '- Hidden -', 'Plain text'],
      ['Lead text', '- Hidden -', 'Plain text'],
      ['Call to action', '- Hidden -', 'Link'],
      ['Anchor', 'Above', 'Plain text'],
      ['Background', 'Above', 'URL to file'],
      ['Color scheme', 'Above', 'Label'],
      ['Enable parallax', 'Above', 'Boolean'],
    ],
    // @todo: Preview.
  ];

  $forms = [];

  return [
    'fields' => $fields,
    'views' => $views,
    'forms' => $forms,
  ];
}

/**
 * Get data for the Images paragraphs type.
 *
 * @return array
 *   Paragraphs type data.
 */
function get_parade_images_paragraphs_type_data() {
  $fields = [
    ['Anchor', 'parade_anchor', 'Text (plain)'],
    ['Color scheme', 'parade_color_scheme', 'Entity reference'],
    ['Images', 'parade_images', 'Image'],
    ['Title', 'parade_title', 'Text (plain)'],
  ];

  $views = [
    'default' => [
      ['Content'],
      ['Title', '- Hidden -', 'Plain text'],
      ['Images', '- Hidden -', 'Image'],
      ['Anchor', 'Above', 'Plain text'],
      ['Color scheme', 'Above', 'Label'],
    ],
    'preview' => [
      ['Top'],
      ['Title', 'Inline', 'Plain text'],
      ['First'],
      ['Color scheme', 'Above', 'Label'],
      ['Second'],
      ['Anchor', 'Above', 'Plain text'],
      ['Images', 'Above', 'Image'],
    ],
  ];

  $forms = [];

  return [
    'fields' => $fields,
    'views' => $views,
    'forms' => $forms,
  ];
}

/**
 * Get data for the Locations paragraphs type.
 *
 * @return array
 *   Paragraphs type data.
 */
function get_parade_locations_paragraphs_type_data() {
  $fields = [
    ['Anchor', 'parade_anchor', 'Text (plain)'],
    ['Color scheme', 'parade_color_scheme', 'Entity reference'],
    ['Geofield', 'parade_geofield', 'Geofield'],
    ['Map markers', 'parade_location', 'Text (plain)'],
    ['Text', 'parade_text', 'Text (formatted, long)'],
    ['Title', 'parade_title', 'Text (plain)'],
  ];

  $views = [
    'default' => [
      ['Content'],
      ['Title', '- Hidden -', 'Plain text'],
      ['Geofield', '- Hidden -', 'Leaflet aggregated map'],
      ['Text', '- Hidden -', 'Default'],
      ['Anchor', 'Above', 'Plain text'],
      ['Color scheme', 'Above', 'Label'],
      ['Map markers', 'Above', 'Plain text'],
    ],
    'preview' => [
      ['Top'],
      ['Title', 'Inline', 'Plain text'],
      ['First'],
      ['Color scheme', 'Above', 'Label'],
      ['Second'],
      ['Anchor', 'Inline', 'Plain text'],
      ['Geofield', 'Above', 'Leaflet aggregated map'],
      ['Map markers', 'Above', 'Plain text'],
      ['Text', 'Above', 'Default'],
    ],
  ];

  $forms = [];

  return [
    'fields' => $fields,
    'views' => $views,
    'forms' => $forms,
  ];
}

/**
 * Get data for the Parallax paragraphs type.
 *
 * @return array
 *   Paragraphs type data.
 */
function get_parade_parallax_paragraphs_type_data() {
  $fields = [
    ['Anchor', 'parade_anchor', 'Text (plain)'],
    ['Background', 'parade_background', 'File'],
    ['Color scheme', 'parade_color_scheme', 'Entity reference'],
    ['Enable parallax', 'parade_enable_parallax', 'Boolean'],
    ['Minimum height', 'parade_minimum_height', 'Number (integer)'],
    ['Text', 'parade_text', 'Text (formatted, long)'],
    ['Title', 'parade_title', 'Text (plain)'],
  ];

  $views = [
    'default' => [
      ['Content'],
      ['Title', '- Hidden -', 'Plain text'],
      ['Text', '- Hidden -', 'Default'],
      ['Anchor', 'Above', 'Plain text'],
      ['Background', 'Above', 'URL to file'],
      ['Color scheme', 'Above', 'Label'],
      ['Enable parallax', 'Above', 'Boolean'],
      ['Minimum height', 'Above', 'Unformatted'],
    ],
  ];

  $forms = [];

  return [
    'fields' => $fields,
    'views' => $views,
    'forms' => $forms,
  ];
}

/**
 * Get data for the Simple paragraphs type.
 *
 * @return array
 *   Paragraphs type data.
 */
function get_parade_simple_paragraphs_type_data() {
  $fields = [
    ['Anchor', 'parade_anchor', 'Text (plain)'],
    ['Call to action', 'parade_call_to_action', 'Link'],
    ['Color scheme', 'parade_color_scheme', 'Entity reference'],
    ['Content', 'parade_text', 'Text (formatted, long)'],
    ['Layout', 'parade_layout', 'Entity reference'],
    ['Title', 'parade_title', 'Text (plain)'],
    ['View mode', 'parade_view_mode', 'View Mode Selector'],
  ];

  $views = [
    'default' => [
      ['Content'],
      ['Title', '- Hidden -', 'Plain text'],
      ['Content', '- Hidden -', 'Default'],
      ['Call to action', '- Hidden -', 'Link'],
      ['Anchor', 'Above', 'Plain text'],
      ['Color scheme', 'Above', 'Label'],
      ['Layout', 'Above', 'Label'],
      ['View mode', 'Above', 'Selected view mode name as text'],
    ],
    'inverse' => [
      ['Content'],
      ['Content', '- Hidden -', 'Default'],
      ['Title', '- Hidden -', 'Plain text'],
      ['Call to action', '- Hidden -', 'Link'],
      ['Anchor', 'Above', 'Plain text'],
      ['Color scheme', 'Above', 'Label'],
      ['Layout', 'Above', 'Label'],
      ['View mode', 'Above', 'Selected view mode name as text'],
    ],
    'preview' => [
      ['Top'],
      ['Title', 'Inline', 'Plain text'],
      ['First'],
      ['Layout', 'Above', 'Label'],
      ['Color scheme', 'Above', 'Label'],
      ['Second'],
      ['Anchor', 'Above', 'Plain text'],
      ['Call to action', '- Hidden -', 'Link'],
      ['Content', 'Above', 'Default'],
      ['View mode', 'Above', 'Selected view mode name as text'],
    ],
  ];

  $forms = [];

  return [
    'fields' => $fields,
    'views' => $views,
    'forms' => $forms,
  ];
}

/**
 * Get data for the Social Links paragraphs type.
 *
 * @return array
 *   Paragraphs type data.
 */
function get_parade_social_links_paragraphs_type_data() {
  $fields = [
    ['Anchor', 'parade_anchor', 'Text (plain)'],
    ['Color scheme', 'parade_color_scheme', 'Entity reference'],
    ['Social links', 'parade_social_link', 'Link'],
    ['Title', 'parade_title', 'Text (plain)'],
  ];

  $views = [
    'default' => [
      ['Content'],
      ['Title', '- Hidden -', 'Plain text'],
      ['Social links', '- Visually Hidden -', 'Link'],
      ['Anchor', 'Above', 'Plain text'],
      ['Color scheme', 'Above', 'Label'],
    ],
  ];

  $forms = [];

  return [
    'fields' => $fields,
    'views' => $views,
    'forms' => $forms,
  ];
}

/**
 * Get data for the Text & Image paragraphs type.
 *
 * @return array
 *   Paragraphs type data.
 */
function get_parade_image_text_paragraphs_type_data() {
  $fields = [
    ['Anchor', 'parade_anchor', 'Text (plain)'],
    ['Call to action', 'parade_call_to_action', 'Link'],
    ['Color scheme', 'parade_color_scheme', 'Entity reference'],
    ['Image', 'parade_image', 'Image'],
    ['Layout', 'parade_layout', 'Entity reference'],
    ['Text', 'parade_text', 'Text (formatted, long)'],
    ['Title', 'parade_title', 'Text (plain)'],
    ['View mode', 'parade_view_mode', 'View Mode Selector'],
  ];

  $views = [
    'default' => [
      ['Content'],
      ['Title', '- Hidden -', 'Plain text'],
      ['Image', '- Hidden -', 'Image'],
      ['Text', '- Hidden -', 'Default'],
      ['Call to action', '- Hidden -', 'Link'],
      ['Anchor', 'Above', 'Plain text'],
      ['Color scheme', 'Above', 'Label'],
      ['Layout', 'Above', 'Label'],
      ['View mode', 'Above', 'Selected view mode name as text'],
    ],
    'inverse' => [
      ['Content'],
      ['Title', '- Hidden -', 'Plain text'],
      ['Text', '- Hidden -', 'Default'],
      ['Image', '- Hidden -', 'Image'],
      ['Call to action', '- Hidden -', 'Link'],
      ['Anchor', 'Above', 'Plain text'],
      ['Color scheme', 'Above', 'Label'],
      ['Layout', 'Above', 'Label'],
      ['View mode', 'Above', 'Selected view mode name as text'],
    ],
    'preview' => [
      ['Top'],
      ['Title', 'Inline', 'Plain text'],
      ['First'],
      ['Layout', 'Above', 'Label'],
      ['Color scheme', 'Above', 'Label'],
      ['Second'],
      ['Anchor', 'Above', 'Plain text'],
      ['Call to action', 'Above', 'Call to action'],
      ['Image', 'Above', 'URL to image'],
      ['Text', 'Above', 'Default'],
      ['View mode', 'Above', 'Selected view mode name as text'],
    ],
  ];

  $forms = [];

  return [
    'fields' => $fields,
    'views' => $views,
    'forms' => $forms,
  ];
}

/**
 * Get data for the Text Box paragraphs type.
 *
 * @return array
 *   Paragraphs type data.
 */
function get_parade_text_box_paragraphs_type_data() {
  $fields = [
    ['Call to action', 'parade_call_to_action', 'Link'],
    ['Color scheme', 'parade_color_scheme', 'Entity reference'],
    ['Image', 'parade_image', 'Image'],
    ['Text', 'parade_text', 'Text (formatted, long)'],
    ['Title', 'parade_title', 'Text (plain)'],
  ];

  $views = [
    'default' => [
      ['Content'],
      ['Title', '- Hidden -', 'Plain text'],
      ['Image', '- Hidden -', 'Image'],
      ['Text', '- Hidden -', 'Default'],
      ['Call to action', 'Above', 'Call to action'],
      ['Color scheme', 'Above', 'Label'],
    ],
    'custom' => [
      ['Content'],
      ['Image', '- Hidden -', 'Image'],
      ['Title', '- Hidden -', 'Plain text'],
      ['Text', '- Hidden -', 'Default'],
      ['Call to action', 'Above', 'Call to action'],
      ['Color scheme', 'Above', 'Label'],
    ],
    'preview' => [
      ['Top'],
      ['Title', 'Inline', 'Plain text'],
      ['First'],
      ['Color scheme', 'Above', 'Label'],
      ['Second'],
      ['No field is displayed.'],
      ['Call to action', 'Above', 'Call to action'],
      ['Image', 'Above', 'URL to image'],
      ['Text', 'Above', 'Default'],
    ],
  ];

  $forms = [];

  return [
    'fields' => $fields,
    'views' => $views,
    'forms' => $forms,
  ];
}

/**
 * Get data for the Text Boxes paragraphs type.
 *
 * @return array
 *   Paragraphs type data.
 */
function get_parade_text_boxes_paragraphs_type_data() {
  $fields = [
    ['Anchor', 'parade_anchor', 'Text (plain)'],
    ['Boxes', 'parade_paragraphs', 'Entity reference revisions'],
    ['Boxes per row', 'parade_boxes_per_row', 'Number (integer)'],
    ['Call to action', 'parade_call_to_action', 'Link'],
    ['Color scheme', 'parade_color_scheme', 'Entity reference'],
    ['Description', 'parade_text', 'Text (formatted, long)'],
    ['Layout', 'parade_layout', 'Entity reference'],
    ['Title', 'parade_title', 'Text (plain)'],
    ['View mode', 'parade_view_mode', 'View Mode Selector'],
  ];

  $views = [
    'default' => [
      ['Content'],
      ['Title', '- Hidden -', 'Plain text'],
      ['Description', '- Hidden -', 'Default'],
      ['Boxes', '- Hidden -', 'Rendered entity'],
      ['Call to action', 'Above', 'Call to action'],
      ['Anchor', 'Above', 'Plain text'],
      ['Boxes per row', 'Above', 'Unformatted'],
      ['Color scheme', 'Above', 'Label'],
      ['Layout', 'Above', 'Label'],
      ['View mode', 'Above', 'Selected view mode name as text'],
    ],
    'custom' => [
      ['Content'],
      ['Title', '- Hidden -', 'Plain text'],
      ['Boxes', '- Hidden -', 'Rendered entity'],
      ['Anchor', 'Above', 'Plain text'],
      ['Boxes per row', 'Above', 'Unformatted'],
      ['Call to action', 'Above', 'Call to action'],
      ['Color scheme', 'Above', 'Label'],
      ['Layout', 'Above', 'Label'],
      ['Description', 'Above', 'Default'],
      ['View mode', 'Above', 'Selected view mode name as text'],
    ],
    'preview' => [
      ['Top'],
      ['Title', 'Inline', 'Plain text'],
      ['First'],
      ['Layout', 'Above', 'Label'],
      ['Color scheme', 'Above', 'Label'],
      ['Second'],
      ['Anchor', 'Above', 'Plain text'],
      ['Boxes per row', 'Above', 'Unformatted'],
      ['Call to action', 'Above', 'Call to action'],
      ['Boxes', 'Above', 'Rendered entity'],
      ['Description', 'Above', 'Default'],
      ['View mode', 'Above', 'Selected view mode name as text'],
    ],
  ];

  $forms = [];

  return [
    'fields' => $fields,
    'views' => $views,
    'forms' => $forms,
  ];
}
