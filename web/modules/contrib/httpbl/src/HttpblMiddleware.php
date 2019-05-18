<?php

namespace Drupal\httpbl;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Provides a HTTP middleware to implement IP based banning.
 */
class HttpblMiddleware implements HttpKernelInterface {

  /**
   * The decorated kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * The Httpbl Evaluator.
   *
   * @var \Drupal\httpbl\HttpblEvaluatorInterface
   */
  protected $httpblEvaluator;

  /**
   * The Httpbl Response.
   *
   * @var \Drupal\httpbl\HttpblResponseInterface
   */
  protected $httpblResponse;

  /**
   * Constructs a HttpblMiddleware object.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The decorated kernel.
   * @param \Drupal\httpbl\HttpblEvaluatorInterface           $httpblEvaluator
   *   The httpbl IP evaluator.
   * @param \Drupal\httpbl\HttpblResponseInterface           $httpblResponse
   *   The httpbl response builder.
   *
   */
  public function __construct(HttpKernelInterface $http_kernel, HttpblEvaluatorInterface $httpblEvaluator, HttpblResponseInterface $httpblResponse) {
    $this->httpKernel = $http_kernel;
    $this->httpblEvaluator = $httpblEvaluator;
    $this->httpblResponse = $httpblResponse;
  }

  /**
   * {@inheritdoc}
   *
   * Primary request handler for Httpbl.
   */
  public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = TRUE) {

    // IMPORTANT - Don't move or remove this line.
    $defaultResponse = $this->httpKernel->handle($request, $type, $catch);
    // If for any reason default response is already not "OK", then just return that
    // and save precious time.
    // Possible scenarios:
    //   #1 - IP found in Ban and already sent a 403.
    //   #2 - Requests a URL already access denied.
    if ($defaultResponse->getStatusCode() != 200) {
      return $defaultResponse;
    }

    // Are we configured to perform checks on all page requests?
    // If not, there really isn't anything to do here.
    if ($this->httpblEvaluator->getPageRequestOption()) {
      $requestUri = $request->getRequestUri();
      $ip = $request->getClientIp();

      // No Project Honeypot support for IPv6 addresses.
      // If this is not an IPv4, set to skip evaluation.
      $project_supported = TRUE;
      if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $project_supported = FALSE;
      }

      if (!isset($evaluated)) {
        // Evaluate this visitor IP.
        $evaluated = $this->httpblEvaluator->evaluateVisitor($ip, $request, $project_supported);
      }

      // If visitor was evaluated as greylisted
      if ($evaluated[1] == HTTPBL_LIST_GREY ) {

        // Check to see if they've been set up for a session white-list challenge.
        // If true, this is the only "page" this grey-listed visitor is allowed
        // to see, until they successfully take the challenge and get white-listed
        // on a session only basis (their status will not change in the httpbl table).
        //
        // Should they fail the challenge, they are blacklisted.
        if ((isset($_SESSION['httpbl_challenge']) && $_SESSION['httpbl_challenge']) && $requestUri == '/httpbl/whitelist') {
          
          // Return default response for access to challenge.
          return $defaultResponse;
        }
      }

      // Response will build a new response, based on evaluation results.
      $httpblResponse = $this->httpblResponse->buildHttpblResponse($ip, $request, $defaultResponse, $evaluated);

      // If there is a new response and it does not contain an OK status,
      // then return the blocked response.
      if (isset($httpblResponse) && $httpblResponse->getStatusCode() != 200) {
        return $httpblResponse;
      }
    }

    // Otherwise return the default "original" response.
    return $defaultResponse;

  }

}

