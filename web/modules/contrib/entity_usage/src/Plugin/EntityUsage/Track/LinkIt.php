<?php

namespace Drupal\entity_usage\Plugin\EntityUsage\Track;

use Drupal\Component\Utility\Html;

/**
 * Tracks usage of entities related in entity_reference fields.
 *
 * @EntityUsageTrack(
 *   id = "linkit",
 *   label = @Translation("Entities embedded with LinkIt"),
 *   description = @Translation("Tracks usage of entities related when embedded with the LinkIt module."),
 * )
 */
class LinkIt extends EmbedBase {

  /**
   * {@inheritdoc}
   */
  public function parseEntitiesFromText($text) {
    $dom = Html::load($text);
    $xpath = new \DOMXPath($dom);
    $entities = [];
    foreach ($xpath->query('//a[@data-entity-type and @data-entity-uuid]') as $node) {
      // Note that this does not cover 100% of the situations. In the (unlikely
      // but possible) use case where the user embeds the same entity twice in
      // the same field, we are just recording 1 usage for this target entity,
      // when we should record 2. The alternative is to add a lot of complexity
      // to the update logic of our plugin, to deal with all possible
      // combinations in the update scenario.
      // @TODO Re-evaluate if this is worth the effort and overhead.
      $entities[$node->getAttribute('data-entity-uuid')] = $node->getAttribute('data-entity-type');
    }
    return $entities;
  }

}
