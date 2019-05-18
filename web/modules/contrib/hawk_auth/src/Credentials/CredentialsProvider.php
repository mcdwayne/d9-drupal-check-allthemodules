<?php

/**
 * @file
 * Contains \Drupal\hawk_auth\Credentials\CredentialsProvider.
 */

namespace Drupal\hawk_auth\Credentials;

use Dragooon\Hawk\Credentials\Credentials;
use Dragooon\Hawk\Credentials\CredentialsNotFoundException;
use Dragooon\Hawk\Credentials\CredentialsProviderInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\hawk_auth\Entity\HawkCredentialInterface;

/**
 * Credentials callback for Hawk server while authenticating.
 *
 * This is called by Dragooon\Hawk\Server\Server::authenticate to load
 * a user by ID when a Hawk authentication request is made, it loads with
 * respect to Hawk credential's ID and not the User's ID.
 */
class CredentialsProvider implements CredentialsProviderInterface {

  /**
   * Entity manager.
   *
   * @var EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a CredentialsProvider object.
   *
   * @param EntityManagerInterface $entity_manager
   *   Entity Manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function loadCredentialsById($id) {
    /** @var HawkCredentialInterface $credential */
    $credential = $this->entityManager->getStorage('hawk_credential')->load($id);

    if (empty($credential)) {
      throw new CredentialsNotFoundException($id . ' is not a valid credential ID');
    }

    return $credential;
  }

}
