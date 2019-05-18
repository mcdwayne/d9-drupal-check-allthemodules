<?php

namespace Drupal\email_confirmer\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\RequeueException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityManagerInterface;

/**
 * Process queued confirmation requests on CRON run.
 *
 * @QueueWorker(
 *   id = "email_confirmer_requests",
 *   title = @Translation("Delayed confirmation request dispatcher"),
 *   cron = {"time" = 5}
 * )
 */
class DelayedRequestDispatcher extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Already processed items.
   *
   * @var array
   */
  protected $already;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Creates a new DelayedRequestDispatcher object.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->already = [];
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    // Ignore dupes.
    if (isset($this->already[$data])) {
      return;
    }

    // Delivery pendings requests.
    /** @var \Drupal\email_confirmer\EmailConfirmationInterface $confirmation */
    $confirmation = $this->entityManager->getStorage('email_confirmer_confirmation')->load($data);
    if (!$confirmation) {
      return;
    }

    if ($confirmation->getCreatedTime() + intval(\Drupal::config('email_confirmer.settings')->get('resendrequest_delay')) < REQUEST_TIME) {
      throw new RequeueException('Early confirmation request re-send');
    }

    // Remember already processed.
    $this->already[$data] = $data;

    // Deliver the confirmation request.
    if ($confirmation->isPending()
      && $confirmation->setLastRequestDate(NULL)->sendRequest()) {
      $confirmation->save();
    }
  }

}
