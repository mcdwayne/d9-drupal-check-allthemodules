<?php

namespace Drupal\dea_blocker\Classes;

use \Drupal\Core\Config\Config;
use \Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Manages a blacklist of email addresses and domains.
 * Provides methods to test email against that list.
 *
 * @author Claudio Nicora <coolsoft.ita@gmail.com>
 */
class EmailBlacklist {

  /**
   * Configuration factory object.
   * @var ConfigFactoryInterface
   */
  protected $configFactory = NULL;


  /**
   * Blacklist content items.
   * @var string[]
   */
  protected $items;


  /**
   * Service constructor.
   *
   * @param ConfigFactoryInterface $configFactory
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->configFactory = $configFactory;  // Needed by save().
    $this->items = $configFactory->get('dea_blocker.settings')->get('blacklist') ?: [];
  }


  /**
   * Returns blacklist defined items.
   *
   * @return string[]
   */
  public function getItems() {
    return $this->items;
  }


  /**
   * Clears the blacklist.
   */
  public function clear() {
    $this->items = [];
  }


  /**
   * Save the blasklist to settings.
   */
  public function save() {
    $this->configFactory
      ->getEditable('dea_blocker.settings')
      ->set('blacklist', $this->items)
      ->save();
  }


  /**
   * Add the given domains to blacklist, removing duplicates.
   *
   * NOTE. the class must be saved by the caller.
   *
   * @param string[] $items
   *   The email domains to merge in.
   * @return EmailBlacklist
   */
  public function addItems(array $items) {
    // Force to lowercase and trim.
    $items = array_map(function($i){return strtolower(trim($i));}, $items);
    $this->items = array_filter(array_unique(array_merge($this->items, $items)));
    sort($this->items);
    return $this;
  }


  /**
   * Returns TRUE if the given blacklist item must be chacked as a regular expression.
   *
   * @param string $item
   *   Item to test.
   */
  static public function isRegex($item) {
    return $item && $item[0] === '/' && substr($item, -1) === '/';
  }


  /**
   * Extract mail domain from the given email address.
   * The result is lower-cased.
   *
   * @param string $email
   *   The email address.
   *
   * @return string|FALSE
   *   Mail domain or FALSE in case of malformed email address.
   */
  static public function getDomain(string $email) {
    $domain = [];
    if (preg_match('/@(.*)$/', $email, $domain) !== 0) {
      return strtolower($domain[1]);
    }
    else {
      return FALSE;
    }
  }


  /**
   * Test the given items for validity.
   *
   * @param string[] $items
   *   Items to test.
   *
   * @return array
   *   Array with bad items, indexed by value.
   */
  static public function validateItems(array $items) {

    // Set a local error handler.
    set_error_handler(function ($errno, $errstr, $errfile, $errline) {
      throw new \ErrorException($errstr);
    });

    // Test items.
    $errors = [];
    foreach ($items as $item) {
      try {
        if (self::isRegex($item)) {
          // Validate RegEx item.
          preg_match($item, '');
        }
        else {
          // Validate standard item.
          if (preg_match('/[^A-Za-z0-9\.\-]+/', $item)) {
            $errors[$item] = t('Item contains not allowed chars');
          }
        }
      } catch (\ErrorException $ex) {
        $errors[$item] = $ex->getMessage();
      }
    }

    // Restore previous error handler.
    restore_error_handler();
    return $errors;
  }


  /**
   * Returns TRUE if the email address is blacklisted, FALSE otherwise.
   *
   * @param string $email
   *   The email address to test.
   *
   * @return string|FALSE
   *   If the email address is blacklisted returns the triggering item,
   *   FALSE otherwise.
   */
  public function isBlacklisted(string $email) {

    // Extract mail domain and test it against blacklist.
    $domain = self::getDomain(strtolower(trim($email)));
    if ($domain) {
      // Test if domain is blacklisted.
      foreach ($this->items as $item) {
          // Test domain against regular expression pattern.
        if (self::isRegex($item) && preg_match($item, $domain)) {
          return $item;
        }
        // Test if value ends with the value.
        else if ($domain == $item) {
          return $item;
        }
      }
    }

    // Not blacklisted.
    return FALSE;

  }

}
