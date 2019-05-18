<?php

namespace Drupal\fake_path_redirect\EventSubscriber;

use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\redirect\EventSubscriber\RedirectRequestSubscriber;
use Drupal\redirect\Exception\RedirectLoopException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Redirect subscriber for controller requests.
 */
class FakeRedirectRequestSubscriber extends RedirectRequestSubscriber {

  /**
   * {@inheritdoc}
   */
  public function onKernelRequestCheckRedirect(GetResponseEvent $event) {
    $request = clone $event->getRequest();

    if (!$this->checker->canRedirect($request)) {
      return;
    }

    // Get URL info and process it to be used for hash generation.
    parse_str($request->getQueryString(), $request_query);

    // Do the inbound processing so that for example language prefixes are
    // removed.
    $path = $this->pathProcessor->processInbound($request->getPathInfo(), $request);
    $path = ltrim($path, '/');

    $this->context->fromRequest($request);

    try {
      $redirect = $this->redirectRepository->findMatchingRedirect($path, $request_query, $this->languageManager->getCurrentLanguage()->getId());

      if (empty($redirect)) {
        // Try to match a redirect in default language.
        $redirect = $this->redirectRepository->findMatchingRedirect($path, $request_query, $this->languageManager->getDefaultLanguage()->getId());
      }
    }
    catch (RedirectLoopException $e) {
      \Drupal::logger('redirect')->warning($e->getMessage());
      $response = new Response();
      $response->setStatusCode(503);
      $response->setContent('Service unavailable');
      $event->setResponse($response);
      return;
    }

    if (!empty($redirect)) {

      // Handle internal path.
      $url = $redirect->getRedirectUrl();
      if ($this->config->get('passthrough_querystring')) {
        $url->setOption('query', (array) $url->getOption('query') + $request_query);
      }
      $headers = [
        'X-Redirect-ID' => $redirect->id(),
      ];
      $response = new TrustedRedirectResponse($url->setAbsolute()->toString(), $redirect->getStatusCode(), $headers);
      $response->addCacheableDependency($redirect);
      $event->setResponse($response);
    }
  }

}
