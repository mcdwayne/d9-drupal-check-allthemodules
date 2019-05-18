<?php

namespace Drupal\file_attributes\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Plugin\Field\FieldWidget\FileWidget;

/**
 * Plugin implementation of the 'file_attributes' widget.
 *
 * @FieldWidget(
 *   id = "file_attributes",
 *   label = @Translation("File attributes"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class FileAttributesFieldWidget extends FileWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['options']['rel'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Rel tag'),
      '#default_value' => isset($items[$delta]->options['rel']) ? $items[$delta]->options['rel'] : NULL,
    ];

    return $element;
  }

}
