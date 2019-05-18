<?php

namespace Drupal\inmail_mailmute\Plugin\mailmute\SendState;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\inmail\DSNStatus;
use Drupal\mailmute\Plugin\mailmute\SendState\SendStateBase;

/**
 * Declares methods for send states that are triggered from bounce messages.
 *
 * @ingroup mailmute
 */
abstract class BounceSendstateBase extends SendStateBase {

  /**
   * Set a descriptive reason for the bounce triggering this state.
   *
   * @param string $reason
   *   The bounce reason.
   *
   * @return $this
   */
  public function setReason($reason) {
    $this->configuration['reason'] = (string) $reason;
    return $this;
  }

  /**
   * Returns a descriptive reason for the bounce that triggered this state.
   *
   * @return string|null
   *   The bounce reason, or NULL if none is set.
   */
  public function getReason() {
    return isset($this->configuration['reason']) ? $this->configuration['reason'] : NULL;
  }

  /**
   * Set a status code for the bounce triggering this state.
   *
   * @param \Drupal\inmail\DSNStatus $code
   *   The bounce status object.
   *
   * @return $this
   */
  public function setStatus(DSNStatus $code) {
    $this->configuration['code'] = $code;
    return $this;
  }

  /**
   * Returns the status code for the bounce that triggered this state.
   *
   * @return \Drupal\inmail\DSNStatus
   *   The bounce status code, or NULL if none is set.
   */
  public function getStatus() {
    return isset($this->configuration['code']) ? $this->configuration['code'] : NULL;
  }

  /**
   * Returns the status code and a matching label.
   *
   * @return string
   *   The status code, and a label if there is one defined for the code.
   */
  public function getCodeString() {
    if ($status = $this->getStatus()) {
      $args = array('@code' => $status->getCode(), '@label' => $status->getLabel());
      return $this->t($status->getLabel() ? '@code @label' : '@code', $args);
    }
    return NULL;
  }

  /**
   * Set the date when the triggering bounce was received.
   *
   * @param \Drupal\Component\DateTime\DateTimePlus $date
   *   The date when the bounce message was received.
   *
   * @return $this
   */
  public function setDate(DateTimePlus $date) {
    $this->configuration['date'] = $date;
    return $this;
  }

  /**
   * Returns the date when the triggering bounce was received.
   *
   * @return \Drupal\Component\DateTime\DateTimePlus|null
   *   The date when the bounce message was received.
   */
  public function getDate() {
    return isset($this->configuration['date']) ? $this->configuration['date'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function display() {
    $display['label'] = parent::display();

    $display['bounce'] = array(
      '#type' => 'details',
      '#title' => $this->t('Triggering bounce'),
      '#access' => $this->getStatus() || $this->getReason(),
    );

    $display['bounce']['code'] = array(
      '#type' => 'item',
      '#title' => $this->t('Status code'),
      '#markup' => $this->getCodeString(),
      '#access' => (bool) $this->getStatus(),
    );

    $display['bounce']['date'] = array(
      '#type' => 'item',
      '#title' => $this->t('Received'),
      '#markup' => $this->getDate(),
    );

    $display['bounce']['reason'] = array(
      '#type' => 'item',
      '#title' => $this->t('Reason message'),
      '#markup' => '<pre>' . $this->getReason() . '</pre>',
      '#access' => (bool) $this->getReason(),
    );

    return $display;
  }

}
