<?php

namespace Drupal\reporting\EventSubscriber;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\reporting\ReportingResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class ReportToSubscriber.
 */
class ResponseSubscriber implements EventSubscriberInterface {

  /**
   * The Entity Type Manager Service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * A cache bin.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  private $cache;

  /**
   * ResponseSubscriber constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The Entity Type Manager service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   A cache bin.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    CacheBackendInterface $cache
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::RESPONSE => [
        ['reportingResponses', 9000],
        ['addReportToHeader', 0],
      ],
    ];
  }

  /**
   * Prevent other response event listeners from altering reporting responses.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The response event.
   */
  public function reportingResponses(FilterResponseEvent $event) {
    if (!$event->isMasterRequest()) {
      return;
    }

    if ($event->getResponse() instanceof ReportingResponse) {
      $event->stopPropagation();
    }
  }

  /**
   * Add report-to header to the response.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The response event.
   */
  public function addReportToHeader(FilterResponseEvent $event) {
    if (!$event->isMasterRequest()) {
      return;
    }

    $cid = 'reporting.header';
    $header = [];

    if (($cacheData = $this->cache->get($cid))) {
      $header = $cacheData->data;
    }
    else {
      try {
        $entityStorage = $this->entityTypeManager->getStorage('reporting_endpoint');
      }
      catch (PluginException $e) {
        watchdog_exception('reporting', $e);
        return;
      }

      $query = $entityStorage->getQuery()
        ->condition('status', TRUE);

      if (($result = $query->execute())) {
        $endpoints = $entityStorage->loadMultiple($result);

        foreach ($endpoints as $endpoint) {
          try {
            // TODO Can local urls be relative?
            $url = $endpoint->toUrl('log', ['absolute' => TRUE])->toString();
          }
          catch (EntityMalformedException $e) {
            watchdog_exception('reporting', $e);
            continue;
          }
          $header[] = [
            'group' => $endpoint->id(),
            // TODO make max_age a property of config entity?
            'max_age' => 86400,
            'endpoints' => [['url' => $url]],
          ];
        }
      }

      $this->cache->set($cid, $header, Cache::PERMANENT, ['config:reporting_endpoint_list']);
    }

    if (!empty($header)) {
      // The header’s value is interpreted as a JSON-formatted array of objects
      // without the outer [ and ].
      // @see https://w3c.github.io/reporting/#header
      $headerString = trim(json_encode($header), '[]');

      $event->getResponse()->headers->set('Report-To', $headerString);
    }
  }

}
