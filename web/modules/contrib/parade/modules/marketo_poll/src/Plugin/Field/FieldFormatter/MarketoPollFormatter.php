<?php

namespace Drupal\marketo_poll\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'marketo_poll' formatter.
 *
 * @FieldFormatter(
 *   id = "marketo_poll",
 *   label = @Translation("Marketo poll"),
 *   field_types = {
 *     "marketo_poll"
 *   }
 * )
 */
class MarketoPollFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   *
   * @todo - js library
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $polls = [];
    foreach ($items as $item) {
      $polls[] = [
        'subscription_url' => $item->subscription_url,
        'poll_class' => $item->poll_class,
        'poll_id' => $item->poll_id,
      ];
    }

    $elements[] = [
      '#theme' => 'marketo_poll_field',
      '#polls' => $polls,
      '#entity' => $items->getEntity(),
    ];
    return $elements;
  }

}
