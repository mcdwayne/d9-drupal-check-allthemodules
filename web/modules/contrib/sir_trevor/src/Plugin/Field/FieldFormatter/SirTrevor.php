<?php

namespace Drupal\sir_trevor\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\sir_trevor\EventDispatchingDataProcessor;
use Drupal\text\Plugin\Field\FieldFormatter\TextDefaultFormatter;

/**
 * Plugin implementation of the 'text_default' formatter.
 *
 * @FieldFormatter(
 *   id = "sir_trevor",
 *   label = @Translation("Sir Trevor"),
 *   field_types = {
 *     "sir_trevor",
 *   }
 * )
 */
class SirTrevor extends TextDefaultFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $decoded = json_decode($items->getString());

    // Return early if we can't decode the items.
    if (empty($decoded)) {
      return [];
    }

    $renderable = $this->itemsToRenderable($decoded);

    if (!empty($renderable)){
      foreach ($renderable as &$item) {
        $item['#entity'] = $items->getEntity();
      }
    }

    return $renderable;
  }


  /**
   * @param \stdClass $items
   * @return array
   */
  private function itemsToRenderable(\stdClass $items) {
    $renderable = [];

    if (empty($items) || empty($items->data)) {
      // Return early. this is an invalid item.
      return [];
    }

    $eventDispatcher = \Drupal::getContainer()->get('event_dispatcher');
    $eventDispatchingDataProcessor = new EventDispatchingDataProcessor($eventDispatcher);
    if (!empty($items->type)) {
      $renderable = [
        '#theme' => "sir_trevor_{$items->type}",
        '#data' => $eventDispatchingDataProcessor->processData($items->data),
      ];
    }
    else {
      foreach ($items->data as $items) {
        $renderable[] = $this->itemsToRenderable($items);
      }
    }

    return $renderable;
  }

}
