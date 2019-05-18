<?php

namespace Drupal\drush_task\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Drush settings for this site.
 */
class ConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'drush_task_config';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['drush_task.config'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $values = $form_state->getValues();

    $config = $this->config('drush_task.config');
    $form['drush_path'] = array(
      '#title' => t('Path to Drush'),
      '#type' => 'textfield',
      '#default_value' => $config->get('drush_path'),
      '#description' => t('EG: `/user/local/bin/drush`, `/home/username/.config/composer/bin/drush`, `/Users/username/.composer/vendor/bin/drush`'),
    );
    $form['drush_php'] = array(
      '#title' => t('Path to PHP (for drush)'),
      '#type' => 'textfield',
      '#default_value' => $config->get('drush_php'),
      '#description' => t('If you need to use a specific PHP to run your chosen drush, setting the DRUSH_PHP var should help. EG: `/user/bin/php`. If not set, the system default will be used.'),
    );
    $form['test_help'] = array(
      '#markup' => t('
      <p>
      When running drush from the web context, many preferences, like the 
      $PATH, the home directory, site-alias scan paths, permissions
      and other environment variables can be extremely different. 
      </p><p>
      Use the [Test] button to bring up diagnostics.
      </p>
    '),
    );
    $form['drush_rc'] = array(
      '#title' => t('drushrc.php config file'),
      '#type' => 'textfield',
      '#default_value' => $config->get('drush_rc'),
      '#description' => t('A number of environment issues can be helped by fixing up settings in a special drushrc.php.'),
    );

    $form['test'] = array(
      '#value' => t('Test'),
      '#type' => 'submit',
      '#submit' => array('::testStatus'),
      // We should be able to 'test' things before they are saved.
      // Applying a #validate to the button allows us to skip the form
      // #validate check.
      '#validate' => array(),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  function ValidateForm(array &$form, FormStateInterface $form_state) {
    $drush_path = $form_state->getValue('drush_path');
    if (!is_readable($drush_path)) {
      $form_state->setErrorByName(
        'drush_path',
        $this->t("Could not access drush_path @drush_path. Does not exist?", array('@drush_path' => $drush_path))
      );
    }

    $task = \Drupal::service('drush_task.drush_task');
    // Set these config values temporarily.
    // It makes sense to validate *before* saving it to config.
    $task->setConfig('drush_path', $form_state->getValue('drush_path'));
    $task->setConfig('drush_php', $form_state->getValue('drush_php'));
    $task->setConfig('drush_rc', $form_state->getValue('drush_rc'));
    $test_result = $task->version();

    if (!$test_result) {
      $form_state->setErrorByName(
        'drush_path',
        $this->t("Testing drush failed. Could not run drush at @drush_path.", array('@drush_path' => $drush_path))
      );
    }
    else {
      drupal_set_message(t("drush version is: @test_result", array('@test_result' => $test_result)));
    }
  }

  /**
   * Test the drush status with the given settings.
   *
   * FAPI submit handler.
   */
  public function testStatus($form, $form_state) {
    $logger = \Drupal::logger('drush_task');
    $logger->notice('instantiating drush task');

    $logger->notice('initializing drush task');
    $task = \Drupal::service('drush_task.drush_task');
    $task->command = 'status';

    // Huh. What's the default stdout format?
    $task->format = 'print-r';
    $task->format = '';
    $result = $task->run();
    $strings = array(
      '%commandRaw' => $task->commandRaw,
      '%resultRaw' => $task->resultRaw,
    );
    $message = '<strong><pre>%commandRaw</pre></strong><pre>%resultRaw</pre>';
    drupal_set_message(t($message, $strings));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('drush_task.config')
      ->set('drush_path', $form_state->getValue('drush_path'))
      ->set('drush_php', $form_state->getValue('drush_php'))
      ->set('drush_rc', $form_state->getValue('drush_rc'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
