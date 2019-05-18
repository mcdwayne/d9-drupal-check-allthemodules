<?php
/**
 * @file
 * Contains \Drupal\pathinfo\Form\ConfigForm.
 */
namespace Drupal\pathinfo;

use Drupal\system\SystemConfigFormBase;

/**
 * Configure Pathinfo output options.
 */
class ConfigForm extends SystemConfigFormBase {

  /**
   * Implements \Drupal\Core\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'pathinfo_config_form';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, array &$form_state) {
    $config = \Drupal::config('pathinfo.settings');
    $form['display.footer'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show Pathinfo details in page footer.'),
      '#default_value' => $config->get('display.footer')
    );

    $t_arguments = array(
      '!drupal_api' => l(
        t('Drupal API reference'),
        'http://api.drupal.org/api/drupal',
        array('external' => TRUE)
      ),
      '!drupalize_me' => l(
        t('drupalize.me'),
        'http://api.drupalize.me',
        array('external' => TRUE)
      ),
    );

    $form['link_functions'] = array(
      '#type' => 'checkbox',
      '#title' => t('Link functions to their documentation in the !drupal_api or !drupalize_me where possible.', $t_arguments),
      '#default_value' => $config->get('link_functions'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, array &$form_state) {
    \Drupal::config('pathinfo.settings')
      ->set('display.footer', $form_state['values']['display.footer'])
      ->set('link_functions', $form_state['values']['link_functions']);
    parent::submitForm($form, $form_state);
  }

}
