<?php

/**
 * @file
 * Contains \Drupal\mob_queue\Form\MobQueueSettings.
 */

namespace Drupal\mob_queue\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class MobQueueSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mob_queue_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('mob_queue.settings');
    $config->set('mob_qinfo', $form_state->getValue('mob_qinfo'));
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['mob_queue.settings'];
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    //$queues = \Drupal::moduleHandler()->invokeAll('cron_queue_info');
    $queues = \Drupal::service('mob_queue.operator')->discoverQueueJobs();
    $config = $this->config('mob_queue.settings');
    $form['mob_qinfo'] = [
      //'#type' => 'fieldset',
      '#title' => t('Drush Queue Handling'),
      '#tree' => TRUE,
    ];
    foreach ($queues as $name) {
      $form['mob_qinfo'][$name] = array(
        '#type' => 'checkbox',
        '#title' => $name,
        '#description' => t('Use Drush Queue Handling(mob_queue.) Turn off queue @name process in cron.', array('@name' => $name)),
        '#default_value' => $config->get('mob_qinfo.' . $name),
      );
    }
    return parent::buildForm($form, $form_state);
  }
}
?>
