<?php

namespace Drupal\autopost_facebook\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Advanced widget for Autopost Facebook field.
 *
 * @FieldWidget(
 *   id = "autopost_facebook_default",
 *   label = @Translation("Autopost Facebook form"),
 *   field_types = {
 *     "autopost_facebook"
 *   }
 * )
 */
class AutopostFacebookDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
      '#type' => 'radios',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#options' => [
        0 => $this->t("Don't post"),
        1 => $this->t("Post only once"),
        2 => $this->t("Post on every update"),
      ],
    ];
    return $element;
  }

}
