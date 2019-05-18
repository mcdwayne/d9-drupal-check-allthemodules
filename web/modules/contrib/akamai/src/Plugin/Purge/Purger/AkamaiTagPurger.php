<?php

namespace Drupal\akamai\Plugin\Purge\Purger;

use Drupal\akamai\Event\AkamaiPurgeEvents;
use Drupal\purge\Plugin\Purge\Purger\PurgerBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;

/**
 * Akamai Tag Purger.
 *
 * @PurgePurger(
 *   id = "akamai_tag",
 *   label = @Translation("Akamai Tag Purger"),
 *   description = @Translation("Provides a Purge service for Akamai Fast Purge Cache Tags."),
 *   types = {"tag"},
 *   configform = "Drupal\akamai\Form\ConfigForm",
 * )
 */
class AkamaiTagPurger extends PurgerBase {


  /**
   * Web services client for Akamai API.
   *
   * @var \Drupal\akamai\AkamaiClient
   */
  protected $client;

  /**
   * Akamai client config.
   *
   * @var \Drupal\Core\Config
   */
  protected $akamaiClientConfig;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * Constructs a \Drupal\Component\Plugin\AkamaiPurger.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The factory for configuration objects.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config, EventDispatcherInterface $event_dispatcher) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->client = \Drupal::service('akamai.client.factory')->get();
    $this->akamaiClientConfig = $config->get('akamai.settings');
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function getTimeHint() {
    // The max value for getTimeHint is 10.00.
    $return = $this->akamaiClientConfig->get('timeout') <= 10 ?: 10;
    return (float) $return;
  }

  /**
   * {@inheritdoc}
   */
  public function invalidate(array $invalidations) {
    // Build array of tag strings.
    $tags_to_clear = [];
    // Get Cache Tag formatter.
    $formatter = \Drupal::service('akamai.helper.cachetagformatter');
    foreach ($invalidations as $invalidation) {
      $invalidation->setState(InvalidationInterface::PROCESSING);
      $tags_to_clear[] = $formatter->format($invalidation->getExpression());
    }
    // Remove duplicate entries.
    $tags_to_clear = array_keys(array_flip($tags_to_clear));
    // Set invalidation type to tag.
    $this->client->setType('tag');

    // Instantiate event and alter tags with subscribers.
    $event = new AkamaiPurgeEvents($tags_to_clear);
    $this->eventDispatcher->dispatch(AkamaiPurgeEvents::PURGE_CREATION, $event);
    $tags_to_clear = $event->data;

    // Purge tags.
    $invalidation_state = InvalidationInterface::SUCCEEDED;
    $result = $this->client->purgeTags($tags_to_clear);
    if (!$result) {
      $invalidation_state = InvalidationInterface::FAILED;
    }
    // Set Invalidation status.
    foreach ($invalidations as $invalidation) {
      $invalidation->setState($invalidation_state);
    }
  }

  /**
   * Use a static value for purge queuer performance.
   *
   * @see parent::hasRunTimeMeasurement()
   */
  public function hasRuntimeMeasurement() {
    return FALSE;
  }

}
