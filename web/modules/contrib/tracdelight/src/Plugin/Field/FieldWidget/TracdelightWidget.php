<?php

/**
 * @file
 * Contains \Drupal\tracdelight\Plugin\Field\FieldWidget\TracdelightWidget.
 */

namespace Drupal\tracdelight\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'tracdelight_widget' widget.
 *
 * @FieldWidget(
 *   id = "tracdelight_widget",
 *   label = @Translation("Tracdelight widget"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class TracdelightWidget extends EntityReferenceAutocompleteWidget {
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $returnValue = parent::formElement($items, $delta, $element, $form, $form_state);
    $returnValue['target_id']['#selection_handler'] = 'tracdelight:product';
    return $returnValue;
  }


}
