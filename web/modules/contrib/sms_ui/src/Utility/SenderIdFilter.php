<?php

namespace Drupal\sms_ui\Utility;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountInterface;

class SenderIdFilter {

  /**
   * Substitutes commonly used by spammers to confuse word lookup algorithms.
   *
   * @var array
   */
  protected static $substitutes = array(
    '@' => 'a',
    '1' => 'l',
    '0' => 'o',
  );

  /**
   * The configuration factory.
   * 
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Static cache to improve speed.
   *
   * @var array
   */
  protected $cache;
  
  /**
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  } 

  /**
   * Checks a sender id and determines if it is allowed for the specified user.
   *
   * @param string $sender_id
   *   The sender id being checked.
   * @param $user \Drupal\Core\Session\AccountInterface
   *   The user account being checked.
   * @param string $word
   *   The actual sender id that was matched.
   *
   * @return bool
   *   TRUE if sender id is allowed, FALSE if not allowed.
   */
  public function isAllowed($sender_id, AccountInterface $user, &$word) {
    $sender_filter = $this->configFactory->get('sms_ui.settings')->get('sender_id_filter');
    // Don't check user #1 if specified.
    if ($user->id() == 1 && $sender_filter['include_superuser'] == FALSE) {
      $word = '';
      return TRUE;
    }

    // Normalize $sender_id. Strip non-alphanumeric non-whitespace characters.
    $sender_id = preg_replace('/[^-a-zA-Z0-9@_ ]/', '', $sender_id);
    // Remove common numeric substitutes for alpha characters.
    $sender_id = str_replace(array_keys(static::$substitutes), array_values(static::$substitutes), $sender_id);

    // Generate and cache the sender id checking rules
    if (!isset($this->cache)) {
      $allowed = TRUE;
      // Get the list of reserved sender ids.
      if (isset($sender_filter['excluded'])) {
        foreach (explode(',', $sender_filter['excluded']) as $ex) {
          if ($test = trim(str_replace('%', '.*', $ex))) {
            $this->cache['excluded'][] = $test;
            if (preg_match('/\b' . $test . '\b/i', $sender_id)) {
              $allowed = FALSE;
              $word = $test;
            }
          }
        }
      }

      // Get the list of users with exceptions.
      if (isset($sender_filter['included'])) {
        foreach (explode(';', trim($sender_filter['included'], "; ,|\n\r")) as $value) {
          if (strpos($value, ':') === FALSE) {
            continue;
          }
          list($username, $ids) = explode(':', $value, 2);
          $usernames = explode(',', $username);
          foreach ($usernames as $name) {
            $name = strtolower(trim($name));
            foreach (explode(',', $ids) as $in) {
              if (($test = trim(str_replace('%', '.*', $in))) && $name) {
                $this->cache['included'][$name][] = $test;
                if (($name === strtolower($user->getAccountName()) || $name === '*') && preg_match('/\b' . $test . '\b/i', $sender_id)) {
                  $allowed = TRUE;
                  $word = $test;
                }
              }
            }
          }
        }
      }
      return $allowed;
    }
    else {
      // Check specific users for inclusion.
      if (isset($this->cache['included'][$user->getAccountName()])) {
        foreach ($this->cache['included'][$user->getAccountName()] as $value) {
          if (preg_match('/\b' . $value . '\b/i', $sender_id)) {
            $word = $value;
            return TRUE;
          }
        }
      }

      // Check generic user inclusions.
      if (isset($this->cache['included']['*'])) {
        foreach ($this->cache['included']['*'] as $value) {
          if (preg_match('/\b' . $value . '\b/i', $sender_id)) {
            $word = $value;
            return TRUE;
          }
        }
      }

      // Check specified exclusions.
      if (isset($this->cache['excluded'])) {
        foreach ($this->cache['excluded'] as $value) {
          if (preg_match('/\b' . $value . '\b/i', $sender_id)) {
            $word = $value;
            return FALSE;
          }
        }
      }
      return TRUE;
    }
  }

}
