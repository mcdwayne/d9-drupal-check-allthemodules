<?php

namespace Drupal\aegir_site_subscriptions_recurly\Plugin\SubscriptionProvider;

use Drupal\aegir_site_subscriptions\Plugin\SubscriptionProviderBase;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\recurly\RecurlyClient;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The Recurly subscription provider plugin.
 *
 * @SubscriptionProvider(
 *   id = "recurly",
 *   label = @Translation("Recurly"),
 * )
 */
class Recurly extends SubscriptionProviderBase implements ContainerFactoryPluginInterface {

  /**
   * The Recurly configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $recurlyConfig;

  /**
   * The Recurly client service.
   *
   * @var \Drupal\recurly\RecurlyClient
   */
  protected $recurlyClient;

  /**
   * Factory method.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return Recurly
   *   An instance of this class.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')->get('recurly.settings'),
      $container->get('recurly.client')
    );
  }

  /**
   * Class constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ImmutableConfig $recurly_config
   *   The Recurly configuration.
   * @param \Drupal\recurly\RecurlyClient $recurly_client
   *   The Recurly client service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ImmutableConfig $recurly_config, RecurlyClient $recurly_client) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->recurlyConfig = $recurly_config;
    $this->recurlyClient = $recurly_client;
  }

  /**
   * {@inheritdoc}
   */
  public function redirectFormToUserSubscriptionsPage(FormStateInterface $form_state) {
    $entity_type_id = $this->recurlyConfig->get('recurly_entity_type') ?: 'user';
    $user_id = $form_state->getFormObject()->getEntity()->getOwner()->id();
    $form_state->setRedirect("entity.$entity_type_id.recurly_subscriptionlist", [
      $entity_type_id => $user_id,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getSubscriptionUrl($subscription_id) {
    $subdomain = $this->recurlyConfig->get('recurly_subdomain');
    return 'https://' . $subdomain . '.recurly.com/subscriptions/' . $subscription_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubscriptionInstance($subscription_id) {
    return \Recurly_Subscription::get($subscription_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getDrupalUserId($subscription_provider_user_id) {
    return recurly_account_load([
      'account_code' => $subscription_provider_user_id,
    ], TRUE)->entity_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubscriptionAddons($subscription) {
    if (!is_a($subscription, '\Recurly_Subscription')) {
      throw new \Exception("Subscription must be a Recurly subscription object; it isn't.");
    }

    $add_ons = [];
    foreach ($subscription->subscription_add_ons as $addon_object) {
      $add_ons[] = [
        'code' => $addon_object->add_on_code,
        'quantity' => $addon_object->quantity,
      ];
    }
    return $add_ons;
  }

  /**
   * {@inheritdoc}
   */
  public function subscriptionIsActive($subscription) {
    return ($subscription->state == 'active') ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubscriptionId($subscription) {
    return $subscription->uuid;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubscriptionPlanCode($subscription) {
    return $subscription->plan->plan_code;
  }

}
