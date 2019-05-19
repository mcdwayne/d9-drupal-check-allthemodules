<?php

namespace Drupal\vk_comments\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a VK Comments Block
 *
 * @Block(
 *   id = "vk_comments",
 *   admin_label = @Translation("VK comments"),
 * )
 */
class VkCommentsSocialPluginBlock extends BlockBase implements BlockPluginInterface {
  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $vk_comments_limit = isset($config['vk_comments_count']) ? $config['vk_comments_count'] : 5;
    $config = \Drupal::config('vk_comments_social_plugin.settings');
    $vk_app_id = $config->get('vk_comments_appid');
    $options = array('absolute' => TRUE);
    $url = Url::fromRoute('<current>', array(), $options)->toString();
    $output = array(
      '#theme' => 'vk_comments_social_plugin_block',
      '#vk_app_id' => $vk_app_id,
      '#vk_comments_limit' => $vk_comments_limit,
      '#url' => $url,
    );
    $output['#attached']['library'][] = 'vk_comments_social_plugin/vk_openapi';
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();
    $form['vk_comments_count'] = array(
      '#type' => 'text',
      '#title' => $this->t('Count of comments to display'),
      '#default_value' => isset($config['vk_comments_count']) ? $config['vk_comments_count'] : 5,
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('vk_comments_count', $form_state->getValue('vk_comments_count'));
  }

}
