<?php
/**
 * Created by PhpStorm.
 * User: th140
 * Date: 11/17/17
 * Time: 8:30 AM
 */

namespace Drupal\basicshib\Plugin\basicshib\user_provider;

use Drupal\basicshib\Annotation\BasicShibUserProvider;
use Drupal\basicshib\Plugin\UserProviderPluginInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class UserProviderPluginDefault
 *
 * @package Drupal\basicshib\Plugin\basicshib\user_provider
 *
 * @BasicShibUserProvider(
 *   id = "basicshib",
 *   title = "Default user provider"
 * )
 */
class UserProviderPluginDefault implements UserProviderPluginInterface, ContainerFactoryPluginInterface {

  /**
   * @var UserStorageInterface
   */
  private $user_storage;

  /**
   * @inheritDoc
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('entity_type.manager')->getStorage('user')
    );
  }


  /**
   * DefaultUserProvider constructor.
   *
   * @param UserStorageInterface $user_storage
   */
  public function __construct(UserStorageInterface $user_storage) {
    $this->user_storage = $user_storage;
  }

  /**
   * @inheritDoc
   */
  public function loadUserByName($name) {
    $users = $this->user_storage
      ->loadByProperties(['name' => $name]);

    if (count($users) === 1) {
      return reset($users);
    }
  }

  /**
   * @inheritDoc
   */
  public function createUser($name, $mail) {
    return $this->user_storage
      ->create([
        'name' => $name,
        'mail' => $mail,
        'status' => 1,
      ]);
  }

}
