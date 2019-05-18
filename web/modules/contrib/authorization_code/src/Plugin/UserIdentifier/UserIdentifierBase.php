<?php

namespace Drupal\authorization_code\Plugin\UserIdentifier;

use Drupal\authorization_code\Plugin\AuthorizationCodePluginBase;
use Drupal\authorization_code\UserIdentifierInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for user identifier plugins.
 */
abstract class UserIdentifierBase extends AuthorizationCodePluginBase implements UserIdentifierInterface, ContainerFactoryPluginInterface {

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * UserIdentifierBase constructor.
   *
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   * @param array $configuration
   *   The configuration array.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   */
  public function __construct(UserStorageInterface $user_storage, array $configuration, string $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->userStorage = $user_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('entity_type.manager')->getStorage('user'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['#access'] = FALSE;
    return $form;
  }

}
