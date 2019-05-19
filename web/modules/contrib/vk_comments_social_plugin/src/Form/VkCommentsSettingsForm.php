<?php
/**
 * @file
 * Contains \Drupal\vk_comments_social_plugin\Form\VkCommentsSettingsForm
 */

namespace Drupal\vk_comments_social_plugin\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

class VkCommentsSettingsForm extends ConfigFormBase {
  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'vk_comments_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'vk_comments_social_plugin.settings'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = \Drupal::config('vk_comments_social_plugin.settings');
    $form['vk_comments_appid'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('VK App ID'),
      '#default_value' => $config->get('vk_comments_appid'),
      '#description' => $this->t('Enter the VK App ID.'),
    );
    return parent::buildForm($form,$form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('vk_comments_social_plugin.settings')
      ->set('vk_comments_appid', $form_state->getValue('vk_comments_appid'))
      ->save();
    parent::submitForm($form, $form_state);
  }
}
