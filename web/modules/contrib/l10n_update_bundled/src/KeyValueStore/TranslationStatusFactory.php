<?php

namespace Drupal\l10n_update_bundled\KeyValueStore;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;

/**
 * Defines the key/value store factory for the translation status collection.
 */
class TranslationStatusFactory implements KeyValueFactoryInterface {

  /**
   * The actual storage factory.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface
   */
  protected $factory;

  /**
   * The request time.
   *
   * @var int
   */
  protected $request_time;

  /**
   * Constructs this factory object.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $factory
   *   The factory that will return the actual storage.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(KeyValueFactoryInterface $factory, TimeInterface $time) {
    $this->factory = $factory;
    $this->request_time = $time->getRequestTime();
  }

  /**
   * {@inheritdoc}
   */
  public function get($collection) {
    $storage = $this->factory->get($collection);
    return new TranslationStatusStorage($collection, $storage, $this->request_time);
  }

}
