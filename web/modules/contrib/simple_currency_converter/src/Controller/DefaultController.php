<?php /**
 * @file
 * Contains \Drupal\simple_currency_converter\Controller\DefaultController.
 */

namespace Drupal\simple_currency_converter\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Default controller for the simple_currency_converter module.
 */
class DefaultController extends ControllerBase {

  /**
   * Provides the config factory.
   */
  protected $config_factory;

  /**
   * Provides the module config.
   */
  protected $config;

  /**
   * Constructs a new object.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config_factory = $config_factory;

    $this->config = $config_factory->get('simple_currency_converter.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  public function setCurrency($from_currency, $to_currency) {
    $conversion_ratio = 1;

    if ($from_currency != $to_currency) {
      $conversion_ratio = $this->convert($from_currency, $to_currency, $amount = 1);
    }

    if (!$conversion_ratio) {
      throw new NotFoundHttpException();
    }

    $cookie_name = 'scc_ratio_from_' . $from_currency . '_to_' . $to_currency;

    $expire = $this->config->get('cookie_expiration');

    setcookie($cookie_name, $conversion_ratio, time() + $expire, "/");

    $data = [
      'ratio' => $conversion_ratio,
      'name' => $cookie_name,
      'to_currency' => $from_currency,
    ];

    return new JsonResponse($data);
  }

  /**
   * Get the currency rate.
   *
   * @param string $from_currency
   *   The currency to convert from.
   * @param string $to_currency
   *   The currency to convert to.
   * @param int $amount
   *   The amount to convert.
   *
   * @return float|null
   *   The converted amount
   */
  private function convert($from_currency, $to_currency, $amount) {
    $feed = $this->requestFeed('primary', $from_currency, $to_currency, $amount);

    if (!$feed) {
      $feed = $this->requestFeed('secondary', $from_currency, $to_currency, $amount);

      $result = \Drupal::hasService('currency_converter_notifier');

      if ($result) {
        \Drupal::service('currency_converter_notifier')->notify(compact('from_currency', 'to_currency', 'feed'));
      }
    }

    if ($feed) {
      return round($feed, 5);
    }

    return NULL;
  }

  /**
   * Get the currency feed.
   */
  private function requestFeed($type, $from_currency, $to_currency, $amount) {
    $output = NULL;

    $service = NULL;

    switch ($type) {
      case 'primary':
        $service = $this->config->get('feed_primary');

        break;
      case 'secondary':
        $service = $this->config->get('feed_secondary');

        break;
    }

    if ($service) {
      /**
       * @var \Drupal\simple_currency_converter\CurrencyConverter\CurrencyConverterInterface
       */
      $service = \Drupal::service($service);

      $output = $service->convert($from_currency, $to_currency, $amount);
    }

    return $output;
  }

}
