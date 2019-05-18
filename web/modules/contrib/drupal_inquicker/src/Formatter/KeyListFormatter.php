<?php

namespace Drupal\drupal_inquicker\Formatter;

use Drupal\drupal_inquicker\Source\SourceCollection;
use Drupal\drupal_inquicker\traits\Singleton;

/**
 * Formats a SourceCollection as a string which is a list of keys.
 */
class KeyListFormatter extends Formatter {

  use Singleton;

  /**
   * {@inheritdoc}
   */
  public function catchError(\Throwable $t) {
    $this->watchdogThrowable($t);
    return $this->t('No keys can be printed due to error @u', [
      '@u' => $this->errorUuid($t->getMessage()),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function formatValidatedSource($data) {
    $keys = [];
    foreach ($data as $source) {
      $keys[$source->key()] = $source->key();
    }
    return count($keys) ? implode(', ', $keys) : 'No keys';
  }

  /**
   * {@inheritdoc}
   */
  public function validateSource($data) {
    $this->validateClass($data, SourceCollection::class);
    $data->validateMembers();
  }

}
