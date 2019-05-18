<?php

namespace Drupal\representative_image\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Plugin\Field\FieldWidget\ImageWidget;

/**
 * Defines the 'representative_image' field widget.
 *
 * @FieldWidget(
 *   id = "representative_image",
 *   label = @Translation("Representative Image"),
 *   field_types = {"representative_image"},
 * )
 */
class RepresentativeImageWidget extends ImageWidget {

  /**
   * @inheritDoc
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * @inheritDoc
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    return [];
  }

}
