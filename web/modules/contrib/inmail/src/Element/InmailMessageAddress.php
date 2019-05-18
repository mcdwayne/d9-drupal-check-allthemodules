<?php

namespace Drupal\inmail\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a render element to display and inmail message address.
 *
 * Properties:
 * - #address: The address object.
 *   An instance of \Drupal\inmail\MIME\MimeMessageInterface.
 *
 * Usage example:
 * @code
 * $build['inmail_message_address_example'] = [
 *   '#title' => $this->t('Inmail Message Address Example'),
 *   '#type' => 'inmail_message_address',
 *   '#address' => $address,
 * ];
 * @endcode
 *
 * @RenderElement("inmail_message_address")
 */
class InmailMessageAddress extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return ['#theme' => 'inmail_message_address'];
  }

}
