<?php

namespace Drupal\csp\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Report URI Controller.
 *
 * @package Drupal\csp\Controller
 */
class ReportUri implements ContainerInjectionInterface {

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
      $container->get('logger.channel.csp')
    );
  }

  /**
   * Handle a report submission.
   *
   * @param string $type
   *   The report type.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   An empty response.
   */
  public function log($type) {
    $validTypes = ['enforce', 'reportOnly'];

    if (!in_array($type, $validTypes)) {
      return new Response('', 404);
    }

    $reportJson = $this->requestStack->getCurrentRequest()->getContent();
    $report = json_decode($reportJson);

    // Return 400: Bad Request if content cannot be parsed.
    if (empty($report) || json_last_error() != JSON_ERROR_NONE) {
      return new Response('', 400);
    }

    $this->logger
      ->info("@type <br/>\n<pre>@data</pre>", [
        '@type' => $type,
        '@data' => json_encode($report, JSON_PRETTY_PRINT),
      ]);

    // 202: Accepted.
    return new Response('', 202);
  }

}
