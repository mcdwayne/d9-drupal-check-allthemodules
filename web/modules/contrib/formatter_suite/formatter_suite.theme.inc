<?php

/**
 * @file
 * Handles the module's theme templates.
 *
 * @ingroup formatter_suite
 */

use Drupal\image\Entity\ImageStyle;

/**
 * Implements hook_theme().
 */
function formatter_suite_theme() {
  $themes = [];

  //
  // Field list theme.
  // -----------------
  // Copy the generic field theme to be a field list.
  $commonThemes = drupal_common_theme();
  $themes['formatter_suite_field_list'] = $commonThemes['field'];

  //
  // General image theme.
  // --------------------
  // The general image formatter swaps out the Image module's
  // 'image_formatter' for its own that uses a slightly better theme
  // that includes URL attributes.
  $themes['formatter_suite_general_image_formatter'] = [
    'variables' => [
      'item'            => NULL,
      'item_attributes' => NULL,
      'url'             => NULL,
      'attributes'      => NULL,
      'image_style'     => NULL,
    ],
  ];

  //
  // Image embed themes.
  // -------------------
  // The image embedding formatter needs to define three themes that
  // mimic standard Drupal themes when the formatter is used:
  //
  // - 'formatter_suite_image' mimics the Drupal core 'image' theme.
  //
  // - 'formatter_suite_image_style' mimics the image module's
  //   'image_style' theme.
  //
  // - 'formatter_suite_image_formatter' mimics the image module's
  //   'image_formatter' theme.
  //
  // This module defines TWIG templates that are copies of the original
  // themes so that we get the same layout.
  //
  // Since we want to mimic the original themes, we need the same set of
  // variables. These variable definitions are copied from the originals,
  // then augmented with additional variables needed for embedding.
  //
  // Get theme descriptions from Drupal core and the image module.
  $commonThemes = drupal_common_theme();
  $imageModuleThemes = image_theme();

  // Image embed themes.
  //
  // Copy theme variable definitions, then add variables for the minimum
  // and maximum image embed width.
  $themes['formatter_suite_image_embed'] =
    $commonThemes['image'];
  $themes['formatter_suite_image_embed_style'] =
    $imageModuleThemes['image_style'];
  $themes['formatter_suite_image_embed_formatter'] =
    $imageModuleThemes['image_formatter'];

  foreach (['image_embed', 'image_embed_style', 'image_embed_formatter'] as $key) {
    $th = 'formatter_suite_' . $key;
    $themes[$th]['variables']['maximumEmbedWidth'] = 0;
    $themes[$th]['variables']['maximumEmbedHeight'] = 0;
  }

  //
  // Patch image formatter-based themes.
  // -----------------------------------
  // Above we've copied the Image module's 'image_formatter' theme
  // description. Unfortunately, this includes a 'file' field that directs
  // the theme manager to include the Image module's 'image.field.inc'
  // where the template's preprocessor function lives. The theme manager
  // always assumes that the path in a 'file' field is relative to the
  // module involved, which is us, not the Image module. This causes the
  // Image module's 'image.field.inc' to not be found, which means its
  // preprocess function isn't found.
  //
  // The fix is this:
  //
  // 1. Remove the 'file' field from the theme description.
  // 2. Handle template preprocessing ourselves.
  unset($themes['formatter_suite_image_embed_formatter']['file']);

  return $themes;
}

/**
 * Prepares variables for field list template.
 *
 * This template is used by this module's field list formatters and the
 * the 'formatter_suite_field_list' template. The template's job is
 * to format a list of fields using a selected list style and with
 * selected list separators between the items.
 *
 * Template processing uses the core field
 * output image width and height and uses them in the output variables.
 *
 * @param array $variables
 *   Returns an associative array of theme variables.
 */
function template_preprocess_formatter_suite_field_list(
  array &$variables,
  $hook) {

  $element = $variables['element'];
  template_preprocess_field($variables, $hook);
  $variables['list_style'] = $element['#list_style'];
  $variables['list_separator'] = $element['#list_separator'];
}

