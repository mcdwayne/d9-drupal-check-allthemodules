<?php

namespace Drupal\sa11y\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\sa11y\Sa11yInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a confirmation form before creating a new report.
 */
class Sa11yCreateForm extends ConfirmFormBase {

  /**
   * The Sa11y service.
   *
   * @var \Drupal\sa11y\Sa11y
   */
  protected $sa11y;

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
  public function getFormId() {
    return 'sa11y_create';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to generate a new Sa11y report?');
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
   * Ensure settings and no pending reports.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if (!$this->sa11y->checkRequirements()) {
      $form_state->setError($form, $this->t('You need to set your API key in the @settings.', [
        '@settings' => Link::createFromRoute($this->t('settings page'), 'sa11y.admin_settings')
          ->toString(),
      ]));
      return;
    }

    // Attempt to create a new report.
    if (!$this->sa11y->createReport()) {
      $form_state->setError($form, $this->t('A new report could not be created. A report might already be pending.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message($this->t('New Report has been queued for next cron run.'));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
