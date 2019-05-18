<?php /**
 * @file
 * Contains \Drupal\miniorange_saml\EventSubscriber\InitSubscriber.
 */

namespace Drupal\miniorange_saml\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class InitSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [KernelEvents::REQUEST => ['onEvent', 0]];
  }

  public function onEvent() {
    global $base_url;
    $relay_state = $base_url . '/' . \Drupal\Core\Url::fromRoute("<current>")->toString();
    $force_auth = \Drupal::config('miniorange_saml.settings')->get('miniorange_saml_force_auth');
    $enable_saml_login = \Drupal::config('miniorange_saml.settings')->get('miniorange_saml_enable_login');
    if ($enable_saml_login) {
      if ($force_auth && !\Drupal::currentUser()->isAuthenticated()) {
        saml_login($relay_state);

		}
    }
  }

}
