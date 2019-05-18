<?php

namespace Drupal\private_message_nodejs\Service;

use Drupal\private_message\Entity\PrivateMessageInterface;
use Drupal\private_message\Entity\PrivateMessageThreadInterface;

/**
 * Provides services for the PrivateMessageNodejs module.
 */
interface PrivateMessageNodejsServiceInterface {

  /**
   * Attaches the Nodejs library and JS settings to the given element.
   *
   * Note that the JS will added to the #attached element of the given element.
   *
   * @param string $library
   *   The library that should be attached. This will be the library key from
   *   the private_message_nodejs.libraries.yml file.
   * @param array $element
   *   The render array of an element to which the library and settings should
   *   be added.
   */
  public function attachNodeJsLibary($library, array &$element);

  /**
   * Build browser notification message data.
   *
   * @param \Drupal\private_message\Entity\PrivateMessageInterface $privateMessage
   *   The private message to which the notification refers.
   * @param \Drupal\private_message\Entity\PrivateMessageThreadInterface $privateMessageThread
   *   The private message thread to which the notification refers.
   *
   * @return array
   *   An array of data regarding the info to be sent.
   *   Required Keys:
   *   - author (string): The name of the sender.
   *   - icon (string): The URL of the image to use in the popup.
   *   - title (string): The title to show in the popup.
   *   Optional:
   *   - link (string): A URL that clicking the private link will show.
   */
  public function buildBrowserPushNotificationData(
    PrivateMessageInterface $privateMessage,
    PrivateMessageThreadInterface $privateMessageThread
  );

}
