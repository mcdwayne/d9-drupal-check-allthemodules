<?php

namespace Drupal\search_365\Serializer;

use Drupal\search_365\SearchResults\Result;
use Drupal\search_365\SearchResults\ResultSet;

/**
 * Serializer for ResultSet objects.
 */
class ResultSetNormalizer extends BaseNormalizer {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = ResultSet::class;

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []) {
    $resultSet = ResultSet::create()
      ->setResultsCount($data['resultscount'])
      ->setPageSize($data['pagesize'])
      ->setPageNum($data['pagenum']);
    if (isset($data['didyoumean'])) {
      $resultSet->setDidYouMean($data['didyoumean']);
    }
    if (isset($data['featured'])) {
      $resultSet->setFeatured($data['featured']);
    }

    foreach ($data['results'] as $resultData) {
      $resultSet->addResult($this->serializer->denormalize($resultData, Result::class));
    }

    return $resultSet;

  }

}
