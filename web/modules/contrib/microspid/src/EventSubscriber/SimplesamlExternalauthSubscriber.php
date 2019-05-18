<?php

namespace Drupal\microspid\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\user\UserInterface;
use Drupal\microspid\Service\MicrospidDrupalAuth;
use Drupal\microspid\Service\SpidPaswManager;
use Drupal\externalauth\Event\ExternalAuthEvents;
use Drupal\externalauth\Event\ExternalAuthLoginEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Psr\Log\LoggerInterface;

/**
 * Event subscriber subscribing to ExternalAuthEvents.
 */
class SimplesamlExternalauthSubscriber implements EventSubscriberInterface {

  /**
   * The SimpleSAML Authentication helper service.
   *
   * @var \Drupal\microspid\Service\SpidPaswManager
   */
  protected $simplesaml;

  /**
   * The SimpleSAML Drupal Authentication service.
   *
   * @var \Drupal\microspid\Service\MicrospidDrupalAuth
   */
  public $simplesamlDrupalauth;

  /**
   * A configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;


  /**
   * {@inheritdoc}
   *
   * @param SpidPaswManager $simplesaml
   *   The SimpleSAML Authentication helper service.
   * @param MicrospidDrupalAuth $simplesaml_drupalauth
   *   The SimpleSAML Drupal Authentication service.
   * @param ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(SpidPaswManager $simplesaml, MicrospidDrupalAuth $simplesaml_drupalauth, ConfigFactoryInterface $config_factory, LoggerInterface $logger) {
    $this->simplesaml = $simplesaml;
    $this->simplesamlDrupalauth = $simplesaml_drupalauth;
    $this->config = $config_factory->get('microspid.settings');
    $this->logger = $logger;
  }

  /**
   * React on an ExternalAuth login event.
   *
   * @param ExternalAuthLoginEvent $event
   *   The subscribed event.
   */
  public function externalauthLogin(ExternalAuthLoginEvent $event) {
    if ($event->getProvider() == "microspid") {

      if (!$this->simplesaml->isActivated()) {
        return;
      }

      if (!$this->simplesaml->isAuthenticated()) {
        return;
      }

      $account = $event->getAccount();
      $this->simplesamlDrupalauth->synchronizeUserAttributes($account);

      // Invoke a hook to let other modules alter the user account based on
      // MicroSPiD attributes.
      $account_altered = FALSE;
      $attributes = $this->simplesaml->getAttributes();
      foreach (\Drupal::moduleHandler()->getImplementations('microspid_user_attributes') as $module) {
        $return_value = \Drupal::moduleHandler()->invoke($module, 'microspid_user_attributes', [$account, $attributes]);
        if ($return_value instanceof UserInterface) {
          if ($this->config->get('debug')) {
            $this->logger->debug('Drupal user attributes have altered based on SAML attributes by %module module.', [
              '%module' => $module,
            ]);
          }
          $account_altered = TRUE;
          $account = $return_value;
        }
      }

      if ($account_altered) {
        $account->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ExternalAuthEvents::LOGIN][] = ['externalauthLogin'];
    return $events;
  }

}
