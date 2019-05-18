<?php

namespace Drupal\securelogin;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\user\Plugin\Block\UserLoginBlock;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Defines the secure login service.
 */
class SecureLoginManager {

  /**
   * Configured secure login settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs the secure login service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EventDispatcherInterface $event_dispatcher, RequestStack $request_stack) {
    $this->config = $config_factory->get('securelogin.settings');
    $this->eventDispatcher = $event_dispatcher;
    $this->requestStack = $request_stack;
    $this->request = $this->requestStack->getCurrentRequest();
  }

  /**
   * Rewrites the form action to use the secure base URL.
   */
  public function secureForm(&$form) {
    // Rebuild this form on based on the actual URL.
    $form['#cache']['contexts'][] = 'securelogin';
    // Flag form as secure for theming purposes.
    $form['#https'] = TRUE;
    if ($this->request->isSecure()) {
      return;
    }
    // Redirect to secure page, if enabled.
    if ($this->config->get('secure_forms')) {
      // Disable caching, as this form must be rebuilt to set the redirect.
      $form['#cache']['max-age'] = 0;
      $this->secureRedirect();
    }
    $form['#action'] = $this->secureUrl($form['#action']);
  }

  /**
   * Redirects a request to the same path on the secure base URL.
   */
  public function secureRedirect() {
    // Do not redirect from HTTPS requests.
    if ($this->request->isSecure()) {
      return;
    }
    $status = $this->getRedirectStatus();
    // Build the redirect URL from the master request.
    $request = $this->requestStack->getMasterRequest();
    // Request may be a 404 so handle as unrouted URI.
    $url = Url::fromUri("internal:{$request->getPathInfo()}");
    $url->setOption('absolute', TRUE)
      ->setOption('external', FALSE)
      ->setOption('https', TRUE)
      ->setOption('query', $request->query->all());
    // Create listener to set the redirect response.
    $listener = function ($event) use ($url, $status) {
      $response = new TrustedRedirectResponse($url->toString(), $status);
      // Page cache has a fatal error if cached response has no Expires header.
      $response->setExpires(\DateTime::createFromFormat('j-M-Y H:i:s T', '19-Nov-1978 05:00:00 UTC'));
      // Add cache context for this redirect.
      $response->addCacheableDependency(new SecureLoginCacheableDependency());
      $event->setResponse($response);
      // Redirect URL has destination so consider this the final destination.
      $event->getRequest()->query->set('destination', '');
    };
    // Add listener to response event at high priority.
    $this->eventDispatcher->addListener(KernelEvents::RESPONSE, $listener, 222);
  }

  /**
   * Rewrites a URL to use the secure base URL.
   */
  public function secureUrl($url) {
    global $base_path, $base_secure_url;
    // Set the form action to use secure base URL in place of base path.
    if (strpos($url, $base_path) === 0) {
      $base_url = $this->config->get('base_url') ?: $base_secure_url;
      return substr_replace($url, $base_url, 0, strlen($base_path) - 1);
    }
    // Or if a different domain is being used, forcibly rewrite to HTTPS.
    return str_replace('http://', 'https://', $url);
  }

  /**
   * Lazy builder callback; renders a form action URL including destination.
   *
   * @return array
   *   A renderable array representing the form action.
   *
   * @see \Drupal\Core\Form\FormBuilder::renderPlaceholderFormAction()
   */
  public function renderPlaceholderFormAction() {
    $action = UserLoginBlock::renderPlaceholderFormAction();
    $action['#markup'] = $this->secureUrl($action['#markup']);
    return $action;
  }

  /**
   * Determines proper redirect status based on request method.
   */
  public function getRedirectStatus() {
    // Request::isMethodSafe() is deprecated in recent versions of Symfony.
    $method = 'isMethodCacheable';
    if (!method_exists($this->request, $method)) {
      $method = 'isMethodSafe';
    }
    // If necessary, use a 308 redirect to avoid losing POST data.
    return $this->request->$method() ? RedirectResponse::HTTP_MOVED_PERMANENTLY : RedirectResponse::HTTP_PERMANENTLY_REDIRECT;
  }

}
