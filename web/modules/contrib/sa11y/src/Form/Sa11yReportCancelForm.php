<?php

namespace Drupal\sa11y\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\sa11y\Sa11yInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Sa11yReportCancelForm.
 *
 * @package Drupal\sa11y\Form
 */
class Sa11yReportCancelForm extends ConfirmFormBase {

  /**
   * The Sa11y service.
   *
   * @var \Drupal\sa11y\Sa11y
   */
  protected $sa11y;

  /**
   * The report to cancel.
   *
   * @var int
   */
  protected $reportId;

  /**
   * Constructs a new Sa11yCreateForm.
   *
   * @param \Drupal\sa11y\Sa11yInterface $sa11y
   *   The Sa11y service.
   */
  public function __construct(Sa11yInterface $sa11y) {
    $this->sa11y = $sa11y;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('sa11y.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $report_id = NULL) {
    $this->reportId = $report_id;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sa11y_create';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to cancel this report?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('sa11y.summary');
  }

  /**
   * {@inheritdoc}
   *
   * Cancels a report.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $report = $this->sa11y->getReport($this->reportId);

    if ($report->status != Sa11yInterface::CREATED && $report->status != Sa11yInterface::RUNNING) {
      $form_state->setError($form, $this->t('This report cannot be cancelled.'));
    }
    else {
      $this->sa11y->setStatus($report->id, Sa11yInterface::CANCELLED);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message($this->t('The report has been cancelled.'));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }
}
