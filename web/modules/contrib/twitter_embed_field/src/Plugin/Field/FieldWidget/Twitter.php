<?php

namespace Drupal\twitter_embed_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextfieldWidget;
use Drupal\Core\Form\FormStateInterface;

/**
 * Field widget for twitter fields.
 *
 * @FieldWidget(
 *   id = "twitter_embed_field",
 *   label = @Translation("Twitter"),
 *   field_types = {
 *     "string",
 *   }
 * )
 */
class Twitter extends StringTextfieldWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['value']['#element_validate'] = [
      [$this, 'validate'],
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($element, FormStateInterface $form_state) {
    $handle = $element['#value'];

    $handle = str_replace('@', '', $handle);

    if (!empty($handle) && !preg_match('/^[a-zA-Z0-9_]{1,15}$/', $handle)) {
      $form_state->setError($element, $this->t('<em>@value</em> is no valid twitter handle.', ['@value' => $handle]));
    }
  }
}