<?php

namespace Drupal\thumbor_effects_crop\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\crop\Entity\Crop;
use Drupal\image\Plugin\Field\FieldWidget\ImageWidget;
use Drupal\thumbor_effects_crop\ThumborCropManager;

/**
 * Plugin implementation of the 'image_thumbor_effects_crop' widget.
 *
 * @FieldWidget(
 *   id = "image_thumbor_effects_crop",
 *   label = @Translation("Image (Thumbor Effects Crop)"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class ThumborCropImageWidget extends ImageWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['#process'][] = [static::class, 'process'];
    $element['#thumbor_effects_crop'] = [
      'aspect_ratio' => NULL,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   *
   * Processes an 'image_thumbor_effects_crop' field widget.
   */
  public static function process($element, FormStateInterface $form_state, $form): array {
    $element = parent::process($element, $form_state, $form);

    $item = $element['#value'];
    $default_aspect_ratio = $item['thumbor_effects_crop']['aspect_ratio'] ?? '';
    $default_orientation = $item['thumbor_effects_crop']['orientation'] ?? ThumborCropManager::ORIENTATION_LANDSCAPE;

    // Flip the ratio when there is a portrait orientation. The options are
    // landscape only.
    if (!empty($default_aspect_ratio) && $default_orientation === ThumborCropManager::ORIENTATION_PORTRAIT) {
      $default_aspect_ratio = implode(':', array_reverse(explode(':', $default_aspect_ratio)));
    }

    $element['thumbor_effects_crop'] = [
      '#type' => 'details',
      '#title' => t('Thumbor Effects Crop'),
      '#description' => t('The selected crop is ony used for image effects that support cropping. Note that only one crop per file can be used.'),
      '#tree' => TRUE,
      '#open' => TRUE,
      'aspect_ratio' => [
        '#type' => 'select',
        '#required' => FALSE,
        '#title' => t('Aspect ratio'),
        '#description' => t('Select the aspect ratio of the crop.'),
        '#default_value' => $default_aspect_ratio,
        '#empty_option' => t('- Select -'),
        '#options' => self::getCropTypeOptions(),
      ],
      'orientation' => [
        '#type' => 'radios',
        '#required' => TRUE,
        '#title' => t('Orientation'),
        '#description' => t('Select the orientation of the crop. Without aspect ratio this setting has no effect.'),
        '#default_value' => $default_orientation,
        '#options' => [
          ThumborCropManager::ORIENTATION_LANDSCAPE => t('Landscape'),
          ThumborCropManager::ORIENTATION_PORTRAIT => t('Portrait'),
        ],
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function value($element, $input, FormStateInterface $form_state): array {
    $return = parent::value($element, $input, $form_state);

    // When an element is loaded, thumbor_effects_crop needs to be set. During a
    // form submission the value will already be there.
    if (!isset($return['target_id']) || isset($return['thumbor_effects_crop'])) {
      return $return;
    }

    /** @var \Drupal\file\FileInterface $file */
    $file = \Drupal::service('entity_type.manager')
      ->getStorage('file')
      ->load($return['target_id']);

    if (!$file) {
      \Drupal::logger('thumbor_effects_crop')
        ->error('Attempted to get crop settings for an invalid or temporary file.');
      $return['thumbor_effects_crop'] = $element['#thumbor_effects_crop'];
      return $return;
    }

    $crop = Crop::findCrop($file->getFileUri(), NULL);
    if (!$crop) {
      return $return;
    }

    $aspect_ratio = ThumborCropManager::getAspectRatio($crop);
    $orientation = ThumborCropManager::getOrientation($crop);

    $return['thumbor_effects_crop'] = [
      'aspect_ratio' => $aspect_ratio,
      'orientation' => $orientation,
    ];

    return $return;
  }

  /**
   * Get the crop type options with an aspect ratio.
   *
   * Note all aspect ratio's should be landscape.
   *
   * @return string[]
   *   The available crop types as options array.
   */
  private static function getCropTypeOptions(): array {
    return [
      '1:1' => t('1:1 (Square)'),
      '2:1' => t('2:1 (Univisium)'),
      '3:1' => t('3:1 (Panorama)'),
      '3:2' => t('3:2 (Classic)'),
      '4:1' => t('4:1 (Napoléon)'),
      '4:3' => t('4:3 (Normal)'),
      '16:9' => t('16:9 (Normal)'),
    ];
  }

}
