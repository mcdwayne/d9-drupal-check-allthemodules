<?php

namespace Drupal\rules_data_exchanger\ContextProvider;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides a stored data for using in rules components or other rules.
 */
class StoredDataContext implements ContextProviderInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    $stored_data = \Drupal::state()->get('rules_data_exchanger.stored_data');
    // If a data still not stored then return nothing (this method calls if any rules action added).
    if (!isset($stored_data)) {
      return [];
    }

    $result = [];

    foreach ($stored_data as $name => $data) {
      $context_definition = new ContextDefinition($data['type'], $this->t('Stored data'));
      $context = new Context($context_definition, $data['data']);
      $cacheability = new CacheableMetadata();
      $cacheability->setCacheContexts(['stored.data']);
      $context->addCacheableDependency($cacheability);

      $result[$name] = $context;
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    return $this->getRuntimeContexts([]);
  }

}
