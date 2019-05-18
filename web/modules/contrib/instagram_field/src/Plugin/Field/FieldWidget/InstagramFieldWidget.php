<?php

namespace Drupal\instagram_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'instagramfield_default' widget.
 *
 * @FieldWidget(
 *   id = "instagramfield_default",
 *   label = @Translation("Instagram Field"),
 *   field_types = {
 *     "instagramfield"
 *   }
 * )
 */
class InstagramFieldWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items,
    $delta,
    array $element,
    array &$form,
    FormStateInterface $form_state) {

    $element['instagramfield'] = [
      '#type' => 'label',
      '#title' => 'Instagram Field',
    ];
    $element['instagramid'] = [
      '#type' => 'hidden',
      '#default_value' => isset($items[$delta]->instagramid) ?
      $items[$delta]->instagramid : ' ',
    ];
    $element['instagramlink'] = [
      '#type' => 'hidden',
      '#default_value' => isset($items[$delta]->instagramlink) ?
      $items[$delta]->instagramlink : ' ',
    ];
    return $element;
  }

}
