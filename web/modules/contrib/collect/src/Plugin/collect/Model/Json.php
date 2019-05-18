<?php
/**
 * @file
 * Contains \Drupal\collect\Plugin\collect\Model\Json.
 */

namespace Drupal\collect\Plugin\collect\Model;

use Drupal\collect\CollectContainerInterface;
use Drupal\collect\Model\ModelPluginBase;
use Drupal\Component\Serialization\Json as SerializationJson;

/**
 * Provides support for JSON data, using a subset of JsonPath for queries.
 *
 * @see http://goessner.net/articles/JsonPath/
 *
 * @Model(
 *   id = "json",
 *   label = @Translation("JSON"),
 *   description = @Translation("Uses a simple query syntax to access specific values in any JSON data.")
 * )
 */
class Json extends ModelPluginBase {

  /**
   * {@inheritdoc}
   */
  public function parse(CollectContainerInterface $collect_container) {
    return SerializationJson::decode($collect_container->getData());
  }

}
