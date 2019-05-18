<?php

namespace Drupal\search_365\Serializer;

use Drupal\search_365\SearchResults\Result;

/**
 * Serializer for Result objects.
 */
class ResultNormalizer extends BaseNormalizer {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = Result::class;

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []) {
    $result = Result::create()
      ->setTitle($data['title'])
      ->setUrl($data['url'])
      ->setBody($data['body']);
    if (isset($data['crawltime'])) {
      $result->setCrawlTime($data['crawltime']);
    }
    if (isset($data['collection'])) {
      $result->setCollection($data['collection']);
    }
    if (isset($data['systemtitle'])) {
      $result->setSystemTitle($data['systemtitle']);
    }
    return $result;
  }

}
