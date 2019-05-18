<?php

namespace Drupal\akamai\EventSubscriber;

use Drupal\akamai\Event\AkamaiHeaderEvents;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\akamai\Helper\CacheTagFormatter;

/**
 * Add cache tags headers on cacheable responses, for external caching systems.
 */
class CacheableResponseSubscriber implements EventSubscriberInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Cache tag formatter.
   *
   * @var \Drupal\akamai\Helper\CacheTagFormatter
   */
  protected $tagFormatter;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a new CacheableResponseSubscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\akamai\Helper\CacheTagFormatter $formatter
   *   The cache tag formatter.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(ConfigFactoryInterface $config_factory, CacheTagFormatter $formatter, EventDispatcherInterface $event_dispatcher) {
    $this->configFactory = $config_factory;
    $this->tagFormatter = $formatter;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onRespond'];
    return $events;
  }

  /**
   * Add cache tags header on cacheable responses.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   */
  public function onRespond(FilterResponseEvent $event) {
    if (!$event->isMasterRequest()) {
      return;
    }

    $response = $event->getResponse();
    $config = $this->configFactory->get('akamai.settings');
    $header = $config->get('edge_cache_tag_header');

    // Send headers if response is cacheable and the setting is enabled.
    if ($header && $response instanceof CacheableResponseInterface) {
      $tags = $response->getCacheableMetadata()->getCacheTags();
      $blacklist = $config->get('edge_cache_tag_header_blacklist');
      $blacklist = is_array($blacklist) ? $blacklist : [];
      $tags = array_filter($tags, function ($tag) use ($blacklist) {
        foreach ($blacklist as $prefix) {
          if (strpos($tag, $prefix) !== FALSE) {
            return FALSE;
          }
        }
        return TRUE;
      });

      // Instantiate our event.
      $event = new AkamaiHeaderEvents($tags);
      $this->eventDispatcher->dispatch(AkamaiHeaderEvents::HEADER_CREATION, $event);
      $tags = $event->data;
      foreach ($tags as &$tag) {
        $tag = $this->tagFormatter->format($tag);
      }
      $response->headers->set('Edge-Cache-Tag', implode(',', $tags));
    }
  }

}
