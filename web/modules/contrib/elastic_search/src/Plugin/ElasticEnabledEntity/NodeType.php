<?php
/**
 * Created by PhpStorm.
 * User: twhiston
 * Date: 24/12/16
 * Time: 00:58
 */

namespace Drupal\elastic_search\Plugin\ElasticEnabledEntity;

use Drupal\elastic_search\Annotation\ElasticEnabledEntity;
use Drupal\elastic_search\Plugin\ElasticEnabledEntityBase;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class NodeType
 *
 * @package Drupal\elastic_search\Plugin\ElasticEnabledEntity
 *
 * @ElasticEnabledEntity(
 *   id = "node_type",
 *   label = @Translation("node type")
 * )
 */
class NodeType extends ElasticEnabledEntityBase {

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   */
  public function getChildType(string $entity_type, string $bundle_type): string {
    return 'node';
  }

}