<?php

namespace Drupal\friends;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\friends\Entity\FriendsInterface;
use Drupal\token\TokenInterface;

/**
 * Class FriendsService.
 */
class FriendsService implements FriendsServiceInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
  /**
   * Drupal\Core\Entity\EntityFieldManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Drupal\token\TokenInterface definition.
   *
   * @var \Drupal\token\TokenInterface
   */
  protected $token;

  /**
   * Constructs a new FriendsService object.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $entity_field_manager,
    ConfigFactoryInterface $config_factory,
    TokenInterface $token
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->configFactory = $config_factory;
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllowedTypes() {
    return $this->entityFieldManager->getFieldDefinitions('friends', 'friends')['friends_type']->getSetting('allowed_values');
  }

  /**
   * {@inheritdoc}
   */
  public function getAllowedStatus(bool $all = FALSE) {
    $statuses = $this->entityFieldManager->getFieldDefinitions('friends', 'friends')['friends_status']->getSetting('allowed_values');
    if (!$all) {
      unset($statuses['pending']);
    }

    return $statuses;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage(string $message, FriendsInterface $friends) {
    $config = $this->configFactory->get('friends.settings');

    return $this->processTokens($config->get($message), $friends);
  }

  /**
   * {@inheritdoc}
   */
  protected function processTokens(string $str, FriendsInterface $friends) {
    return $this->token->replace($str, ['friends' => $friends], []);
  }

}
