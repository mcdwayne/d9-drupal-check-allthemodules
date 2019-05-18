<?php

namespace Drupal\image_field_repair\Plugin\Field\FieldWidget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Plugin\Field\FieldWidget\ImageWidget;

/**
 * Fix support multiple upload in ImageWidget.
 *
 * @see https://www.drupal.org/project/drupal/issues/2644468
 */
class ImageFieldRepairWidget extends ImageWidget {

  /**
   * {@inheritdoc}
   */
  public static function process($element, FormStateInterface $form_state, $form) {
    $element = parent::process($element, $form_state, $form);
    if (!empty($element['#files']) && count($element['#files']) > 1) {
      // In the case of a multiple upload, do not cache the image dimensions as
      // it can lead to all images having the same dimensions.
      if (isset($element['width']['#value'], $element['height']['#value'])) {
        $element['width']['#value'] = $element['height']['#value'] = NULL;
      }
    }
    return $element;
  }

}
