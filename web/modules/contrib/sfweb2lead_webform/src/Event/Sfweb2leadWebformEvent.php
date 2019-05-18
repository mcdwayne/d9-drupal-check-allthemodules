<?php

namespace Drupal\sfweb2lead_webform\Event;

use Drupal\sfweb2lead_webform\Plugin\WebformHandler\SalesforceWebToLeadPostWebformHandler;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\EventDispatcher\Event;

class Sfweb2leadWebformEvent extends Event {

  const SUBMIT = 'sfweb2lead_webform.submit';

  protected $data;
  protected $handler;
  protected $submission;

  /**
   * Sfweb2leadWebformEvent constructor.
   *
   * @param array $data
   * @param \Drupal\sfweb2lead_webform\Plugin\WebformHandler\SalesforceWebToLeadPostWebformHandler $handler
   * @param \Drupal\webform\WebformSubmissionInterface $submission
   */
  public function __construct(array $data, SalesforceWebToLeadPostWebformHandler $handler, WebformSubmissionInterface $submission) {
    $this->data = $data;
    $this->handler = $handler;
    $this->submission = $submission;
  }

  /**
   * Data getter.
   *
   * @return array
   *   Data.
   */
  public function getData() {
    return $this->data;
  }

  /**
   * Data setter.
   *
   * @param array $data
   *   Data.
   *
   * @return $this
   */
  public function setData(array $data) {
    $this->data = $data;
    return $this;
  }

  /**
   * @return \Drupal\sfweb2lead_webform\Plugin\WebformHandler\SalesforceWebToLeadPostWebformHandler
   */
  public function getHandler() {
    return $this->handler;
  }

  /**
   * @return \Drupal\webform\WebformSubmissionInterface
   */
  public function getSubmission() {
    return $this->submission;
  }

}
