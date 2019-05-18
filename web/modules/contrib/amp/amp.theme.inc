<?php

/**
 * @file
 * Preprocessors and helper functions to make theming easier.
 */

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;

/**
 * Prepares variables for AMP Views Carousel templates.
 *
 * Default template: amp-views-carousel.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - view: A View object.
 */
function template_preprocess_amp_views_carousel(&$variables) {
  $view = $variables['view'];

  // If in a Views live preview, don't try to render the carousel.
  if (!empty($view->live_preview)) {
    $variables['theme_hook_original'] = 'views_view_unformatted';
    template_preprocess_views_view_unformatted($variables);
    return;
  }

  $options = $view->style_plugin->options;
  $handler = $view->style_plugin;

  // Fetch wrapper classes from handler options.
  if ($handler->options['wrapper_class']) {
    $wrapper_class = explode(' ', $handler->options['wrapper_class']);
    $variables['attributes']['class'] = array_map('\Drupal\Component\Utility\Html::cleanCssIdentifier', $wrapper_class);
  }

  $layout = $handler->getSetting('layout');
  $width = $handler->validWidth($handler->getSetting('width'), $layout);
  $height = $handler->validHeight($handler->getSetting('height'), $layout);

  $attributes['type'] = $handler->getSetting('type');
  $attributes['layout'] = $layout;
  $attributes['width'] = $width;
  $attributes['height'] = $height;
  $attributes['controls'] = $handler->getSetting('controls');
  $attributes['loop'] = $handler->getSetting('loop');
  $attributes = array_filter($attributes);

  $variables['carousel']['attributes'] = new Attribute($attributes);

  //$variables['carousel']['#theme'] = 'amp_image_carousel';
  $variables['#attached']['library'] = $handler->getLibraries();

  template_preprocess_views_view_unformatted($variables);

}

/**
 * Prepares variables for AMP Sidebar Element.
 *
 * Default template: amp-sidebar.html.twig.
 *
 * @param array $variables
 * An associative array containing:
 * - #id: The id of the sidebar (used by the toggle and close buttons).
 * - #tabindex: The number of the tabindex.
 * - #attributes: Attributes for the container.
 * - #content_attributes: Attributes for the close button.
 */
function template_preprocess_amp_sidebar(&$variables) {
  $element = $variables['element'];

  // Ensure #attributes are set.
  $element += ['#attributes' => [], '#content_attributes' => []];

  $variables['attributes'] = $element['#attributes'];
  $variables['content_attributes'] = $element['#content_attributes'];
  $variables['tabindex'] = $element['#tabindex'];
  $variables['id'] = $element['#id'];
  $variables['children'] = $element['#children'];

}

/**
 * Prepares variables for AMP Social Post Element.
 *
 * @param array $variables
 * An associative array containing:
 * - #url: The url of the post.
 * - #provider: The social post provider, deduced from url.
 * - #placeholder: Placeholder text (used by Twitter).
 * - #attributes: Attributes for the container.
 *   - layout: The layout of the element.
 *   - height: The height of the element.
 *   - width: The width of the element.
 *   - data-embed-as: Embed as post or video (used by Facebook).
 *   - data-align-center: Center align or not (used by Facebook).
 */
function template_preprocess_amp_social_post(&$variables) {
  $element = $variables['element'];

  // Ensure #attributes are set.
  $element += ['#attributes' => []];

  $variables['attributes'] = $element['#attributes'];
  $variables['url'] = $element['#url'];
  $variables['provider'] = $element['#provider'];
  $variables['placeholder'] = $element['#placeholder'];
}