/**
 * Prepares variables for image embed formatter template.
 *
 * This template is used by this module's ImageInlineFormatter to enable
 * embedding images directly on a page by using a data URL. Processing
 * swaps out the image module's 'image_formatter' theme for this module's
 * 'formatter_suite_image_formatter'. This template preprocessor then
 * sets up the image in a way nearly identical to the parent class, but
 * with support for data URL handling.
 *
 * Incoming variables:
 *   - 'item':  an ImageItem object.
 *   - 'item_attributes':  (optional) an associative array of html attributes
 *     to be placed in the <img> tag.
 *   - 'image_style':  (optional) the name of an image style.
 *   - 'url':  (optional) a \Drupal\Core\Url object for linking the image to
 *     another entity or file.
 *   - 'maximumEmbedWidth': (optional) the maximum width for embedding
 *     as a data URL.
 *   - 'maximumEmbedHEight': (optional) the maximum height for embedding
 *     as a data URL.
 *
 * Outgoing variables include the above items, plus:
 *   - 'image':  a theme-ready image description.
 *     - '#theme':  the name of the theme to use.
 *     - '#attributes':  a copy of 'item_attributes'.
 *     - '#width':  the original image width.
 *     - '#height':  the original image height.
 *     - '#alt':  the image's alternative text.
 *     - '#style_name':  (optional) the name of an image style.
 *     - '#title':  (optional) an image title.
 *     - '#uri':  the URI of the item or file.
 *     - '#maximumEmbedWidth': (optional) a copy of 'maximumEmbedWidth'.
 *     - '#maximumEmbedHeight': (optional) a copy of 'maximumEmbedHeight'.
 *
 * The theme name will be one of:
 *   - 'image' if the image does not have a style.
 *   - 'formatter_suite_image_style' if the image does have a style.
 *
 * Note that the 'width' and 'height' are for the original image, not
 * for the styled image.
 *
 * Compared to the base class's template_preprocess_image_formatter(),
 * this function primarily insures that the maximum embed width/height
 * variables are passed onwards to the customized theme templates that
 * actually do the embedding.
 *
 * Default template: formatter-suite-image-embed-formatter.html.twig.
 *
 * @param array $variables
 *   Returns an associative array of theme variables.
 */
function template_preprocess_formatter_suite_image_embed_formatter(
  array &$variables) {

  // The following code is based upon the image module's
  // template_preprocess_image_formatter() function to process the
  // image_formatter template.
  //
  // Changes include redirecting to this module's image embed templates,
  // instead of that for the image module, and adding the maximum embed
  // dimensions.
  if (empty($variables['image_style']) === FALSE) {
    // An image style is defined. Set up an image that redirects to
    // our image_style template. Forward the name of the style.
    $variables['image'] = [
      '#theme'      => 'formatter_suite_image_embed_style',
      '#style_name' => $variables['image_style'],
    ];
  }
  else {
    // No image style is defined. Use the stock Drupal core template.
    $variables['image'] = [
      '#theme' => 'formatter_suite_image_embed',
    ];
  }

  // Copy down the item's attributes.
  $variables['image']['#attributes'] = $variables['item_attributes'];
  if (isset($variables['maximumEmbedWidth']) === TRUE) {
    $variables['image']['#maximumEmbedWidth'] = $variables['maximumEmbedWidth'];
  }

  if (isset($variables['maximumEmbedHeight']) === TRUE) {
    $variables['image']['#maximumEmbedHeight'] = $variables['maximumEmbedHeight'];
  }

  // Get the item being formatted.
  $item = $variables['item'];

  // Do not output an empty 'title' attribute.
  if (mb_strlen($item->title) !== 0) {
    $variables['image']['#title'] = $item->title;
  }

  // Get the URI.
  if (($entity = $item->entity) !== NULL &&
      empty($item->uri) === TRUE) {
    $variables['image']['#uri'] = $entity->getFileUri();
  }
  else {
    $variables['image']['#uri'] = $item->uri;
  }

  // Add basic image attributes.
  foreach (['width', 'height', 'alt'] as $key) {
    $variables['image']["#$key"] = $item->$key;
  }
}

