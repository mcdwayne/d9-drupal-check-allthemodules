<?php
/**
 * @filesource: Accessiblizer configuration form.
 */

namespace Drupal\accessiblizer\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure accessiblizer settings for this site.
 */

class AccessiblizerSettingFrom extends ConfigFormBase {

  public function getFormId() {
    return 'accessiblizer_admin_settings';
  }

  protected function getEditableConfigNames() {
    return ['accessiblizer.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('accessiblizer.settings');

    // Activate the Accessiblizer.
    $form['activate_accessiblizer'] = array(
      '#type' =>'checkbox',
      '#title' => t('Activate the Accessiblizer'),
      '#default_value' =>  $config->get('activate_accessiblizer'),
    );

    $form['activate_accessiblizer_admin_panel'] = array(
      '#type' =>'checkbox',
      '#title' => t('Enable Accessiblizer on admin panel'),
      '#default_value' => $config->get('activate_accessiblizer_admin_panel'),
      '#states' => array(
        'invisible' => array(
          ':input[name="activate_accessiblizer"]' => array('checked' => FALSE),
        ),
      ),
    );

    $form['accessiblizer_code'] = array(
      '#type' =>'textarea',
      '#title' => t('Code'),
      '#description' => t('Fill your code as received from Accessiblizer. You must register <a href="http://www.accessiblizer.com/">here</a> to get the code.'),
      '#default_value' => $config->get('accessiblizer_code'),
      '#states' => array(
        'invisible' => array(
          ':input[name="activate_accessiblizer"]' => array('checked' => FALSE),
        ),
      ),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * Form submit callback.
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')->getEditable('accessiblizer.settings');
    $input = $form_state->getValues(array('activate_accessiblizer', 'activate_accessiblizer_admin_panel', 'accessiblizer_code'));

    $this->config('accessiblizer.settings')
      ->set('activate_accessiblizer', $input['activate_accessiblizer'])
      ->set('activate_accessiblizer_admin_panel', $input['activate_accessiblizer_admin_panel'])
      ->set('accessiblizer_code', $input['accessiblizer_code'])
      ->save();

    parent::submitForm($form, $form_state);
  }
}