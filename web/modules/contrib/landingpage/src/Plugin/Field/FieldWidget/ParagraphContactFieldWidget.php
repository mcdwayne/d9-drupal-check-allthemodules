<?php

namespace Drupal\landingpage\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'paragraph_contact_field_widget' widget.
 *
 * @FieldWidget(
 *   id = "paragraph_contact_field_widget",
 *   label = @Translation("Paragraph Contact Form field widget"),
 *   field_types = {
 *     "paragraph_contact_field_type"
 *   }
 * )
 */
class ParagraphContactFieldWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $value = isset($items[$delta]->value) ? $items[$delta]->value : '';

    $options = array();
    $contacts = \Drupal::service('entity.manager')->getStorage('contact_form')->loadMultiple();
    foreach ($contacts as $contact) {
      $options[$contact->id()] = $contact->label();
    }

    $element = array(
      '#type' => 'select',
      '#title' => $this->t('Contact Form'),
      '#options' => $options,
      '#default_value' => $value,
      '#multiple' => FALSE,
    );

    return array('value' => $element);
  }

}
