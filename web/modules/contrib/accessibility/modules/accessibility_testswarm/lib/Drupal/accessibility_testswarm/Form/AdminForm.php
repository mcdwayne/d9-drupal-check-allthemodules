<?php
/**
 * @file
 * Contains \Drupal\accessibility_testswarm\Form\AdminForm.
 */

namespace Drupal\accessibility_testswarm\Form;

use Drupal\system\SystemConfigFormBase;

/**
 * Defines a form to configure maintenance settings for this site.
 */
class AdminForm extends SystemConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $config = $this->configFactory->get('accessibility.accessibility_testswarm');

    $form['paths'] = array(
      '#type' => 'textarea',
      '#title' => t('Provide a list of paths to check'),
      '#default_value' => $config->get('paths'),
    );

    $form['check_all'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable checking all enabled Drupal paths (can take a long time)'),
      '#default_value' => $config->get('check_all'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->configFactory->get('accessibility.accessibility_testswarm')
      ->set('paths', $form_state['values']['paths'])
      ->set('check_all', $form_state['values']['check_all'])
      ->save();
    cache()->delete('accessibility_testswarm:paths');
    parent::submitForm($form, $form_state);
  }

}