/**
 * Prepares variables for image embed template.
 *
 * This template is used by this module's ImageInlineFormatter by way of
 * the 'formatter_suite_image_embed_formatter' template. The template's job is
 * to compare the image width and height to any limits imposed by the
 * formatter, and then optionally embed the styled image as a data URL
 * instead of as a file URL.
 *
 * Incoming variables:
 *   - 'theme':  the name of the theme to use.
 *   - 'attributes':  a copy of 'item_attributes'.
 *   - 'width':  the original image width.
 *   - 'height':  the original image height.
 *   - 'alt':  the image's alternative text.
 *   - 'title':  (optional) an image title.
 *   - 'uri':  the URI of the item or file.
 *   - 'maximumEmbedWidth': (optional) a copy of 'maximumEmbedWidth'.
 *   - 'maximumEmbedHeight': (optional) a copy of 'maximumEmbedHeight'.
 *
 * Outgoing variables include the above plus:
 *   - 'attributes':  a copy of 'attributes'.
 *   - 'width':  the styled image width.
 *   - 'height':  the styled image height.
 *   - 'alt':  the image's alternative text.
 *   - 'title':  (optional) an image title.
 *   - 'sizes':  (optional) a list of sizes for responsive images, if the
 *     image could not be embedded as a data URL.
 *   - 'srcset':  (optional) a list of responsive images, if the image could
 *     note be embedded as a data URL.
 *   - '#uri':  (optional) the URI of the original image if it could not
 *     be embedded as a data URL.
 *
 * Compared to the Drupal core template_preprocess_image(), this
 * function checks if a image is within the maximum embed width/height
 * limits and attempts to load the image to create a data URL. If the image
 * cannot be found, a file URL is used.
 *
 * Default template: formatter-suite-image-embed-style.html.twig.
 *
 * @param array $variables
 *   Returns an associative array of theme variables.
 */
function template_preprocess_formatter_suite_image_embed(
  array &$variables) {

  // Invoke Drupal core to process variables. This sets up variables with
  // the image width, height, title, etc.
  template_preprocess_image($variables);

  // Check that the image is within our embedding bounds.
  $maximumEmbedWidth = (int) $variables['maximumEmbedWidth'];
  $maximumEmbedHeight = (int) $variables['maximumEmbedHeight'];
  $width = (int) $variables['width'];
  $height = (int) $variables['height'];

  if (($maximumEmbedWidth > 0 && $width > $maximumEmbedWidth) ||
      ($maximumEmbedHeight > 0 && $height > $maximumEmbedHeight)) {
    // The styled image is too large for embedding. The parent class has
    // already set things up to use a file URL. Leave things that way.
    return;
  }

  // Get the real path to the image.
  if (empty($variables['uri']) === FALSE) {
    $originalUri = $variables['uri'];
  }
  elseif (empty($variables['src']) === FALSE) {
    $originalUri = $variables['src'];
  }
  else {
    // Cannot find URI for image. Fall back to whatever the original theme
    // was going to do.
    return;
  }

  $fileSystem = \Drupal::service('file_system');
  $path = $fileSystem->realpath($originalUri);
  if ($path === FALSE || @file_exists($path) === FALSE) {
    // The file does not exist. Curious. Fall back to whatever Drupal core
    // set up for the template.
    return;
  }

  // Get the file's MIME type. We need this for the data URL.
  $mimeType = \Drupal::service('file.mime_type.guesser')->guess($path);

  // Read the file's raw bytes and base64 encode them as a data URL.
  $data = file_get_contents($path);
  $dataUrl = 'data:' . $mimeType . ';base64,' . base64_encode($data);

  // Set the image's source to be the data URL. To prevent this from
  // being overwritten with the image's URI, delete that URI from the
  // variables. Delete the srcset and sizes too, if present.
  $variables['attributes']['src'] = $dataUrl;
  unset($variables['uri']);
  unset($variables['srcset']);
  unset($variables['sizes']);
}

