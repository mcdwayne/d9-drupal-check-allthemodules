<?php

namespace Drupal\micon_local_task\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class MiconLocalTaskConfigForm.
 *
 * @package Drupal\micon_local_task\Form
 */
class MiconLocalTaskConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'micon_local_task.config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'micon_local_task_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('micon_local_task.config');
    $form['icon_only'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Icon Only'),
      '#description' => $this->t('When an local task has an icon, only show the icon and hide the text.'),
      '#default_value' => $config->get('icon_only'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('micon_local_task.config')
      ->set('icon_only', $form_state->getValue('icon_only'))
      ->save();
  }

}
