<?php

namespace Drupal\akismet\EventSubscriber;

use Drupal\Core\Url;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\akismet\Utility\Logger;

class Subscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {

    // Set a low value to start as early as possible.
    $events[KernelEvents::REQUEST][] = array('onRequest', -100);

    // Why only large positive value works here?
    $events[KernelEvents::TERMINATE][] = array('onTerminate', 1000);

    return $events;
  }

  /**
   * Implements hook_init().
   */
  function onRequest() {
    // On all Akismet administration pages, check the module configuration and
    // display the corresponding requirements error, if invalid.
    $url = Url::fromRoute('<current>');
    $current_path = $url->toString();
    if (empty($_POST) && strpos($current_path, 'admin/config/content/akismet') === 0 && \Drupal::currentUser()->hasPermission('administer akismet')) {
      // Re-check the status on the settings form only.
      $status = \Drupal\akismet\Utility\AkismetUtilities::getAPIKeyStatus($current_path == 'admin/config/content/akismet/settings');
      if ($status !== TRUE) {
        // Fetch and display requirements error message, without re-checking.
        module_load_install('akismet');
        $requirements = akismet_requirements('runtime', FALSE);
        if (isset($requirements['akismet']['description'])) {
          \Drupal::messenger()->addMessage($requirements['akismet']['description'], 'error');
        }
      }
    }
  }

  /**
   * Implements after all other processing.
   */
  function onTerminate() {
    Logger::writeLog();
  }


}
