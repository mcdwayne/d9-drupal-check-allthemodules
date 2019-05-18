<?php

namespace Drupal\commerce_currency_switcher\Resolver;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_currency_switcher\CurrencyManager;
use Drupal\commerce_currency_switcher\Event\CurrencyConvertEvent;
use Drupal\commerce_currency_switcher\Event\CurrencyEvents;
use Drupal\commerce_currency_switcher\Event\GeoipCurrencyResolveEvent;
use Drupal\commerce_price\Resolver\PriceResolverInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandler;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Multicurrency price resolver.
 */
class MulticurrencyPriceResolver implements PriceResolverInterface {

  /**
   * Current request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * {@inheritdoc}
   */
  public function __construct(RequestStack $request_stack, ConfigFactoryInterface $configFactory, ModuleHandler $moduleHandler, EventDispatcherInterface $event_dispatcher) {
    $this->request = $request_stack->getCurrentRequest();
    $this->configFactory = $configFactory;
    $this->moduleHandler = $moduleHandler;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(PurchasableEntityInterface $variation, $quantity, Context $context) {

    $session = $this->request->getSession();

    $currency_switch_config = $this->configFactory->getEditable('currency_switch_settings.settings');

    if ($currency_switch_config->get('geoip_enable') && $this->moduleHandler->moduleExists('geoip')) {
      $geo_locator = \Drupal::service('geoip.geolocation');
      $ip_address = \Drupal::request()->getClientIp();
      $country = $geo_locator->geolocate($ip_address);
      if (is_object($country)) {
        $currency_code = $country->getCurrencyCode();

        // Give chance for event subscribers to change this.
        $event = new GeoipCurrencyResolveEvent($currency_code);
        $this->eventDispatcher->dispatch(CurrencyEvents::GEOIP_CURRENCY_RESOLVE, $event);
        $currency_code = $event->getCurrencyCode();

        $active_currencies = \Drupal::state()->get('active_currencies');
        $currency_code = isset($active_currencies[$currency_code]) ? $currency_code : reset($active_currencies);
        $session->set('selected_currency', $currency_code);
      }
    }

    $selected_currency = $session->get('selected_currency');

    /* @var CurrencyManager $currency_manager */
    $currency_manager = \Drupal::service('commerce_multicurrency.currency_manager');

    if (!$currency_manager->isCurrencyConversionEnabled()) {
      $resolved_price_field = 'field_price_' . strtolower($selected_currency);
      if (!empty($selected_currency) && $variation->hasField($resolved_price_field)) {
        return $variation->get($resolved_price_field)->first()->toPrice();
      }
    }

    $final_price = $original_price = $variation->getPrice();
    $current_currency_code = $original_price->getCurrencyCode();
    $target_currency_code = $selected_currency;
    if ($current_currency_code !== $target_currency_code) {
      $conversion_rate = $currency_manager->getExchangeRate($current_currency_code, $target_currency_code);

      if (!empty($conversion_rate)) {
        $final_price = $original_price->convert($target_currency_code, $conversion_rate);

        // Give chance for event subscribers to change this.
        $event = new CurrencyConvertEvent($final_price);
        $this->eventDispatcher->dispatch(CurrencyEvents::CURRENCY_CONVERT, $event);
        $final_price = $event->getPrice();
      }
    }

    return $final_price;
  }

}
