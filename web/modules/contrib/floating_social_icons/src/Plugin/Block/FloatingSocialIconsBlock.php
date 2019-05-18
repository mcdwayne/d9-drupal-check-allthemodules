<?php

namespace Drupal\floating_social_icons\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Floating Social Icon' Block.
 *
 * @Block(
 *   id = "floating_icons",
 *   admin_label = @Translation("Floating Social Block"),
 *   category = @Translation("social icon"),
 * )
 */
class FloatingSocialIconsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    // Load the configuration from the form.
    $config = $this->getConfiguration();

    $facebook = isset($config['facebook']) ? $config['facebook'] : '';
    $google_plus = isset($config['google_plus']) ? $config['google_plus'] : '';
    $linkedIn = isset($config['linkedIn']) ? $config['linkedIn'] : '';
    $twitter = isset($config['twitter']) ? $config['twitter'] : '';
    $pinterest = isset($config['pinterest']) ? $config['pinterest'] : '';
    $instagram = isset($config['instagram']) ? $config['instagram'] : '';
    $mail = isset($config['mail']) ? $config['mail'] : '';
    $youtube = isset($config['youtube']) ? $config['youtube'] : '';
    $icons = isset($config['place']) ? $config['place'] : '';
    $count = isset($config['count']) ? $config['count'] : '';
    $target = isset($config['target']) ? $config['target'] : '';
    $hover = isset($config['hover']) ? $config['hover'] : '';

    // Check title field for empty.
    $facebook_title = $config['facebook_title'] ?: 'Facebook';
    $google_plus_title = $config['google_plus_title'] ?: 'GooglePlus';
    $linkedIn_title = $config['linkedIn_title'] ?: 'LinkedIn';
    $twitter_title = $config['twitter_title'] ?: 'Twitter';
    $pinterest_title = $config['pinterest_title'] ?: 'Pinterest';
    $instagram_title = $config['instagram_title'] ?: 'Instagram';
    $mail_title = $config['mail_title'] ?: 'Mail';
    $youtube_title = $config['youtube_title'] ?: 'Youtube';

    $social_values = [
      'facebook' => $facebook,
      'google_plus' => $google_plus,
      'linkedIn' => $linkedIn,
      'twitter' => $twitter,
      'pinterest' => $pinterest,
      'instagram' => $instagram,
      'mail' => $mail,
      'youtube' => $youtube,
      'icons' => $icons,
      'count' => $count,
      'target' => $target,
      'hover' => $hover,
    ];

    $social_titles = [
      'facebook_title' => $facebook_title,
      'google_plus_title' => $google_plus_title,
      'linkedIn_title' => $linkedIn_title,
      'twitter_title' => $twitter_title,
      'pinterest_title' => $pinterest_title,
      'instagram_title' => $instagram_title,
      'youtube_title' => $youtube_title,
      'mail_title' => $mail_title,
    ];

    return [
      '#theme' => 'floating_social_icons_display',
      '#social_values' => $social_values,
      '#social_titles' => $social_titles,
      '#attached' => [
        'library' => ['floating_social_icons/floating_social_icons'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    // Facebook details.
    $form['floating_facebook'] = [
      '#type' => 'details',
      '#title' => $this->t('Facebook settings'),
      '#collapsible' => TRUE,
      '#open' => TRUE,
      '#description' => '',
    ];

    $form['floating_facebook']['facebook_link'] = [
      '#type' => 'url',
      '#title' => $this->t('Facebook Link'),
      '#size' => 60,
      '#default_value' => isset($this->configuration['facebook']) ? $this->configuration['facebook'] : '',
    ];

    $form['floating_facebook']['facebook_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Facebook title'),
      '#description' => $this->t('Text to display when in active or hover state'),
      '#default_value' => isset($this->configuration['facebook_title']) ? $this->configuration['facebook_title'] : '',
      '#size' => 60,
      '#maxlength' => 128,
    ];

    // Twitter details.
    $form['floating_twitter'] = [
      '#type' => 'details',
      '#title' => $this->t('Twitter settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#description' => '',
    ];

    $form['floating_twitter']['twitter_link'] = [
      '#type' => 'url',
      '#title' => $this->t('Twitter Link'),
      '#size' => 60,
      '#default_value' => isset($this->configuration['twitter']) ? $this->configuration['twitter'] : '',
    ];

    $form['floating_twitter']['twitter_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Twitter title'),
      '#description' => $this->t('Text to display when in active or hover state'),
      '#default_value' => isset($this->configuration['twitter_title']) ? $this->configuration['twitter_title'] : '',
      '#size' => 60,
      '#maxlength' => 128,
    ];

    // Google Plus details.
    $form['floating_google_plus'] = [
      '#type' => 'details',
      '#title' => $this->t('Google Plus settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#description' => '',
    ];

    $form['floating_google_plus']['google_plus_link'] = [
      '#type' => 'url',
      '#title' => $this->t('Google Link'),
      '#size' => 60,
      '#default_value' => isset($this->configuration['google_plus']) ? $this->configuration['google_plus'] : '',
    ];

    $form['floating_google_plus']['google_plus_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('GooglePlus title'),
      '#description' => $this->t('Text to display when in active or hover state'),
      '#default_value' => isset($this->configuration['google_plus_title']) ? $this->configuration['google_plus_title'] : '',
      '#size' => 60,
      '#maxlength' => 128,
    ];

    // LinkedIn details.
    $form['floating_linkedIn'] = [
      '#type' => 'details',
      '#title' => $this->t('LinkedIn settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#description' => '',
    ];

    $form['floating_linkedIn']['linkedIn_link'] = [
      '#type' => 'url',
      '#title' => $this->t('LinkedIn Link'),
      '#size' => 60,
      '#default_value' => isset($this->configuration['linkedIn']) ? $this->configuration['linkedIn'] : '',
    ];

    $form['floating_linkedIn']['linkedIn_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('LinkedIn title'),
      '#description' => $this->t('Text to display when in active or hover state'),
      '#default_value' => isset($this->configuration['linkedIn_title']) ? $this->configuration['linkedIn_title'] : '',
      '#size' => 60,
      '#maxlength' => 128,
    ];

    // Pinterest details.
    $form['floating_pinterest'] = [
      '#type' => 'details',
      '#title' => $this->t('Pinterest settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#description' => '',
    ];

    $form['floating_pinterest']['pinterest_link'] = [
      '#type' => 'url',
      '#title' => $this->t('Pinterest Link'),
      '#size' => 60,
      '#default_value' => isset($this->configuration['pinterest']) ? $this->configuration['pinterest'] : '',
    ];

    $form['floating_pinterest']['pinterest_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pinterest title'),
      '#description' => $this->t('Text to display when in active or hover state'),
      '#default_value' => isset($this->configuration['pinterest_title']) ? $this->configuration['pinterest_title'] : '',
      '#size' => 60,
      '#maxlength' => 128,
    ];

    // Instagram details.
    $form['floating_instagram'] = [
      '#type' => 'details',
      '#title' => $this->t('Instagram settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#description' => '',
    ];

    $form['floating_instagram']['instagram_link'] = [
      '#type' => 'url',
      '#title' => $this->t('Instagram Link'),
      '#size' => 60,
      '#default_value' => isset($this->configuration['instagram']) ? $this->configuration['instagram'] : '',
    ];

    $form['floating_instagram']['instagram_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Instagram title'),
      '#description' => $this->t('Text to display when in active or hover state'),
      '#default_value' => isset($this->configuration['instagram_title']) ? $this->configuration['instagram_title'] : '',
      '#size' => 60,
      '#maxlength' => 128,
    ];

    // YouTube details.
    $form['floating_youtube'] = [
      '#type' => 'details',
      '#title' => $this->t('Youtube settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#description' => '',
    ];

    $form['floating_youtube']['youtube_link'] = [
      '#type' => 'url',
      '#title' => $this->t('Youtube Link'),
      '#size' => 60,
      '#default_value' => isset($this->configuration['youtube']) ? $this->configuration['youtube'] : '',
    ];

    $form['floating_youtube']['youtube_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Youtube title'),
      '#description' => $this->t('Text to display when in active or hover state'),
      '#default_value' => isset($this->configuration['youtube_title']) ? $this->configuration['youtube_title'] : '',
      '#size' => 60,
      '#maxlength' => 128,
    ];

    // Mail details.
    $form['floating_mail'] = [
      '#type' => 'details',
      '#title' => $this->t('mail settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#description' => '',
    ];

    $form['floating_mail']['mail_link'] = [
      '#type' => 'email',
      '#title' => $this->t('Mail Link'),
      '#size' => 60,
      '#description' => $this->t('Please type only the e-mail id'),
      '#default_value' => isset($this->configuration['mail']) ? $this->configuration['mail'] : '',
    ];

    $form['floating_mail']['mail_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mail title'),
      '#description' => $this->t('Text to display when in active or hover state'),
      '#default_value' => isset($this->configuration['mail_title']) ? $this->configuration['mail_title'] : '',
      '#size' => 60,
      '#maxlength' => 128,
    ];

    // Block details.
    $form['floating_icons'] = [
      '#type' => 'details',
      '#title' => $this->t('Display Icons'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#description' => '',
    ];

    $form['floating_icons']['hover'] = [
      '#type' => 'radios',
      '#title' => $this->t('Select the Hover Effects'),
      '#required' => TRUE,
      '#attributes' => array('class' => array('floating-block')),
      '#default_value' => isset($this->configuration['hover']) ? $this->configuration['hover'] : 'grow',
      '#options' => [
        'grow' => $this->t('Grow'),
        'shrink' => $this->t('Shrink'),
        'black-white' => $this->t('Black and white'),
        'white-black' => $this->t('White and black'),
        'rotate' => $this->t('Rotate 360'),
      ],
    ];

    $form['floating_icons']['place'] = [
      '#type' => 'radios',
      '#title' => $this->t('Where do you want to display the icons?'),
      '#required' => TRUE,
      '#default_value' => isset($this->configuration['place']) ? $this->configuration['place'] : 4,
      '#options' => [
        1 => $this->t('Top'),
        2 => $this->t('Right'),
        3 => $this->t('Bottom'),
        4 => $this->t('Left'),
      ],
    ];

    $form['floating_icons']['target'] = [
      '#type' => 'select',
      '#title' => $this->t('Target Attribute'),
      '#default_value' => isset($this->configuration['target']) ? $this->configuration['target'] : '_self',
      '#options' => [
        '_self' => $this->t('_Self'),
        '_blank' => $this->t('_Blank'),
      ],
    ];

    $form['floating_icons']['count'] = [
      '#title' => $this->t('Count'),
      '#value' => isset($this->configuration['count']) ? $this->configuration['count'] : 0,
      '#type' => 'hidden',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {

    $values = $form_state->getValues();

    $links = [];
    $links[] = $values['floating_facebook']['facebook_link'];
    $links[] = $values['floating_twitter']['twitter_link'];
    $links[] = $values['floating_google_plus']['google_plus_link'];
    $links[] = $values['floating_linkedIn']['linkedIn_link'];
    $links[] = $values['floating_pinterest']['pinterest_link'];
    $links[] = $values['floating_instagram']['instagram_link'];
    $links[] = $values['floating_mail']['mail_link'];
    $links[] = $values['floating_youtube']['youtube_link'];

    $count = 0;
    if ($links) {
      foreach ($links as $link) {
        if (!empty($link)) {
          $count = $count + 1;
        }
      }
    }
    if ($count < 2) {
      $form_state->setErrorByName('floatingsocialblock', $this->t('At least two fields should be filled.'));
    }
    // Setting count value.
    $this->configuration['count'] = $count;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {

    parent::blockSubmit($form, $form_state);

    $values = $form_state->getValues();

    $this->configuration['facebook'] = $values['floating_facebook']['facebook_link'];
    $this->configuration['google_plus'] = $values['floating_google_plus']['google_plus_link'];
    $this->configuration['linkedIn'] = $values['floating_linkedIn']['linkedIn_link'];
    $this->configuration['twitter'] = $values['floating_twitter']['twitter_link'];
    $this->configuration['pinterest'] = $values['floating_pinterest']['pinterest_link'];
    $this->configuration['instagram'] = $values['floating_instagram']['instagram_link'];
    $this->configuration['mail'] = $values['floating_mail']['mail_link'];
    $this->configuration['youtube'] = $values['floating_youtube']['youtube_link'];
    $this->configuration['place'] = $values['floating_icons']['place'];
    $this->configuration['target'] = $values['floating_icons']['target'];
    $this->configuration['hover'] = $values['floating_icons']['hover'];

    // Setting Config for Titles.
    $this->configuration['facebook_title'] = $values['floating_facebook']['facebook_title'];
    $this->configuration['google_plus_title'] = $values['floating_google_plus']['google_plus_title'];
    $this->configuration['linkedIn_title'] = $values['floating_linkedIn']['linkedIn_title'];
    $this->configuration['twitter_title'] = $values['floating_twitter']['twitter_title'];
    $this->configuration['pinterest_title'] = $values['floating_pinterest']['pinterest_title'];
    $this->configuration['instagram_title'] = $values['floating_instagram']['instagram_title'];
    $this->configuration['mail_title'] = $values['floating_mail']['mail_title'];
    $this->configuration['youtube_title'] = $values['floating_youtube']['youtube_title'];
  }

}