/**
 * Prepares variables for image embed style template.
 *
 * This template is used by this module's ImageEmbedDataFormatter by way of
 * the 'formatter_suite_image_embed_formatter' template. The template's job is
 * to look up the image style, compare the styled image width and height to
 * any limits imposed by the formatter, and then optionally embed the
 * styled image as a data URL instead of as a file URL.
 *
 * Incoming variables:
 *   - 'theme':  the name of the theme to use.
 *   - 'attributes':  a copy of 'item_attributes'.
 *   - 'width':  the original image width.
 *   - 'height':  the original image height.
 *   - 'alt':  the image's alternative text.
 *   - 'style_name':  (optional) the name of an image style.
 *   - 'title':  (optional) an image title.
 *   - 'uri':  the URI of the item or file.
 *   - 'maximumEmbedWidth': (optional) a copy of 'maximumEmbedWidth'.
 *   - 'maximumEmbedHeight': (optional) a copy of 'maximumEmbedHeight'.
 *
 * Outgoing variables include the above plus:
 *   - 'image':  a theme-ready image description.
 *     - '#theme':  the name of the theme to use (always 'image').
 *     - '#attributes':  a copy of 'attributes'.
 *     - '#width':  the styled image width.
 *     - '#height':  the styled image height.
 *     - '#alt':  the image's alternative text.
 *     - '#style_name':  (optional) the name of an image style.
 *     - '#title':  (optional) an image title.
 *     - '#uri':  (optional) the URI of the styled or original image if it
 *       could not be embedded as a data URL.
 *
 * Template processing loads the named style and computes the style's
 * output image width and height and uses them in the output variables.
 *
 * Compared to the parent class's template_preprocess_image_style(), this
 * function checks if a styled image is within the maximum embed width/height
 * limits and attempts to load the image to create a data URL. If the image
 * is not available yet (such as a styled image that hasn't been created and
 * cached yet), a file URL is used.
 *
 * Default template: formatter-suite-image-embed-style.html.twig.
 *
 * @param array $variables
 *   Returns an associative array of theme variables.
 */
function template_preprocess_formatter_suite_image_embed_style(
  array &$variables) {

  // Invoke the image module to process variables. This sets up the 'image'
  // variable with the image's styled width and height, style, title, and
  // URL for links. It also includes a URI for the source image.
  template_preprocess_image_style($variables);

  // Check that the styled image is within our embedding bounds.
  $maximumEmbedWidth = (int) $variables['maximumEmbedWidth'];
  $maximumEmbedHeight = (int) $variables['maximumEmbedHeight'];
  $width = (int) $variables['image']['#width'];
  $height = (int) $variables['image']['#height'];

  if (($maximumEmbedWidth > 0 && $width > $maximumEmbedWidth) ||
      ($maximumEmbedHeight > 0 && $height > $maximumEmbedHeight)) {
    // The styled image is too large for embedding. The parent class has
    // already set things up to use a file URL. Leave things that way.
    return;
  }

  // Get the style and validate.
  $sourceUri = $variables['uri'];
  $style = ImageStyle::load($variables['style_name']);
  if ($style === NULL || $style->supportsUri($sourceUri) === FALSE) {
    // Either the style won't load or the image at hand is not supported
    // by the style (or the underlying image toolkit). In this case, there
    // is no styled image and the parent class has already set up theme
    // variables to point to the original image. Leave things that way.
    return;
  }

  // Get the URI of the styled image.
  $styledUri = $style->buildUri($sourceUri);

  // Get the real path to the styled image.
  $fileSystem = \Drupal::service('file_system');
  $path = $fileSystem->realpath($styledUri);
  if ($path === FALSE || @file_exists($path) === FALSE) {
    // The file does not exist yet. The image module only creates and caches
    // styled images on the first use, so this image hasn't been used yet.
    //
    // The parent class has already set things up to use a file URL that will
    // trigger creation of the styled image. Leave things that way.
    //
    // On the NEXT use of this image, the file will exist and we can
    // embed it.
    return;
  }

  // Get the file's MIME type. We need this for the data URL.
  //
  // Styling can change the file format of the image, which changes its
  // MIME type. So we need to query the MIME type of the styled image
  // instead of using whatever is saved in the image object.
  $mimeType = \Drupal::service('file.mime_type.guesser')->guess($path);

  // Read the file's raw bytes and base64 encode them as a data URL.
  $data = file_get_contents($path);
  $dataUrl = 'data:' . $mimeType . ';base64,' . base64_encode($data);

  // Set the image's source to be the data URL. To prevent this from
  // being overwritten with the image's URI, delete that URI from the
  // variables.
  $variables['image']['#attributes']['src'] = $dataUrl;
  unset($variables['image']['#uri']);
}

