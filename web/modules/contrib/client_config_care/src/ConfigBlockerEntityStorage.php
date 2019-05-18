<?php

namespace Drupal\client_config_care;

use Drupal\client_config_care\Entity\ConfigBlockerEntityInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the storage handler class for Config blocker entity entities.
 *
 * This extends the base storage class, adding required special handling for
 * Config blocker entity entities.
 *
 * @ingroup client_config_care
 */
class ConfigBlockerEntityStorage extends SqlContentEntityStorage implements ConfigBlockerEntityStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(ConfigBlockerEntityInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {config_blocker_entity_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {config_blocker_entity_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(ConfigBlockerEntityInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {config_blocker_entity_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('config_blocker_entity_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

  public function isBlockerExisting(string $configName): bool {
    $result = $this->database->query('SELECT COUNT(*) FROM {config_blocker_entity} WHERE name = :name', [':name' => $configName])
      ->fetchField();

    if (is_numeric($result) && $result > 0) {
      return TRUE;
    }

    return FALSE;
  }

}
