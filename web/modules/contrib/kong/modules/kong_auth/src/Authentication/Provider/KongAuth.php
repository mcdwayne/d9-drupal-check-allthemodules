<?php

namespace Drupal\kong_auth\Authentication\Provider;

use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\kong_auth\Authentication\ConsumerAccountInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class KongAuth.
 */
class KongAuth implements AuthenticationProviderInterface {

  /**
   * The consumer account service.
   *
   * @var \Drupal\kong_auth\Authentication\ConsumerAccountInterface
   */
  protected $consumerAccount;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a HTTP basic authentication provider object.
   *
   * @param \Drupal\kong_auth\Authentication\ConsumerAccountInterface $consumer_account
   *   The consumer account.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ConsumerAccountInterface $consumer_account, EntityTypeManagerInterface $entity_type_manager) {
    $this->consumerAccount = $consumer_account;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    return $request->isFromTrustedProxy() && $request->headers->has('X-Consumer-ID') && $request->headers->has('X-Consumer-Custom-ID');
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(Request $request) {
    if ($this->entityTypeManager->getStorage('consumer')
      ->load($request->headers->get('X-Consumer-Custom-ID'))) {
      $this->consumerAccount->setRequest($request);
      return $this->consumerAccount;
    }

    return NULL;
  }

}
