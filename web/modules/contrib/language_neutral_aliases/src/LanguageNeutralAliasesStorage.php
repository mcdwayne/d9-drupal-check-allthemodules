<?php

namespace Drupal\language_neutral_aliases;

use Drupal\Core\Language\LanguageInterface;
use \Drupal\Core\Path\AliasStorage;

/**
 * Language neutral alias storage.
 */
class LanguageNeutralAliasesStorage extends AliasStorage {

  /**
   * {@inheritdoc}
   */
  public function save($source, $alias, $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED, $pid = NULL) {
    // Enfoce neutral language.
    $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED;

    // If attempting to save a non-neutral alias, save as new.
    if ($pid &&
      ($row = parent::load(['pid' => $pid])) &&
      $row &&
      $row['langcode'] != $langcode) {
      $pid = NULL;
    }

    return parent::save($source, $alias, $langcode, $pid);
  }

  /**
   * {@inheritdoc}
   */
  public function load($conditions) {
    // Callers might have opinions about what language version they want, ignore
    // them.
    $conditions['langcode'] = LanguageInterface::LANGCODE_NOT_SPECIFIED;
    return parent::load($conditions);
  }

  /**
   * {@inheritdoc}
   */
  public function delete($conditions) {
    $conditions['langcode'] = LanguageInterface::LANGCODE_NOT_SPECIFIED;
    return parent::delete($conditions);
  }

  /**
   * {@inheritdoc}
   */
  public function preloadPathAlias($preloaded, $langcode) {
    return parent::preloadPathAlias($preloaded, LanguageInterface::LANGCODE_NOT_SPECIFIED);
  }

  /**
   * {@inheritdoc}
   */
  public function lookupPathAlias($path, $langcode) {
    return parent::lookupPathAlias($path, LanguageInterface::LANGCODE_NOT_SPECIFIED);
  }

  /**
   * {@inheritdoc}
   */
  public function lookupPathSource($path, $langcode) {
    return parent::lookupPathSource($path, LanguageInterface::LANGCODE_NOT_SPECIFIED);
  }

  /**
   * {@inheritdoc}
   */
  public function aliasExists($alias, $langcode, $source = NULL) {
    return parent::aliasExists($alias, LanguageInterface::LANGCODE_NOT_SPECIFIED, $source);
  }

  /**
   * {@inheritdoc}
   */
  public function languageAliasExists() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getAliasesForAdminListing($header, $keys = NULL) {
    $query = $this->connection->select(static::TABLE)
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('Drupal\Core\Database\Query\TableSortExtender');
    $query->condition('langcode', LanguageInterface::LANGCODE_NOT_SPECIFIED);
    if ($keys) {
      // Replace wildcards with PDO wildcards.
      $query->condition('alias', '%' . preg_replace('!\*+!', '%', $keys) . '%', 'LIKE');
    }
    try {
      return $query
        ->fields(static::TABLE)
        ->orderByHeader($header)
        ->limit(50)
        ->execute()
        ->fetchAll();
    }
    catch (\Exception $e) {
      $this->catchException($e);
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function pathHasMatchingAlias($initial_substring) {
    $query = $this->connection->select(static::TABLE, 'u');
    $query->addExpression(1);
    try {
      return (bool) $query
        ->condition('u.source', $this->connection->escapeLike($initial_substring) . '%', 'LIKE')
        ->condition('u.langcode', LanguageInterface::LANGCODE_NOT_SPECIFIED)
        ->range(0, 1)
        ->execute()
        ->fetchField();
    }
    catch (\Exception $e) {
      $this->catchException($e);
      return FALSE;
    }
  }

}
