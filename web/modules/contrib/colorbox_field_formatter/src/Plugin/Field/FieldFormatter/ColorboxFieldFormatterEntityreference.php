<?php

namespace Drupal\colorbox_field_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'colorbox_field_formatter' formatter for entityreferences.
 *
 * @FieldFormatter(
 *   id = "colorbox_field_formatter_entityreference",
 *   label = @Translation("Colorbox FF"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class ColorboxFieldFormatterEntityreference extends ColorboxFieldFormatter {

  /**
   * @inheritdoc
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $form['link_type']['#access'] = FALSE;
    $form['link']['#access'] = FALSE;
    return $form;
  }

  /**
   * @inheritdoc
   */
  protected function viewValue(FieldItemInterface $item) {
    return $item->entity->label();
  }

  /**
   * @inheritdoc
   */
  protected function getUrl(FieldItemInterface $item) {
    return $item->entity->toUrl();
  }

}
