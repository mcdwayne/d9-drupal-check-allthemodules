<?php

namespace Drupal\dialect\EventSubscriber;

use Drupal\dialect\DialectManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Redirects excluded or fallback languages.
 */
class RedirectLanguage implements EventSubscriberInterface {

  /**
   * Drupal\dialect\DialectManager definition.
   *
   * @var \Drupal\dialect\DialectManager
   */
  protected $dialectManager;

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configurationFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(DialectManager $dialect_manager) {
    $this->dialectManager = $dialect_manager;
  }

  /**
   * Checks the current language and redirect to the fallback node if necessary.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   GetResponseEvent.
   */
  public function redirect(GetResponseEvent $event) {
    $currentLanguageId = $this->dialectManager->getCurrentLanguageId();

    // Redirect excluded languages to the default language on the front page.
    $excludedLanguages = $this->dialectManager->getExcludedLanguageIds();
    try {
      if (!empty($excludedLanguages)) {
        if (in_array($currentLanguageId, $excludedLanguages) &&
          $this->dialectManager->isRedirectPage()) {
          $event->setResponse($this->dialectManager->getFrontPageRedirectResponse());
        }
      }
    }
    catch (\Exception $e) {
      drupal_set_message($e->getMessage(), 'error');
    }

    // Check if current language is in the fallback subset.
    if ($this->dialectManager->isFallbackLanguage($currentLanguageId)) {
      // Check that a fallback node exists.
      if ($this->dialectManager->fallbackNodeExists($currentLanguageId)) {
        // Check if the current node is not the fallback node
        // and if the page must be redirected.
        if (!$this->dialectManager->isCurrentNodeFallback()
            && $this->dialectManager->isRedirectPage()) {
          try {
            $event->setResponse($this->dialectManager->getFallbackNodeRedirectResponse());
          }
          catch (\Exception $e) {
            \Drupal::logger('dialect')->error($e->getMessage());
          }
        }
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['redirect'];
    return $events;
  }

}
