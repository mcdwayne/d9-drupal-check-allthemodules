<?php

namespace Drupal\commerce_amazon_lpa\Plugin\Commerce\Condition;

use Drupal\commerce\Plugin\Commerce\Condition\ConditionBase;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the currency condition for orders.
 *
 * @CommerceCondition(
 *   id = "amazon_order",
 *   label = @Translation("Amazon order"),
 *   display_label = @Translation("Is an Amazon order"),
 *   category = @Translation("Amazon Pay"),
 *   entity_type = "commerce_order",
 * )
 */
class AmazonOrder extends ConditionBase implements ContainerFactoryPluginInterface {

  /**
   * The Amazon Pay settings.
   *
   * @var array|\Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig|mixed|null
   */
  protected $amazonPaySettings;

  /**
   * Constructions a new AmazonOrder object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   The config.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactory $config) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->amazonPaySettings = $config->get('commerce_amazon_lpa.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(EntityInterface $entity) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $entity */
    $this->assertEntity($entity);
    if (!$entity->hasField('amazon_order_reference')) {
      return FALSE;
    }
    $environment_prefix = $this->amazonPaySettings->get('mode') == 'test' ? 'S' : 'P';
    $amazon_order_reference = $entity->get('amazon_order_reference')->value;
    return !empty($amazon_order_reference) && substr($amazon_order_reference, 0, 1) == $environment_prefix;
  }

}
