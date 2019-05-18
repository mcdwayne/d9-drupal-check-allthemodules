<?php

namespace Drupal\mail;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Interface for entities that can be mailed with the entity mailer service.
 */
interface MailMessageInterface extends EntityInterface {

  /**
   * Returns the mail subject.
   *
   * @return string
   *  The mail subject.
   */
  public function getSubject();

  /**
   * Returns the mail body.
   *
   * @return string
   *  The mail body.
   */
  public function getBody();

  /**
   * Returns the ID of the mail backend plugin to use to send the mail.
   *
   * @return string|null
   *  The ID of a mail plugin.
   */
  public function getMailBackendPluginID();

  /**
   * Returns the ID of the mail processor plugin to use to process the mail.
   *
   * @return string|null
   *  The ID of a mail processor plugin.
   */
  public function getMailProcessorPluginID();

}
