<?php

namespace Drupal\submission_ip_anonymizer\ListBuilder;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\webform\WebformSubmissionListBuilder;

/**
 * Class SubmissionIpAnonymizerListBuilder.
 *
 * @package Drupal\submission_ip_anonymizer\ListBuilder
 */
class SubmissionIpAnonymizerListBuilder extends WebformSubmissionListBuilder {

  /**
   * Integer Value from configuration.
   *
   * @var int
   */
  private $switchOnOff;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $config = $this->configFactory->get('submission_ip_anonymizer.submissionipanonymizer');

    if ($config->get('show_ip') == 0) {
      unset($this->columns['remote_addr']);
    }
    else {
      $this->columns['remote_addr']['title'] = 'IP Address Hash';
    }

    return parent::buildHeader();
  }

}
