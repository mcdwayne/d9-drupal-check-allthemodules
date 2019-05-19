<?php

/**
 * @file
 * Definition of RealmMerge.
 */

namespace WoW\Realm\Entity;

use WoW\Core\CallbackInterface;
use WoW\Core\Response;
use WoW\Core\ServiceInterface;

/**
 * Callback; Merges a realm with service response.
 */
class RealmMerge implements CallbackInterface {

  protected $storage;
  protected $realm;

  public function __construct(RealmStorageController $storage, Realm $realm) {
    $this->storage = $storage;
    $this->realm = $realm;
  }

  /**
   * (non-PHPdoc)
   * @see \WoW\Core\CallbackInterface::process()
   */
  public function process(ServiceInterface $service, Response $response) {
    $realm = $this->realm;
    $realms = $response->getData('realms');
    // Always returned as an array even if there is only one value.
    $values = $realms[0];
    $values['lastFetched'] = $response->getDate()->getTimestamp();
    // Merges the values and permanently saves the entity.
    $realm->merge($values);
    $this->storage->save($realm);
  }

}
