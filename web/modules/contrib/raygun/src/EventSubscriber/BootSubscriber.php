<?php
/**
 * @file
 * Contains \Drupal\raygun\EventSubscriber\BootSubscriber.
 */

namespace Drupal\raygun\EventSubscriber;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use \Symfony\Component\HttpKernel\Event\GetResponseEvent;
use \Raygun4php\RaygunClient;


/**
 * BootSubscriber event subscriber.
 *
 * @package Drupal\raygun\EventSubscriber
 */
class BootSubscriber extends ControllerBase implements EventSubscriberInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactory $configFactory) {
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [KernelEvents::REQUEST => ['onEvent', 0]];
  }

  public function onEvent(GetResponseEvent $event) {
    $config = $this->configFactory->get('raygun.settings');
    $apikey = $config->get('apikey');
    if (!empty($apikey)) {
      $user = \Drupal::currentUser();
      global $raygunClient;
      $raygunClient = new RaygunClient($config->get('apikey'), (bool) $config->get('async_sending'));

      if ($config->get('send_version') && $config->get('application_version') != '') {
        $raygunClient->SetVersion($config->get('application_version'));
      }
      if ($config->get('send_email') && $user->id()) {
        $raygunClient->SetUser($user->getEmail());
      }
      if ($config->get('exceptions')) {
        set_exception_handler('raygun_exception_handler');
      }
      if ($config->get('error_handling')) {
        set_error_handler('raygun_error_handler');
        register_shutdown_function('raygun_fatal_error');
      }
    }
  }
}
