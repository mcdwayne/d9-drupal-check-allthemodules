<?php

namespace Drupal\update_runner\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure update runner settings for this site.
 *
 * @internal
 */
class UpdateRunnerSettingsForm extends ConfigFormBase implements ContainerInjectionInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'update_runner_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['update_runner.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['help'] = [
      '#type' => 'markup',
      '#markup' => t('To setup update runner, create processors to handle update jobs when updates are detected. Jobs will be run on cron or on demand.'),
    ];

    return $form;
  }

}
