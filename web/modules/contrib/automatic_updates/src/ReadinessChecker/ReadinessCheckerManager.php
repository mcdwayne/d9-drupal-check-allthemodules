<?php

namespace Drupal\automatic_updates\ReadinessChecker;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;

/**
 * Defines a chained readiness checker implementation combining multiple checks.
 */
class ReadinessCheckerManager implements ReadinessCheckerManagerInterface {

  /**
   * The key/value storage.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $keyValue;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * An unsorted array of arrays of active checkers.
   *
   * An associative array. The keys are integers that indicate priority. Values
   * are arrays of ReadinessCheckerInterface objects.
   *
   * @var \Drupal\automatic_updates\ReadinessChecker\ReadinessCheckerInterface[][]
   */
  protected $checkers = [];

  /**
   * ReadinessCheckerManager constructor.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value
   *   The key/value service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(KeyValueFactoryInterface $key_value, ConfigFactoryInterface $config_factory) {
    $this->keyValue = $key_value->get('automatic_updates');
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function addChecker(ReadinessCheckerInterface $checker, $category = 'warning', $priority = 0) {
    if (!in_array($category, $this->getCategories(), TRUE)) {
      throw new \InvalidArgumentException(sprintf('Readiness checker category "%s" is invalid. Use "%s" instead.', $category, implode('" or "', $this->getCategories())));
    }
    $this->checkers[$category][$priority][] = $checker;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function run($category) {
    $messages = [];
    if (!$this->isEnabled()) {
      return $messages;
    }
    if (!isset($this->getSortedCheckers()[$category])) {
      throw new \InvalidArgumentException(sprintf('No readiness checkers exist of category "%s"', $category));
    }

    foreach ($this->getSortedCheckers()[$category] as $checker) {
      $messages = array_merge($messages, $checker->run());
    }
    $this->keyValue->set("readiness_check_results.$category", $messages);
    $this->keyValue->set('readiness_check_timestamp', \Drupal::time()->getRequestTime());
    return $messages;
  }

  /**
   * {@inheritdoc}
   */
  public function getResults($category) {
    $results = [];
    if ($this->isEnabled()) {
      $results = $this->keyValue->get("readiness_check_results.$category", []);
    }
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function timestamp() {
    return $this->keyValue->get('readiness_check_timestamp', 0);
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return $this->configFactory->get('automatic_updates.settings')->get('enable_readiness_checks');
  }

  /**
   * {@inheritdoc}
   */
  public function getCategories() {
    return ['warning', 'error'];
  }

  /**
   * Sorts checkers according to priority.
   *
   * @return \Drupal\automatic_updates\ReadinessChecker\ReadinessCheckerInterface[]
   *   A sorted array of checker objects.
   */
  protected function getSortedCheckers() {
    $sorted = [];
    foreach ($this->checkers as $category => $priorities) {
      foreach ($priorities as $checkers) {
        krsort($checkers);
        $sorted[$category] = isset($sorted[$category]) ? array_merge($sorted[$category], $checkers) : array_merge([], $checkers);
      }
    }
    return $sorted;
  }

}