/**
 * Prepares variables for general image formatter template.
 *
 * This template is used by this module's GeneralImageFormatter to add
 * captions and link attributes to images. Processing swaps out the
 * image module's 'image_formatter' theme for this module's
 * 'formatter_suite_general_image_formatter'. This template preprocessor then
 * sets up the image in a way nearly identical to the parent class, but
 * with URL attributes.
 *
 * Incoming variables:
 *   - 'item':  an ImageItem object.
 *   - 'item_attributes':  (optional) an associative array of html attributes
 *     to be placed in the <img> tag.
 *   - 'image_style':  (optional) the name of an image style.
 *   - 'url':  (optional) a \Drupal\Core\Url object for linking the image to
 *     another entity or file.
 *   - 'url_attributes': (optional) attributes for the image link, if any.
 *
 * Outgoing variables include the above items, plus:
 *   - 'image':  a theme-ready image description.
 *     - '#theme':  the name of the theme to use.
 *     - '#attributes':  a copy of 'item_attributes'.
 *     - '#width':  the original image width.
 *     - '#height':  the original image height.
 *     - '#alt':  the image's alternative text.
 *     - '#style_name':  (optional) the name of an image style.
 *     - '#title':  (optional) an image title.
 *     - '#uri':  the URI of the item or file.
 *     - '#maximumEmbedWidth': (optional) a copy of 'maximumEmbedWidth'.
 *     - '#maximumEmbedHeight': (optional) a copy of 'maximumEmbedHeight'.
 *
 * The theme name will be one of:
 *   - 'image' if the image does not have a style.
 *   - 'image_style' if the image does have a style.
 *
 * Note that the 'width' and 'height' are for the original image, not
 * for the styled image.
 *
 * Compared to the base class's template_preprocess_image_formatter(),
 * this function just adds URL attributes.
 *
 * Default template: formatter-suite-general-image-formatter.html.twig.
 *
 * @param array $variables
 *   Returns an associative array of theme variables.
 */
function template_preprocess_formatter_suite_general_image_formatter(
  array &$variables) {

  //
  // Mimic image module.
  // -------------------
  // Repeat the image module's template_preprocess_image_formatter() function.
  if (empty($variables['image_style']) === FALSE) {
    // An image style is defined.
    $variables['image'] = [
      '#theme'      => 'image_style',
      '#style_name' => $variables['image_style'],
    ];
  }
  else {
    // No image style is defined. Use the stock Drupal core template.
    $variables['image'] = [
      '#theme' => 'image',
    ];
  }

  // Copy down the item's attributes.
  $variables['image']['#attributes'] = $variables['item_attributes'];

  // Get the item being formatted.
  $item = $variables['item'];

  // Do not output an empty 'title' attribute.
  if (mb_strlen($item->title) !== 0) {
    $variables['image']['#title'] = $item->title;
  }

  // Get the URI.
  if (($entity = $item->entity) !== NULL &&
      empty($item->uri) === TRUE) {
    $variables['image']['#uri'] = $entity->getFileUri();
  }
  else {
    $variables['image']['#uri'] = $item->uri;
  }

  // Add basic image attributes.
  foreach (['width', 'height', 'alt'] as $key) {
    $variables['image']["#$key"] = $item->$key;
  }

}
