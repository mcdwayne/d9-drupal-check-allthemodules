<?php
/**
 * @file
 * Contains \Drupal\wechat_share_advance\Form\WechatShareForm..
 */
namespace Drupal\wechat_share_advance\Form;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class WechatShareForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'wechat_share_advance.adminsettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'wechat_share_advance_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('wechat_share_advance.adminsettings');

    $form['debug_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Debug Mode'),
      '#options' => array(
        $this->t('Disabled'),
        $this->t('Enabled'),
      ),
      '#default_value' => $config->get('debug_mode'),
      '#description' => $this->t('Turn on debugging mode, the return value of all APIs that are called will come out on the client alert.'),
    ];
    return parent::buildForm($form, $form_state); 
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('wechat_share_advance.adminsettings')
      ->set('debug_mode', $form_state->getValue('debug_mode'))
      ->save();
  }
}
