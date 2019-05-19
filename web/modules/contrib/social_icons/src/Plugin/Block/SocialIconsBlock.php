<?php

namespace Drupal\social_icons\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides social icon block with configurations.
 * 
 * @Block(
 *   id = "social_icons_block",
 *   admin_label = @Translation("Social Icons"),
 *   category = @Translation("Social Icons"),
 * )
 */
class SocialIconsBlock extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form , FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    
    $config = $this->getConfiguration();
    
    $form['facebook_icon'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Facebook'),
      '#description' => $this->t('Add Facebook URL or leave empty if you want to disable this'),
      '#default_value' => isset($config['facebook_icon']) ? $config['facebook_icon'] : '',
    ];
    $form['twitter_icon'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Twitter'),
      '#description' => $this->t('Add Twitter URL or leave empty if you want to disable this'),
      '#default_value' => isset($config['twitter_icon']) ? $config['twitter_icon'] : '',
    ];
    $form['google_plus_icon'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Plus'),
      '#description' => $this->t('Add Google Plus URL or leave empty if you want to disable this'),
      '#default_value' => isset($config['google_plus_icon']) ? $config['google_plus_icon'] : '',
    ];
    $form['instragram_icon'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Instagram'),
      '#description' => $this->t('Add Instagram URL or leave empty if you want to disable this'),
      '#default_value' => isset($config['instragram_icon']) ? $config['instragram_icon'] : '',
    ];
    $form['telegram_icon'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Telegram'),
      '#description' => $this->t('Add Telegram URL or leave empty if you want to disable this'),
      '#default_value' => isset($config['telegram_icon']) ? $config['telegram_icon'] : '',
    ];
    $form['linkedin_icon'] = [
      '#type' => 'textfield',
      '#title' => $this->t('LinkedIn'),
      '#description' => $this->t('Add LinkedIn URL or leave empty if you want to disable this'),
      '#default_value' => isset($config['linkedin_icon']) ? $config['linkedin_icon'] : '',
    ];
    $form['pinterest_icon'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pinterest'),
      '#description' => $this->t('Add Pinterest URL or leave empty if you want to disable this'),
      '#default_value' => isset($config['pinterest_icon']) ? $config['pinterest_icon'] : '',
    ];
    $form['youtube_icon'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Youtube'),
      '#description' => $this->t('Add Youtube URL or leave empty if you want to disable this'),
      '#default_value' => isset($config['youtube_icon']) ? $config['youtube_icon'] : '',
    ];

    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['facebook_icon'] = $values['facebook_icon'];
    $this->configuration['twitter_icon'] = $values['twitter_icon'];
    $this->configuration['google_plus_icon'] = $values['google_plus_icon'];
    $this->configuration['instragram_icon'] = $values['instragram_icon'];
    $this->configuration['telegram_icon'] = $values['telegram_icon'];
    $this->configuration['linkedin_icon'] = $values['linkedin_icon'];
    $this->configuration['pinterest_icon'] = $values['pinterest_icon'];
    $this->configuration['youtube_icon'] = $values['youtube_icon'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();

    return [
      '#theme' => 'social_icons_block',
      '#title' => $config['label']? $config['label']: '',
      '#facebook_icon' => $config['facebook_icon']?$config['facebook_icon']:'',
      '#twitter_icon' => $config['twitter_icon']?$config['twitter_icon']:'',
      '#google_plus_icon' => $config['google_plus_icon']?$config['google_plus_icon']:'',
      '#instragram_icon' => $config['instragram_icon']?$config['instragram_icon']:'',
      '#telegram_icon' => $config['telegram_icon']?$config['telegram_icon']:'',
      '#linkedin_icon' => $config['linkedin_icon']?$config['linkedin_icon']:'',
      '#pinterest_icon' => $config['pinterest_icon']?$config['pinterest_icon']:'',
      '#youtube_icon' => $config['youtube_icon']?$config['youtube_icon']:'',
      '#attached' => [
        'library' => [
          'social_icons/font-awesome'
        ]
      ],
    ];
  }
}