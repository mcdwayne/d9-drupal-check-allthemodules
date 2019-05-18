<?php

namespace Drupal\pet;

use Drupal\Core\Entity\ContentEntityInterface;

interface PetInterface extends ContentEntityInterface {

  /**
   * Returns the template title.
   *
   * @return string
   *   Title of the template.
   */
  public function getTitle();

  /**
   * Set the title for template.
   *
   * @param string $title
   *   Title of template.
   * @return object
   *   Template.
   */
  public function setTitle($title);

  public function getStatus();

  public function setStatus($status);

  public function getSubject();

  public function setSubject($subject);

  public function getMailbody();

  public function setMailbody($mail_body);

  public function getMailbodyPlain();

  public function setMailbodyPlain($mail_body_plain);

  public function getSendPlain();

  public function setSendPlain($send_plain);

  public function getRecipientCallback();

  public function setRecipientCallback($recipient_callback);

  public function getCCDefault();

  public function setCCDefault($cc_default);

  public function getBCCDefault();

  public function setBCCDefault($bcc_default);

  public function getFromOverride();

  public function setFromOverride($from_override);
}
