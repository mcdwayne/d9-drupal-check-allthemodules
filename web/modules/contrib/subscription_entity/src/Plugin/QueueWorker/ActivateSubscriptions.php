<?php

namespace Drupal\subscription_entity\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;

use Drupal\subscription_entity\Entity\SubscriptionTerm;
use Drupal\subscription_entity\SubscriptionLoaderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Activates subscriptions that are pending and assigns the user role they need.
 *
 * @QueueWorker(
 *   id = "activate_subscriptions",
 *   title = @Translation("Activates subscription entities based on term info"),
 *   cron = {"time" = 30}
 * )
 */
class ActivateSubscriptions extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  protected $subscriptionLoader;

  /**
   * ActivateSubscriptions constructor.
   *
   * @param \Drupal\subscription_entity\SubscriptionLoaderInterface $subscriptionLoader
   *   SubscriptionLoader service.
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
    $subscription_term->activateTerm();
    $subscription_term->save();
  }

}
