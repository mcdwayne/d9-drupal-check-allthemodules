<?php

namespace Drupal\landingpage\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'paragraph_donate_field_widget' widget.
 *
 * @FieldWidget(
 *   id = "paragraph_donate_field_widget",
 *   label = @Translation("Paragraph donate field widget"),
 *   field_types = {
 *     "paragraph_donate_field_type"
 *   }
 * )
 */
class ParagraphDonateFieldWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $value = isset($items[$delta]->value) ? $items[$delta]->value : '';

    $element = array(
      '#type' => 'textfield',
      '#title' => $this->t('Paragraph Donate'),
      '#default_value' => $value,
    );

    return array('value' => $element);
  }

}
