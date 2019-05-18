<?php

/**
 * @file
 * PHP-GA: method and functions.
 */

use UnitedPrototype\GoogleAnalytics\Item;
use UnitedPrototype\GoogleAnalytics\Transaction;
use UnitedPrototype\GoogleAnalytics\Event;
use UnitedPrototype\GoogleAnalytics\Session;
use UnitedPrototype\GoogleAnalytics\Visitor;
use UnitedPrototype\GoogleAnalytics\Tracker;

/**
 * GA Push Method callback: PHP-GA (php).
 */
function ga_push_method_php_ga($push, $type, $options) {

  $php_ga_path = libraries_get_path('php-ga');
  include_once $php_ga_path . '/src/autoload.php';

  /*
   * use UnitedPrototype\GoogleAnalytics;
   * causes 500 Internal Server error, so need to use full namespace references.
   * For example:
   * UnitedPrototype\GoogleAnalytics\Tracker instead of GoogleAnalytics\Tracker
   */

  $ua = !empty($options['tid']) ? $options['tid'] : \Drupal::config('ga_push.settings')->get('default_method');
  $host = str_replace(['http://', 'https://'], ['', ''], $GLOBALS['base_url']);
  $tracker = new Tracker($ua, $host);

  // Visitor:
  $visitor = new Visitor();
  $visitor->setIpAddress(\Drupal::request()->getClientIp());
  $visitor->setUserAgent($_SERVER['HTTP_USER_AGENT']);
  if (!empty($options['utma'])) {
    $visitor->fromUtma($options['utma']);
  }
  elseif (!empty($_COOKIE['__utma'])) {
    $visitor->fromUtma($_COOKIE['__utma']);
  }

  // Session:
  $session = new Session();
  if (!empty($options['utmb'])) {
    $session->fromUtmb($options['utmb']);
  }
  elseif (!empty($_COOKIE['__utmb'])) {
    $session->fromUtmb($_COOKIE['__utmb']);
  }

  switch ($type) {

    case GA_PUSH_TYPE_EVENT:
      $event = new Event();

      // Required:
      $event->setCategory($push['eventCategory']);
      $event->setAction($push['eventAction']);

      // Optional:
      if (isset($push['eventLabel'])) {
        $event->setLabel($push['eventLabel']);
      }
      if (isset($push['eventValue'])) {
        $event->setValue($push['eventValue']);
      }
      if (isset($push['non-interaction'])) {
        $event->setNoninteraction($push['non-interaction']);
      }

      $tracker->trackEvent($event, $session, $visitor);
      break;

    case GA_PUSH_TYPE_ECOMMERCE:

      if (isset($push['trans']) && isset($push['items']) && count($push['items'])) {

        $transaction = new Transaction();
        $transaction->setOrderId($push['trans']['id']);
        $transaction->setAffiliation($push['trans']['affiliation']);
        $transaction->setTotal($push['trans']['revenue']);
        $transaction->setTax($push['trans']['tax']);
        $transaction->setShipping($push['trans']['shipping']);
        $transaction->setCity($push['trans']['city']);
        $transaction->setRegion($push['trans']['region']);
        $transaction->setCountry($push['trans']['country']);

        foreach ($push['items'] as $value) {
          $item = new Item();
          $item->setOrderId($value['id']);
          $item->setSku($value['sku']);
          $item->setName($value['name']);
          // $item->setCategory($value['category]);
          // Until it changes in the current library:
          // the category method -> set Variation.
          // http://code.google.com/p/php-ga/issues/detail?id=13
          $item->setVariation($value['category']);
          $item->setPrice($value['price']);
          $item->setQuantity($value['quantity']);

          $transaction->addItem($item);
        }

        $tracker->trackTransaction($transaction, $session, $visitor);

      }

      break;

  }

}
