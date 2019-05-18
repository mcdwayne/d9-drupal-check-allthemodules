<?php

namespace Drupal\authorization_code_sms\Plugin\UserIdentifier;

use Drupal\authorization_code\Plugin\UserIdentifier\UserIdentifierBase;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * User identifier plugin that uses the users telephone number.
 *
 * @UserIdentifier(
 *   id = "telephone",
 *   title = @Translation("Telephone")
 * )
 */
class Telephone extends UserIdentifierBase implements DependentPluginInterface {

  /**
   * The phone number settings entity storage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $phoneNumberSettingsStorage;

  /**
   * Telephone constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, UserStorageInterface $user_storage, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($user_storage, $configuration, $plugin_id, $plugin_definition);
    $this->phoneNumberSettingsStorage = $entity_type_manager
      ->getStorage('phone_number_settings');
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function loadUser($identifier) {
    $maybe_user = $this->userStorage->loadByProperties([$this->phoneFieldName() => $identifier]);
    return reset($maybe_user) ?: NULL;
  }

  /**
   * The phone number field name.
   *
   * @return string
   *   The phone number field name.
   */
  private function phoneFieldName(): string {
    /** @var \Drupal\sms\Entity\PhoneNumberSettingsInterface $settings */
    $settings = $this->phoneNumberSettingsStorage->load('user.user');
    return $settings->getFieldName('phone_number');
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return NestedArray::mergeDeep(parent::defaultConfiguration(), [
      'plugin' => ['sms.phone.user.user'],
      'module' => ['authorization_code_sms', 'sms'],
    ]);
  }

}
