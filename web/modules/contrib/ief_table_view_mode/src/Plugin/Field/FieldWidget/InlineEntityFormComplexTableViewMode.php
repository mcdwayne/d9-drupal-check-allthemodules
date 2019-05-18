<?php

namespace Drupal\ief_table_view_mode\Plugin\Field\FieldWidget;

use Drupal\inline_entity_form\Plugin\Field\FieldWidget\InlineEntityFormComplex;
use Drupal\Core\Form\FormStateInterface;

/**
 * Complex inline widget.
 *
 * @FieldWidget(
 *   id = "inline_entity_form_complex_table_view_mode",
 *   label = @Translation("Inline entity form - Complex - Table View Mode"),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = true
 * )
 */
class InlineEntityFormComplexTableViewMode extends InlineEntityFormComplex {
  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    $element += parent::settingsForm($form, $form_state);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function createInlineFormHandler() {
    if (!isset($this->inlineFormHandler)) {
      $target_type = $this->getFieldSetting('target_type');
      $this->inlineFormHandler = $this->entityTypeManager->getHandler($target_type, 'inline_form_table_view_mode');
    }
  }
}
