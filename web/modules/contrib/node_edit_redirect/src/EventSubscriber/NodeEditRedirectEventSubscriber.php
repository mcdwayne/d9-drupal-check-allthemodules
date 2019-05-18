<?php

namespace Drupal\node_edit_redirect\EventSubscriber;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;

/**
 * Node edit redirect event subscriber.
 */
class NodeEditRedirectEventSubscriber implements EventSubscriberInterface {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new class object.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(LanguageManagerInterface $language_manager, RouteMatchInterface $route_match) {
    $this->languageManager = $language_manager;
    $this->routeMatch = $route_match;
  }

  /**
   * Event subscriber callback.
   *
   * Redirects to the language of the node that is being edited.
   *
   * @param GetResponseEvent $event
   *   The response event object.
   */
  public function redirectToLanguage(GetResponseEvent $event) {
    if ($this->routeMatch->getRouteName() == 'entity.node.edit_form') {
      // Find out what the negotiated content language is.
      $current_content_langcode = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
      // Find out what the language of the node that's being edited is.
      $node = $this->routeMatch->getParameter('node');
      $node_language = $node->language();
      $node_langcode = $node_language->getId();
      if ($node_langcode && $node_langcode != LanguageInterface::LANGCODE_NOT_APPLICABLE && $node_langcode != LanguageInterface::LANGCODE_NOT_SPECIFIED && $node_langcode != $current_content_langcode) {
        $request = $event->getRequest();
        $options = [];
        // Redirect to the language of the node.
        $options['language'] = $node_language;
        // Retain the current query string.
        $query = $request->query->all();
        $options['query'] = UrlHelper::filterQueryParameters($query);
        $url = Url::fromRoute('entity.node.edit_form', ['node' => $node->id()], $options)->setAbsolute()->toString();
        // Prevent the redirection from redirecting to the destination.
        $request->query->remove('destination');
        // Redirect to the right prefix/domain.
        $event->setResponse(new RedirectResponse($url));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('redirectToLanguage');
    return $events;
  }

}
