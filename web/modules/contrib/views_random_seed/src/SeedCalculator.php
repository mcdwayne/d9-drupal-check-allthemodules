<?php

namespace Drupal\views_random_seed;

use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Calculates seeds.
 */
class SeedCalculator {

  /**
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The key value store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $keyValueStore;

  public function __construct(RequestStack $requestStack, KeyValueFactoryInterface $keyValueFactory) {
    $this->requestStack = $requestStack;
    $this->keyValueStore = $keyValueFactory->get('views_random_seed');
  }

  /**
   * Calculate a seed.
   *
   * @param array $options
   *   The options for the random seed handler.
   * @param string $view_name
   *   The name of the view.
   * @param string $display
   *   The current display.
   * @param string $db_type
   *   The current database type (mysql(i) - pgsql).
   *
   * @return int
   *   Seed value which is a timestamp.
   */
  public function calculateSeed($options, $view_name, $display, $db_type) {
    $time = (int) $this->requestStack->getCurrentRequest()->server->get('REQUEST_TIME');
    $seed_name = 'views_seed_name-' . $view_name . '-' . $display;
    $seed_time = 'views_seed_time-' . $view_name . '-' . $display;
    $seed = $this->getSession()->get($seed_name, FALSE);

    $options += ['user_seed_type' => 'same_per_user'];

    // Create a first seed if necessary.
    if ($seed === FALSE) {
      $seed = $this->generateSeed($options['user_seed_type'], $seed_name, $seed_time, $time, $db_type);
    }

    // Reset seed or not ?
    if ($options['reset_seed_int'] !== 'never') {
      $reset_time = $options['reset_seed_int'];
      if (($this->getSession()->get($seed_time, FALSE) + $reset_time) < $time) {
        $this->keyValueStore->delete($seed_name);
        $seed = $this->generateSeed($options['user_seed_type'], $seed_name, $seed_time, $time, $db_type);
      }
    }

    // Return seed.
    return $seed;
  }

  /**
   * Helper function to generate a seed
   *
   * @param string $user_seed_type
   *   Type of user seed.
   * @param string $seed_name
   *   Name of the seed.
   * @param string $seed_time
   *   Time of the seed.
   * @param int $time
   *   Current timestamp.
   * @param string $db_type
   *   The current database type (mysql(i) - pgsql).
   *
   * @return int
   *   The seed value.
   */
  protected function generateSeed($user_seed_type, $seed_name, $seed_time, $time, $db_type) {
    // Different per user, simply return $time.
    if ($user_seed_type === 'diff_per_user') {
      $seed = $this->createInt($time, $db_type);
    }
    else {
      // Same for al users, get a stored variable.
      $seed = $this->keyValueStore->get($seed_name, FALSE);
      if ($seed === FALSE) {
        $seed = $this->createInt($time, $db_type);
        $this->keyValueStore->set($seed_name, $seed);
      }
    }

    $this->getSession()->set($seed_time, $time);
    $this->getSession()->set($seed_name, $seed);
    return $seed;
  }

  /**
   * Helper function to create a seed based on db_type. MySQL can
   * handle any integer in the RAND() function, Postgres needs
   * an int between 0 and 1.
   *
   * @param int $time Current timestamp.
   * @param string $db_type the current database type (mysql(i) - pgsql)
   *
   * @return int $seed timestamp or int between 0 and 1.
   */
  protected function createInt($time, $db_type) {
    switch ($db_type) {
      case 'mysql':
      case 'mysqli':
        return $time;
        break;
      case 'pgsql':
        $seed = $time / 10000000000;
        return $seed;
        break;
    }
  }

  /**
   * @return \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected function getSession() {
    return $this->requestStack->getCurrentRequest()->getSession();
  }

}

