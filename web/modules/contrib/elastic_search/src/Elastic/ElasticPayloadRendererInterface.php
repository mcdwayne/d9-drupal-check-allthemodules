<?php
/**
 * Created by PhpStorm.
 * User: twhiston
 * Date: 01.06.17
 * Time: 19:21
 */

namespace Drupal\elastic_search\Elastic;

use Drupal\Core\Entity\EntityInterface;

/**
 * Interface ElasticPayloadRendererInterface
 *
 * @package Drupal\elastic_search\Elastic
 */
interface ElasticPayloadRendererInterface {

  /**
   * Build a payload from an array of documents. It is assumed that the array of documents is already validated and
   * thus safe to process without testing, and that the document is the correct translation
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return array
   */
  public function buildDocumentPayload(EntityInterface $entity): array;

}