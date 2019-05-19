<?php

namespace Drupal\subscription_entity\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\subscription_entity\Entity\SubscriptionTerm;
use Drupal\subscription_entity\SubscriptionLoaderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deactivates subscriptions that are active and removes the user's role.
 *
 * @QueueWorker(
 *   id = "deactivate_subscriptions",
 *   title = @Translation("Deactivates subscription entities based on term info"),
 *   cron = {"time" = 30}
 * )
 */
class DeActivateSubscriptions extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  protected $entityTypeManager;

  protected $subscriptionLoader;

  /**
   * DeActivateSubscriptions constructor.
   *
   * Sets the subscriptionLoader property.
   *
   * @param \Drupal\subscription_entity\SubscriptionLoaderInterface $subscriptionLoader
   *   The subscription loader service.
   */
  public function __construct(SubscriptionLoaderInterface $subscriptionLoader) {
    $this->subscriptionLoader = $subscriptionLoader;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('subscription_entity.subscription_entity_loader')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    /** @var SubscriptionTerm $subscription_term */
    $subscription_term = $this->subscriptionLoader->loadSubscriptionTermById($data->id);
    $subscription_term->deActivateTerm();
    $subscription_term->save();
  }

}
