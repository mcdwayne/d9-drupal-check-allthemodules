<?php

namespace Drupal\private_message_flood\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Provides services for the Private Message Flood Protection module.
 */
class PrivateMessageFloodService implements PrivateMessageFloodServiceInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a PrivateMessageFloodService object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(
    AccountProxyInterface $currentUser,
    ConfigFactoryInterface $configFactory,
    TimeInterface $time
  ) {
    $this->currentUser = $currentUser;
    $this->configFactory = $configFactory;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public function checkUserFlood() {
    $flood_info = $this->getFloodProtectionInfo();
    // Get the type of limiting, either 'post' or 'thread'. Only do something if
    // there is a value for limit type.
    if ($limit_type = $flood_info['type']) {
      // Get the number of $limit_type allowed. Only do something if a value has
      // been entered.
      if ($limit = $flood_info['limit']) {
        // Get the amount of time in which they are limited to make said number
        // of threads/posts.
        $duration = new \DateInterval($flood_info['duration']);
        // Get the current time.
        $now = new \DateTime();
        $now->setTimestamp($this->time->getRequestTime());
        // Get the UNIX timestamp for time that is $duration ago from now. For
        // example, if $duration is one year, then $since will be the exact date
        // and time one year ago from now.
        $since = $now->sub($duration)->format('U');

        if ($limit_type == 'thread') {
          // Query the number of posts that the user has made since the given
          // timestamp.
          $query = db_select('private_message_threads', 'pm');
        }
        elseif ($limit_type == 'post') {
          // Query the number of posts that the user has made since the given
          // timestamp.
          $query = db_select('private_messages', 'pm');
        }
        $post_count = $query->fields('pm', ['id'])
          ->condition('owner', $this->currentUser->id())
          ->condition('created', $since, '>=')
          ->countQuery()
          ->execute()
          ->fetchField();

        // Return TRUE if they've posted their limit already, FALSE if they have
        // not.
        return $post_count >= $limit;
      }
    }

    // If there is no limit for the user, then they have no flood protection
    // applied to them, and therefore are unable to flood.
    return FALSE;
  }

  /**
   * Gets flood protection info for the current user, based on their roles.
   *
   * Each role in the system can be assigned flood protection settings. Roles
   * are also assigned a priority. This function fetches the flood protection
   * data for the highest prioirty role that the current user has.
   *
   * @return array
   *   An array of flood protection data for the current user based on their
   *   roles.
   */
  private function getFloodProtectionInfo() {
    $user_roles = $this->currentUser->getRoles();
    $role = array_intersect_key($this->getRoleInfo(), array_flip($user_roles));
    $role_id = key($role);

    $config = $this->configFactory->get('private_message_flood.role.' . $role_id);
    if ($config) {
      return $config->get();
    }

    return [
      'limit' => 0,
    ];
  }

  /**
   * Fetches an array of roles, ordered by priority.
   *
   * Each role in the system may have flood protection settings that determine
   * how many posts a user can make in a given duration. Roles have priority,
   * and the highest priority role is the one that a user is checked against
   * when determining whether or not they have crossed the threshold for their
   * flood settings.
   *
   * @return array
   *   An array of roles that have flood protection assigned to them, keyed by
   *   the role ID, with the value being the weight. Roles in the array are
   *   ordered highest to lowest priority.
   */
  private function getRoleInfo() {
    $roles = array_map(['\Drupal\Component\Utility\Html', 'escape'], user_role_names(TRUE));
    $items = [];
    foreach (array_keys($roles) as $role_id) {
      $role_info = $this->configFactory->get('private_message_flood.role.' . $role_id)->get();
      $items[$role_id] = $role_info['weight'];
    }
    uasort($items, [$this, 'sortByWeight']);

    return $items;
  }

  /**
   * Sorting callback to sort arrays by their 'weight' attribute.
   */
  private function sortByWeight($a, $b) {
    if ($a === $b) {
      return 0;
    }

    return $a > $b ? 1 : -1;
  }

}
