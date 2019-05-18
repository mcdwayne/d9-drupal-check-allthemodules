<?php

namespace Drupal\demandbase\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class DemandbaseSettingsForm.
 */
class DemandbaseSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'demandbase.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'demandbase_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('demandbase.settings');

    $form['tag_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Demandbase Tag Settings')
    ];

    $form['tag_settings']['tag_enabled'] = [
      '#title' => $this->t('Enable tracking'),
      '#type' => 'checkbox',
      '#default_value' => !empty($config->get('tag_enabled')) ? $config->get('tag_enabled') : 1,
    ];

    $form['tag_settings']['tag_id'] = [
      '#title' => $this->t('Tracking ID'),
      '#type' => 'textfield',
      '#size' => 10,
      '#default_value' => $config->get('tag_id'),
      '#description' => 'This id can be found inside of your Demandbase Tag embed code: \n <script>\n
(function(d,b,a,s,e){ var t = b.createElement(a),\n
  fs = b.getElementsByTagName(a)[0]; t.async=1; t.id=e; t.src=s;\n
  fs.parentNode.insertBefore(t, fs); })\n
(window,document,\'script\',\'https://tag.demandbase.com/XXXXXXXX.min.js\',\'demandbase_js_lib\');  \n
</script>'
    ];
    $form['tag_settings']['tag_pages_op'] = [
      '#type' => 'radios',
      '#title' => $this->t('Add snippet on specific paths'),
      '#default_value' => $config->get('tag_pages_op') ?  $config->get('tag_pages_op') : 'exclude',
      '#options' => array('exclude' => $this->t('All paths except the listed paths'), 'include' => $this->t('Only the listed paths')),
    ];
    $form['tag_settings']['tag_pages'] =  [
      '#type' => 'textarea',
      '#title' => $this->t('Pages'),
      '#default_value' => $config->get('tag_pages'),
      '#description' => $this->t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. An example path is %user-wildcard for every user page. %front is the front page.", [
        '%user-wildcard' => '/user/*',
        '%front' => '<front>',
      ]),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('demandbase.settings')
      ->set('tag_enabled', $form_state->getValue('tag_enabled'))
      ->set('tag_id', $form_state->getValue('tag_id'))
      ->set('tag_pages_op', $form_state->getValue('tag_pages_op'))
      ->set('tag_pages', $form_state->getValue('tag_pages'))
      ->save();
  }

}
