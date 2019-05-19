<?php

namespace Drupal\vk_community\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'VKCommunityBlock' block.
 *
 * @Block(
 *  id = "vkcommunity_block",
 *  admin_label = @Translation("Vkcommunity block"),
 * )
 */
class VKCommunityBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'vk_community_group_id' => '',
      'vk_community_layout' => 3,
      'vk_community_width' => $this->t('400'),
      'vk_community_height' => $this->t('220'),
      'vk_community_bg_color' => $this->t('FFFFFF'),
      'vk_community_text_color' => $this->t('000000'),
      'vk_community_button_color' => $this->t('5E81A8'),
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['vk_community_group_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Group ID'),
      '#description' => $this->t('Name of Group or Page'),
      '#default_value' => $this->configuration['vk_community_group_id'],
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
    ];
    $form['vk_community_layout'] = [
      '#type' => 'radios',
      '#title' => $this->t('Layout'),
      '#description' => $this->t('Community layout'),
      '#options' => [
        '3' => $this->t('Members'),
        '4' => $this->t('News'),
        '1' => $this->t('Name Only'),
      ],
      '#default_value' => $this->configuration['vk_community_layout'],
      '#weight' => '0',
    ];
    $form['vk_community_width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Width'),
      '#description' => $this->t('Width'),
      '#default_value' => $this->configuration['vk_community_width'],
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
    ];
    $form['vk_community_height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Height'),
      '#description' => $this->t('Height'),
      '#default_value' => $this->configuration['vk_community_height'],
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
    ];
    $form['vk_community_bg_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Background color'),
      '#default_value' => $this->configuration['vk_community_bg_color'],
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
    ];
    $form['vk_community_text_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text color'),
      '#default_value' => $this->configuration['vk_community_text_color'],
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
    ];
    $form['vk_community_button_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Buttons color'),
      '#default_value' => $this->configuration['vk_community_button_color'],
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['vk_community_group_id'] = $form_state->getValue('vk_community_group_id');
    $this->configuration['vk_community_layout'] = $form_state->getValue('vk_community_layout');
    $this->configuration['vk_community_width'] = $form_state->getValue('vk_community_width');
    $this->configuration['vk_community_height'] = $form_state->getValue('vk_community_height');
    $this->configuration['vk_community_bg_color'] = $form_state->getValue('vk_community_bg_color');
    $this->configuration['vk_community_text_color'] = $form_state->getValue('vk_community_text_color');
    $this->configuration['vk_community_button_color'] = $form_state->getValue('vk_community_button_color');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'block_vk_community',
      '#attached' => [
        'library' => ['vk_community/drupal.vk_community'],
      ],
      '#group_id' => $this->configuration['vk_community_group_id'],
      '#layout' => $this->configuration['vk_community_layout'],
      '#width' => $this->configuration['vk_community_width'],
      '#height' => $this->configuration['vk_community_height'],
      '#bg_color' => $this->configuration['vk_community_bg_color'],
      '#text_color' => $this->configuration['vk_community_text_color'],
      '#button_color' => $this->configuration['vk_community_button_color'],
    ];
  }

}
