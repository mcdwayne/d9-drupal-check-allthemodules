<?php

namespace Drupal\kashing\StackMiddleware;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Performs a custom task.
 */
class KashingStackMiddleware implements HttpKernelInterface {

  /**
   * The wrapped HTTP kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;
  private $config;

  /**
   * Creates a HTTP middleware handler.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $kernel
   *   The HTTP kernel.
   * @param $config
   *   Configuration file
   */
  public function __construct(HttpKernelInterface $kernel, $config) {
    $this->httpKernel = $kernel;
    $this->config = $config;
  }

  /**
   * Create.
   */
  public static function create(HttpKernelInterface $kernel, ContainerInterface $container) {

    return new static(
      $kernel,
      $container->get('config.factory')->get('kashing.settings')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {

    $this->kashingRedirectionAction();
    return $this->httpKernel->handle($request, $type, $catch);
  }

  /**
   * Redirect Action - looking for special GET values.
   */
  private function kashingRedirectionAction() {

    // Check if the Response and Reasoncode GET parameters exist in the URL.
    // $config = \Drupal::config('kashing.settings');.
    $base_url = $this->config->get('base');

    $succes_page = $base_url . '/payment-success/';
    $failure_page = $base_url . '/payment-failure//';

    if (isset($_GET) && array_key_exists('Response', $_GET) && array_key_exists('Reason', $_GET)) {

      $return_page = FALSE;

      // Determine the success or failure based on the response and reason code
      // Success.
      if ($_GET['Response'] == 1 && $_GET['Reason'] == 1 && $succes_page != '') {
        $return_page = $succes_page;
      }
      elseif ($_GET['Response'] == 4) {
        $return_page = $failure_page;
      }
      elseif ($failure_page != '') {
        $return_page = $failure_page;
      }

      // If redirection page exists, make a redirection.
      if ($return_page) {

        $return_url = $return_page;

        // Forward parameters.
        $url_parameters = 'kTransactionID=' . $_GET['TransactionID'] . '&kResponse=' . $_GET['Response'] . '&kReason=' . $_GET['Reason'];

        // Already has parameters.
        if (strpos($return_url, '&') !== FALSE) {
          $return_url .= '&' . $url_parameters;
        }
        else {
          $return_url .= '?' . $url_parameters;
        }

        // Make a redirection.
        $response = new RedirectResponse($return_url, 302);
        $response->send();

        // No need to execute the rest of the code.
        exit;
      }

    }

  }

}
