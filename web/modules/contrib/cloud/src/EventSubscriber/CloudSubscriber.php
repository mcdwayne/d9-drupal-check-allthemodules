<?php

namespace Drupal\cloud\EventSubscriber;

use Drupal\Core\Url;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Routing\RouteMatchInterface;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event subscriber class for cloud module.
 */
class CloudSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager instance.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  private $messenger;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  private $routeMatch;

  /**
   * Constructs a new AwsEc2Service object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   An entity type manager instance.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager,
                              MessengerInterface $messenger,
                              TranslationInterface $string_translation,
                              RouteMatchInterface $route_match) {

    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;

    // Setup the $this->t().
    $this->stringTranslation = $string_translation;

    $this->routeMatch = $route_match;
  }

  /**
   * Redirect if there is no cloud service provider.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The filter response event.
   */
  public function redirectIfEmpty(FilterResponseEvent $event) {
    $route_names = [
      'view.cloud_listing.page_1',
      'view.server_template_listing.page_1',
      'entity.cloud_server_template.collection',
    ];
    if (in_array($this->routeMatch->getRouteName(), $route_names)) {
      // Return if not a master request.
      if (!$event->isMasterRequest()) {
        return;
      }

      // Return if not 200.
      $response = $event->getResponse();
      if ($response->getStatusCode() != 200) {
        return;
      }

      $ids = $this->entityTypeManager
        ->getStorage('cloud_config')
        ->getQuery()
        ->execute();

      if (empty($ids)) {
        $this->messenger->addMessage(
          $this->t('There is no cloud service provider. Please create a new one.')
        );
        $response = new RedirectResponse(Url::fromRoute('entity.cloud_config.add_page')->toString());
        $event->setResponse($response);
      }
    }
  }

  /**
   * Get Subscribed events.
   *
   * @return string[]
   *   An array of subscribed events.
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['redirectIfEmpty'];
    return $events;
  }

}
