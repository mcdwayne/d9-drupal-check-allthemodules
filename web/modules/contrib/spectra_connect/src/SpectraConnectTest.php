<?php

namespace Drupal\spectra_connect;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SpectraConnectTest.
 *
 * @package Drupal\spectra_connect
 */
class SpectraConnectTest implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * EntityTypeInfo constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   */
  public function __construct(AccountInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user')
    );
  }

  /**
   * Adds the connect test operation.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity on which to define an operation.
   *
   * @return array
   *   An array of operation definitions.
   *
   * @see hook_entity_operation()
   */
  public function entityOperation(EntityInterface $entity) {
    $operations = [];
    if ($entity->getEntityTypeId() === 'spectra_connect' && $this->currentUser->hasPermission('perform spectra_connect test')) {
      $operations['spectra_connect'] = [
        'title' => $this->t('Connect Test'),
        'weight' => 100,
        'url' => Url::fromRoute('spectra_connect.connect_test', ['spectra_connect' => $entity->id()]),
      ];
    }
    return $operations;
  }

}
