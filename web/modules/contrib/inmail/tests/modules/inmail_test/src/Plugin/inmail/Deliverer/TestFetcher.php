<?php

namespace Drupal\inmail_test\Plugin\inmail\Deliverer;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\inmail\Plugin\inmail\Deliverer\FetcherBase;
use Drupal\inmail\Plugin\inmail\Deliverer\FetcherInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Delivers a dummy message and counts invocations.
 *
 * @Deliverer(
 *   id = "test_fetcher",
 *   label = @Translation("Test Fetcher")
 * )
 */
class TestFetcher extends FetcherBase {

  use TestDelivererTrait;

  /**
   * The number of remaining messages.
   *
   * Unlike the state variable inmail.test.deliver_remaining, this static
   * property models the actual number at a remote location.
   *
   * @var int
   */
  protected static $remaining = 100;

  /**
   * Constructs a TestFetcher.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function success($key) {
    parent::success($key);
    $this->setSuccess($key);
  }

  /**
   * {@inheritdoc}
   */
  public function fetchUnprocessedMessages() {
    $time = time();

    // Decrement the remaining counter.
    static::$remaining--;

    $this->setTotalCount(200);
    // Save number of unread messages.
    $this->setUnprocessedCount(static::$remaining);
    $this->setLastCheckedTime($time);

    if ($message = \Drupal::state()->get('inmail.test_fetcher.invalid_message')) {
      // Return malformed message that has missing RFC mandatory From field.
      return [$message];
    }
    else {
      // MimeMessage must be fully valid, so it can pass all validations and trigger
      // some functions (i.e. success()).
      return [
        "From: FooBar\nDate: Tue, 23 Aug 2016 17:48:6 +0600\nSubject: Dummy message\n\nMessage Body"
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function update() {
    $time = time();

    $this->setTotalCount(250);

    $this->setUnprocessedCount(static::$remaining);
    $this->setLastCheckedTime($time);
  }

  /**
   * {@inheritdoc}
   */
  public function getHost() {
    // For purpose of testing, value is hardcoded.
    return 'localhost';
  }

}
