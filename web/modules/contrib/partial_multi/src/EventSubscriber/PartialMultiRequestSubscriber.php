<?php

namespace Drupal\partial_multi\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to Request events and redirects them if necessary.
 *
 * Redirects to the page source language if it is a lc/node/* page (with
 * language code lc) and the language is not available on this node.
 */
class PartialMultiRequestSubscriber implements EventSubscriberInterface {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The path processor.
   *
   * @var \Drupal\Core\PathProcessor\InboundPathProcessorInterface
   */
  protected $pathProcessor;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a PartialMultiRequestSubscriber object.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\PathProcessor\InboundPathProcessorInterface $path_processor
   *   The path processor service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(LanguageManagerInterface $language_manager, EntityManagerInterface $entity_manager, InboundPathProcessorInterface $path_processor, ConfigFactoryInterface $config_factory) {
    $this->languageManager = $language_manager;
    $this->entityManager = $entity_manager;
    $this->pathProcessor = $path_processor;
    $this->configFactory = $config_factory;
  }

  /**
   * Checks to see if a redirect is needed, and if so, redirects.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event to process.
   */
  public function onKernelRequestCheckRedirect(GetResponseEvent $event) {
    // Clone the request to make sure we don't alter anything on it, and
    // figure out the language and path.
    $request = clone $event->getRequest();
    $path = $this->pathProcessor->processInbound($request->getPathInfo(), $request);
    $page_langcode = $this->languageManager->getCurrentLanguage()->getId();

    // See if we need to redirect.
    $matches = [];
    if (!preg_match('|^/node/(\d+)$|', $path, $matches)) {
      // Not a node page, so we don't care about it.
      return;
    }

    $nid = $matches[1];
    $node = $this->entityManager->getStorage('node')->load($nid);
    if (!$node) {
      // Not a valid node. Let something else handle the 404.
      return;
    }

    foreach ($node->getTranslationLanguages() as $language) {
      if ($language->getId() == $page_langcode) {
        // This node has a translation in the current page language, so current
        // URL is OK.
        return;
      }
    }

    // If we get here, we need to redirect, because this is a node page and
    // there is no translation to the current page language. Redirect to the
    // source language of this node.
    $config = $this->configFactory->get('partial_multi.settings');
    $node = $node->getUntranslated();
    $language = $node->language();
    $url = $node->toUrl()
      ->setOption('language', $language);
    $response = new TrustedRedirectResponse($url->setAbsolute()->toString(), $config->get('redirect_code'));
    $event->setResponse($response);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // This needs to run after RouterListener::onKernelRequest(), which has
    // a priority of 32, to avoid trying to process unknown routes.
    $events[KernelEvents::REQUEST][] = ['onKernelRequestCheckRedirect', 31];
    return $events;
  }

}
