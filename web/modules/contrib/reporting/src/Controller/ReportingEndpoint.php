<?php

namespace Drupal\reporting\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\reporting\Entity\ReportingEndpointInterface;
use Drupal\reporting\ReportingResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * ReportingEndpoint Controller.
 */
class ReportingEndpoint extends ControllerBase {

  /**
   * The Request Stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $requestStack;

  /**
   * The Logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * Create a new Report URI Controller.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The Request Stack service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The Logger channel.
   */
  public function __construct(RequestStack $requestStack, LoggerInterface $logger) {
    $this->requestStack = $requestStack;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('logger.factory')->get('reporting')
    );
  }

  /**
   * Handle a report submission.
   *
   * @param \Drupal\reporting\Entity\ReportingEndpointInterface $reporting_endpoint
   *   The reporting endpoint.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A Response object.
   */
  public function log(ReportingEndpointInterface $reporting_endpoint) {

    // Return 410: Gone if endpoint is disabled.
    // @see https://w3c.github.io/reporting/#try-delivery
    if (!$reporting_endpoint->status()) {
      return new ReportingResponse(410);
    }

    $request = $this->requestStack->getCurrentRequest();

    // Return 405: Method Not Allowed if not a POST request.
    // This is used instead of the 'methods' property on the route so that an
    // empty response body can be returned instead of a rendered error page.
    if ($request->getMethod() !== 'POST') {
      return new ReportingResponse(405);
    }

    $report = json_decode($request->getContent(), TRUE);

    // Return 400: Bad Request if content cannot be parsed.
    if (empty($report) || json_last_error() != JSON_ERROR_NONE) {
      return new ReportingResponse(400);
    }

    switch ($request->headers->get('Content-Type')) {
      case 'application/reports+json':
        $this->storeReportToData($reporting_endpoint, $report);
        break;

      case 'application/csp-report':
        $this->storeReportUriData($reporting_endpoint, $report, $request);
        break;

      default:
        // 415: Unsupported Media Type.
        return new ReportingResponse(415);
    }

    // 202: Accepted.
    return new ReportingResponse(202);
  }

  /**
   * Helper to log CSP report sent via report-uri directive.
   *
   * @param \Drupal\reporting\Entity\ReportingEndpointInterface $reporting_endpoint
   *   The reporting endpoint.
   * @param array $report
   *   The parsed report request body.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The Request object.
   */
  private function storeReportUriData(ReportingEndpointInterface $reporting_endpoint, array $report, Request $request) {

    // Convert data to format expected by Reporting API.
    $report = [
      'type' => 'csp',
      'age' => 0,
      'url' => $report['csp-report']['document-uri'],
      'user_agent' => $request->headers->get('User-Agent'),
      'body' => $report['csp-report'],
    ];

    $this->logger
      ->info("@endpoint <br/>\n<pre>@data</pre>", [
        '@endpoint' => $reporting_endpoint->id(),
        '@data' => json_encode($report, JSON_PRETTY_PRINT),
      ]);
  }

  /**
   * Helper to log reports sent to Report-To endpoint.
   *
   * @param \Drupal\reporting\Entity\ReportingEndpointInterface $reporting_endpoint
   *   The reporting endpoint.
   * @param array $report
   *   The parsed report request body.
   */
  private function storeReportToData(ReportingEndpointInterface $reporting_endpoint, array $report) {

    foreach ($report as $item) {
      $this->logger
        ->info("@endpoint <br/>\n<pre>@data</pre>", [
          '@endpoint' => $reporting_endpoint->id(),
          '@data' => json_encode($item, JSON_PRETTY_PRINT),
        ]);
    }

  }

}
