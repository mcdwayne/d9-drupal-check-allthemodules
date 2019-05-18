<?PHP

/**
 * @file
 * Contains \Drupal\acquia_geoip_vary_by_country\EventSubscriber\SetVaryHeader.
 */

namespace Drupal\acquia_geoip_vary_by_country\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Adds the X-Geo-Country header to Drupal's Vary response header so that your site gets varied by content.
 */
class SetVaryHeader implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public function onRespond(FilterResponseEvent $event) {
    $response = $event->getResponse();
    $response->headers->set('Vary', 'X-Geo-Country');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onRespond'];
    return $events;
  }

}