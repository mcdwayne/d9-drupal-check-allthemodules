<?php
/**
 * @file
 * Contains eboks_message entity interface definition.
 */

namespace Drupal\eboks\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a EboksMessage entity.
 *
 * We have this interface so we can join the other interfaces it extends.
 *
 * @ingroup eboks_message
 */
interface EboksMessageInterface extends \IteratorAggregate, ContentEntityInterface {

  /**
   * Gets the eboks message shimpment Id for sender object.
   *
   * @return string
   *   generated string with shimpent ID.
   */
  public function generateShipmentId();

}
