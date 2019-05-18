<?php
/**
 * @file
 * Defines the API functions provided by the auto_retina module.
 *
 * @ingroup auto_retina
 * @{
 */

/**
 * Implements HOOK_auto_retina_create_derivative_alter().
 *
 * Allow modules to modify ANY IMAGE STYLE before the derivative is created.
 *
 * @param  array &$style
 *     An additional element is added:
 *     - auto_retina
 *     - suffix string The suffix used e.g. '@2x'
 *     - multiplier int|float The numeric part of the suffix for math, e.g. 2.
 * @param string $source
 *   Path of the source file.
 * @param string $destination
 *   Path or URI of the destination file.
 *
 * @see image_style_create_derivative().
 */
function HOOK_auto_retina_create_derivative_alter(array &$style, &$source, &$destination) {

}

/**
 * Implements HOOK_auto_retina_image_style_deliver_alter().
 *
 * This hook fires when an image derivative is first created and then delivered
 * for the first time. It allows module authors to alter the http headers that
 * are sent with that image.  Once the image is created as a file, this hook no
 * longer fires and headers will have to be sent using other methods
 * (.htaccess, etc.).
 *
 * @link https://www.drupal.org/project/auto_retina/issues/2888881#comment-12429687
 *
 * @param array &$headers
 *   The HTTP headers that will be sent with the created derivative image.
 * @param string $uri
 *   The image URI of the retina image.
 * @param string $original_image_uri
 *   The original source image uri.
 * @param array $style
 *   The image style used to generate this.
 */
function HOOK_auto_retina_image_style_deliver_alter(array &$headers, $uri, $original_image_uri, array $style) {

}

/**
 * Implements HOOK_auto_retina_effect_EFFECT_NAME_alter().
 *
 * Allow modules to alter a specific effect when processing a retina derivative.
 */
function HOOK_auto_retina_effect_EFFECT_NAME_alter(&$effect, $context) {

}

/**
 * Implements HOOK_auto_retina_effect_alter().
 *
 * Allow modules to alter a effects when processing a retina derivative.
 */
function HOOK_auto_retina_effect_alter(&$effect, $retina_info, $context) {

}
