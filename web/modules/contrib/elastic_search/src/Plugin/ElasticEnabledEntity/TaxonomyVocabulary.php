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

/**
 * Class NodeType
 *
 * @package Drupal\elastic_search\Plugin\ElasticEnabledEntity
 *
 * @ElasticEnabledEntity(
 *   id = "taxonomy_vocabulary",
 *   label = @Translation("Taxonomy Vocabulary")
 * )
 */
class TaxonomyVocabulary extends ElasticEnabledEntityBase {

  /**
   * @inheritdoc
   */
  public function getChildType(string $entity_type, string $bundle_type): string {
    return 'taxonomy_term';
  }

}