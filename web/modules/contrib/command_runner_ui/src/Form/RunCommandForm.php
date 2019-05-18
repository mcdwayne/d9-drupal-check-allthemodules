<?php

namespace Drupal\command_runner_ui\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Runs commands.
 */
class RunCommandForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'command_runner_ui_run_command';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['command'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Command'),
      '#required' => TRUE,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Run'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $command = $form_state->getValue('command');
    $process = new Process($command);
    $process->run();

    if (!$process->isSuccessful()) {
      throw new ProcessFailedException($process);
    }

    drupal_set_message($process->getOutput());
  }

}
