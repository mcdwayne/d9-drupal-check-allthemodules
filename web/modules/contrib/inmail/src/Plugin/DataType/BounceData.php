<?php

namespace Drupal\inmail\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\Map;
use Drupal\inmail\DSNStatus;

/**
 * @DataType(
 *   id = "inmail_bounce",
 *   definition_class = "Drupal\inmail\BounceDataDefinition"
 * )
 */
class BounceData extends Map {

  /**
   * Report the intended recipient for a bounce message.
   *
   * @param string $recipient
   *   The address of the recipient.
   */
  public function setRecipient($recipient) {
    if (!$this->getRecipient()) {
      $this->set('recipient',$recipient);
    }
  }

  /**
   * Report the status code of a bounce message.
   *
   * @param \Drupal\inmail\DSNStatus $code
   *   A status code.
   */
  public function setStatusCode(DSNStatus $code) {
    if (!$this->getStatusCode()) {
      $this->set('status_code', $code->getCode());
      return;
    }

    // If subject and detail are 0 (like X.0.0), allow overriding those.
    $current_code = $this->getStatusCode();
    if ($current_code->getSubject() == 0 && $current_code->getDetail() == 0) {
      $new_code = new DSNStatus($current_code->getClass(), $code->getSubject(), $code->getDetail());
      $this->set('status_code',$new_code->getCode());
    }
  }

  /**
   * Report the reason for a bounce message.
   *
   * @param string $reason
   *   Human-readable information in English explaining why the bounce happened.
   */
  public function setReason($reason) {
    if (!$this->getReason()) {
      $this->set('reason',$reason);
    }
  }

  /**
   * Returns the reported recipient for a bounce message.
   *
   * @return string|null
   *   The address of the intended recipient, or NULL if it has not been
   *   reported.
   */
  public function getRecipient() {
    return $this->get('recipient')->getValue();
  }

  /**
   * Returns the reported status code of a bounce message.
   *
   * @return \Drupal\inmail\DSNStatus|null
   *   The status code, or NULL if it has not been reported.
   */
  public function getStatusCode() {
    if ($code = $this->get('status_code')->getValue()) {
      return DSNStatus::parse($this->get('status_code')->getValue());
    }

    return NULL;
  }

  /**
   * Returns the reason for a bounce message.
   *
   * @return string|null
   *   The reason message, in English, or NULL if it has not been reported.
   */
  public function getReason() {
    return $this->get('reason')->getValue();
  }

  /**
   * Tells whether any analyzer has classified the message as a bounce.
   *
   * @return bool
   *   TRUE if a bounce analyzer has reported a recipient address and a status
   *   code like 4.X.X or 5.X.X, otherwise FALSE.
   */
  public function isBounce() {
    $recipient = $this->getRecipient();
    if (empty($recipient)) {
      return FALSE;
    }

    $status_code = $this->getStatusCode();
    // If there is a status code, it almost certainly indicates a failure
    // (there's no reason to generate a DSN when delivery is successful), but
    // let's check isSuccess() for the sake of correctness.
    if (empty($status_code) || $status_code->isSuccess()) {
      return FALSE;
    }

    return TRUE;
  }

}
