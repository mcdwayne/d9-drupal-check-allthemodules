<?php

namespace Drupal\user_restrictions;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user_restrictions\Entity\UserRestrictions;
use Psr\Log\LoggerInterface;

/**
 * Defines the user restriction manager.
 */
class UserRestrictionsManager implements UserRestrictionsManagerInterface {

  use StringTranslationTrait;

  /**
   * List of restriction errors.
   *
   * @var string[]
   */
  protected $errors = [];

  /**
   * The entity storage interfacce.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * The type manager interfacce.
   *
   * @var \Drupal\user_restrictions\UserRestrictionTypeManagerInterface
   */
  protected $typeManager;

  /**
   * The logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a UserRestrictionsManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity storage.
   * @param \Drupal\user_restrictions\UserRestrictionTypeManagerInterface $type_manager
   *   The user restriction type manager.
   * @param Psr\Log\LoggerInterface $logger
   *   The user_restrictions logger channel.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, UserRestrictionTypeManagerInterface $type_manager, LoggerInterface $logger) {
    $this->entityStorage = $entity_manager->getStorage('user_restrictions');
    $this->typeManager = $type_manager;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function matchesRestrictions(array $data) {
    /** @var \Drupal\user_restrictions\Plugin\UserRestrictionTypeInterface $type */
    foreach ($this->typeManager->getTypes() as $key => $type) {
      if ($type->matches($data)) {
        $this->setError($key, $type->getErrorMessage());
        // Break after first match.
        return TRUE;
      }
    }
    // No restrictions match.
    return FALSE;
  }

  /**
   * Set error message for a specific restriction type.
   *
   * @param string $type
   *   Type of restriction, i.e. "name".
   * @param string $message
   *   Error message.
   *
   * @return \Drupal\user_restrictions\UserRestrictionsManagerInterface
   *   The service for chaining.
   */
  protected function setError($type, $message) {
    $this->errors[$type] = $message;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getErrors() {
    return $this->errors;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteExpiredRules() {
    $rules = $this->entityStorage->loadMultiple();
    /* @var $rule \Drupal\user_restrictions\Entity\UserRestrictions */
    foreach ($rules as $rule) {
      $expiry = $rule->getExpiry();

      if ($expiry !== UserRestrictions::NO_EXPIRY && $expiry < REQUEST_TIME) {
        $rule->delete();
        $this->logger->notice('Expired rule %label has been deleted.', ['%label' => $rule->label()]);
      }
    }
    return $this;
  }

}
