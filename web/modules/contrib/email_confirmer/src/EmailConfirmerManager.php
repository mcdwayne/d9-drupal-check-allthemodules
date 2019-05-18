<?php

namespace Drupal\email_confirmer;

use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Email confirmation service.
 */
class EmailConfirmerManager implements EmailConfirmerManagerInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The entity query.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * The email_confirmer config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Construct an EmailConfirmerManager.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The entity query.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(EntityManagerInterface $entity_manager, QueryFactory $entity_query, ConfigFactoryInterface $config_factory) {
    $this->entityManager = $entity_manager;
    $this->entityQuery = $entity_query;
    $this->config = $config_factory->get('email_confirmer.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function confirm($email, array $data = [], $realm = '') {
    $confirmation = $this->getConfirmation($email, 'pending', $realm);
    if (!$confirmation) {
      $confirmation = $this->createConfirmation($email);
    }

    // Set the realm.
    if (!empty($realm)) {
      $confirmation->setRealm($realm);
    }

    // Store properties.
    foreach ($data as $key => $value) {
      $confirmation->setProperty($key, $value);
    }

    $confirmation->sendRequest();
    $confirmation->save();

    return $confirmation;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmations($email, $status = FALSE, $limit = 0, $realm = '') {
    $results = [];
    $confirmation_query = $this->entityQuery->get('email_confirmer_confirmation');

    // Add realm filter condition.
    if (!empty($realm)) {
      $confirmation_query->condition('realm', $realm);
    }

    // Add limit condition.
    if ($limit) {
      $confirmation_query->range(0, $limit);
    }

    // Filter by status.
    switch ($status) {
      case 'expired':
        $confirmation_query->condition('created', REQUEST_TIME - $this->config->get('hash_expiration'), '<');
        break;

      case 'cancelled':
        $confirmation_query->condition('status', EmailConfirmationInterface::CANCELLED);
        break;

      case 'confirmed':
        $confirmation_query->condition('confirmed', EmailConfirmationInterface::CONFIRMED);
        break;

      case 'pending':
        // Non cancelled, confirmed and expired.
        $confirmation_query->condition('status', EmailConfirmationInterface::ACTIVE)
          ->condition('confirmed', EmailConfirmationInterface::UNCONFIRMED)
          ->condition('created', REQUEST_TIME - $this->config->get('hash_expiration'), '>');
        break;
    }

    $old_confirmations = $confirmation_query
      ->condition('email', $email)
      ->sort('created', 'DESC')
      ->execute();

    $confirmation_storage = $this->entityManager->getStorage('email_confirmer_confirmation');
    if (is_array($old_confirmations) && count($old_confirmations)) {
      // Return existent email confirmation.
      $results = array_values($confirmation_storage->loadMultiple($old_confirmations));
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmation($email, $status = FALSE, $realm = '') {
    $confirmation = NULL;
    $confirmations = $this->getConfirmations($email, $status, 1, $realm);
    if (count($confirmations)) {
      $confirmation = $confirmations[0];
    }
    return $confirmation;
  }

  /**
   * {@inheritdoc}
   */
  public function createConfirmation($email) {
    return $this->entityManager->getStorage('email_confirmer_confirmation')->create([
      'email' => $email,
    ]);
  }

}
