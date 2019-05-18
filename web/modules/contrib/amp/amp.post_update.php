<?php

use Drupal\image\Entity\ImageStyle;
use Drupal\image\ImageStyleInterface;

/**
 * Add new image styles for 1200px AMP minimum size recommendation.
 */
function amp_post_update_8010() {

  $styles = [
    'amp_1200x675_16_9' => [
      'label' => 'AMP 1200x675 - 16:9',
      'effect_name' => 'image_scale',
      'data' => [
        'width' => 1200,
        'height' => 675,
        'upscale' => true,
      ],
    ],
    'amp_1200x900_4_3' => [
      'label' => 'AMP 1200x900 - 4:3',
      'effect_name' => 'image_scale',
      'data' => [
        'width' => 1200,
        'height' => 900,
        'upscale' => true,
      ],
    ],
    'amp_1200x1200_1_1' => [
      'label' => 'AMP 1200x1200 - 1:1',
      'effect_name' => 'image_scale',
      'data' => [
        'width' => 1200,
        'height' => 1200,
        'upscale' => true,
      ],
    ],
  ];

  foreach ($styles as $name => $values) {
    _amp_update_create_image_style($name, $values['label'], $values['effect_name'], $values['data']);
  }
  $message = t('AMP now recommends using a minimum image size of 1200px instead of 696px, with 16:9, 4:3, or 1:1 aspect ratios. New AMP image styles have been added, and you may want to find places you used the old 696px image style and update to one of the new styles. The old style was not removed, you can do that manually once you are no longer using it. If the default image styles do not work for you, you can create your own instead.');
  return $message;
}

/**
 * Helper function to create image style configuration objects for an update.
 *
 * @param string $name
 *   The name of the config object.
 * @param string $label
 *   The label for the image style.
 * @param string $effect_name
 *   The name of the image effect.
 * @param array $effect_data
 *   The image style effect data.
 */
function _amp_update_create_image_style($name, $label, $effect_name, array $effect_data) {
  if (!\Drupal::service('config.storage')->exists($name)) {
    /** @var ImageStyleInterface $image_style */
    $image_style = ImageStyle::create([
      'name' => $name,
      'label' => $label,
      'dependencies' => ['enforced' => ['module' => ['amp']]],
    ]);
    $image_style->addImageEffect([
      'id' => $effect_name,
      'data' => $effect_data,
      'weight' => 1,
    ]);
    $image_style->save();
  }
}
