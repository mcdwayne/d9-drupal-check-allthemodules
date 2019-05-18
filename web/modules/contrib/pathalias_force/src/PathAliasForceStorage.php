<?php

/**
 * @file
 * Class used to decorate path.alias_manager service.
 */

namespace Drupal\pathalias_force;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\AliasStorage;
use Drupal\Core\Path\AliasStorageInterface;
use Drupal\language\Entity\ConfigurableLanguage;

class PathAliasForceStorage extends AliasStorage {

  /**
   * Alias Storage Interface
   *
   * @var  \Drupal\Core\Path\AliasStorageInterface
   */
  protected $innerService;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    AliasStorageInterface $inner_service,
    Connection $connection,
    ModuleHandlerInterface $module_handler
  ) {
    $this->innerService = $inner_service;
    parent::__construct($connection, $module_handler);
  }

  /**
   * {@inheritdoc}
   */
  public function save($source, $alias, $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED, $pid = NULL, $forced = 0) {
    if ($source[0] !== '/') {
      throw new \InvalidArgumentException(sprintf('Source path %s has to start with a slash.', $source));
    }

    if ($alias[0] !== '/') {
      throw new \InvalidArgumentException(sprintf('Alias path %s has to start with a slash.', $alias));
    }

    // Update forced aliases when translating instead of creating duplicates.
    // Update instead of creating a new one.
    $query = $this->connection->select('url_alias', 'ul')
      ->fields('ul', ['pid'])
      ->condition('ul.source', $source)
      ->condition('ul.langcode', $langcode);
    $pid = $query->execute()->fetchField();

    $fields = [
      'source' => $source,
      'alias' => $alias,
      'langcode' => $langcode,
      'forced' => $forced,
    ];

    // Insert or update the alias.
    if (empty($pid)) {
      $try_again = FALSE;
      try {
        $query = $this->connection->insert(static::TABLE)
          ->fields($fields);
        $pid = $query->execute();
      }
      catch (\Exception $e) {
        // If there was an exception, try to create the table.
        if (!$try_again = $this->ensureTableExists()) {
          // If the exception happened for other reason than the missing table,
          // propagate the exception.
          throw $e;
        }
      }
      // Now that the table has been created, try again if necessary.
      if ($try_again) {
        $query = $this->connection->insert(static::TABLE)
          ->fields($fields);
        $pid = $query->execute();
      }

      $fields['pid'] = $pid;
      $operation = 'insert';
    }
    else {
      // Fetch the current values so that an update hook can identify what
      // exactly changed.
      try {
        $original = $this->connection->query('SELECT source, alias, langcode FROM {url_alias} WHERE pid = :pid', [':pid' => $pid])
          ->fetchAssoc();
      }
      catch (\Exception $e) {
        $this->catchException($e);
        $original = FALSE;
      }
      $fields['pid'] = $pid;
      $query = $this->connection->update(static::TABLE)
        ->fields($fields)
        ->condition('pid', $pid);
      $pid = $query->execute();
      $fields['original'] = $original;
      $operation = 'update';
    }
    if ($pid) {
      // @todo Switch to using an event for this instead of a hook.
      $this->moduleHandler->invokeAll('path_' . $operation, [$fields]);
      Cache::invalidateTags(['route_match']);
      return $fields;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function delete($conditions, $fallback = FALSE) {
    // Triggered by hook_uninstall, removes all forced aliases.
    if ($fallback) {
      parent::delete($conditions);
    }
    else {
      // Get the deleted path by conditions.
      $existing = $this->load($conditions);

      if ($existing) {
        // Get configurable language using path langcode.
        $conf_language = ConfigurableLanguage::load($existing['langcode']);

        // Check if configurable language has a fallback language.
        if ($fallback_langcode = $conf_language->getThirdPartySetting('language_hierarchy', 'fallback_langcode', '')) {
          // Assign the fallback language to conditions, use the same source.
          $fallback_conditions = [
            'source' => $existing['source'],
            'langcode' => $fallback_langcode,
          ];
          // Check if the alias for the fallback language exists.
          if ($fallback_path = $this->load($fallback_conditions)) {
            // If it exists, we're deleting a translation, provide fallback alias.
            $this->save($existing['source'], $existing['alias'], $existing['langcode'], $existing['pid'], $existing['forced']);
          }
          else {
            // If it doesn't, this we're deleting fallback node and translations.
            parent::delete($conditions);
          }
        }
      }
      else {
        // This language has no fallback, just delete.
        parent::delete($conditions);
      }
    }
  }

  /**
   * Helper function to delete all forced aliases.
   *
   * @param array $conditions
   *  Path conditions.
   */
  public function removeForcedAliases($conditions) {
    $this->delete($conditions, TRUE);
  }

}
