<?php

namespace Drupal\term_level\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\term_level\Plugin\Field\FieldType\TermLevelItem;

/**
 * Plugin for Term level widget.
 *
 * @FieldWidget(
 *   id = "term_level_widget",
 *   label = @Translation("Term level widget"),
 *   field_types = {
 *     "term_level"
 *   }
 * )
 */
class TermLevelWidget extends EntityReferenceAutocompleteWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $field_settings = $this->getFieldSettings();
    $widget = parent::formElement($items, $delta, $element, $form, $form_state);
    $widget['target_id']['#weight'] = 0;
    // Display title for entity autocomplete textfield.
    $widget['target_id']['#title'] = $this->t('Term');
    unset($widget['target_id']['#title_display']);
    // Level widget.
    $level_options = TermLevelItem::extractLevels($field_settings['levels']);
    $widget['level'] = [
      '#type' => 'select',
      '#title' => $this->t('Level'),
      '#options' => $level_options,
      '#default_value' => $items[$delta]->level,
      '#weight' => 1,
    ];
    return $widget;
  }

}
