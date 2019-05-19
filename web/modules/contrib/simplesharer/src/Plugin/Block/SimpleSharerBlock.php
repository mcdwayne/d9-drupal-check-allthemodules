<?php

namespace Drupal\simplesharer\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a SocialSimpleSharerBlock
 *
 *
 * @Block(
 *   id = "simplesharer_block",
 *   admin_label = @Translation("Social SimpleSharer")
 * )
 */
  class SimpleSharerBlock extends BlockBase {

    /**
     * {@inheritdoc}
     */
    public function defaultConfiguration() {
      $default_config = \Drupal::config('simplesharer.settings');
      return array(
        'simplesharer_style' => $default_config->get('simplesharer.style'),
        'simplesharer_library' => $default_config->get('simplesharer.library'),
        'simplesharer_facebook' => $default_config->get('simplesharer.facebook'),
        'simplesharer_twitter' => $default_config->get('simplesharer.twitter'),
        'simplesharer_pinterest' => $default_config->get('simplesharer.pinterest'),
        'simplesharer_linkedin' => $default_config->get('simplesharer.linkedin'),
        'simplesharer_tumblr' => $default_config->get('simplesharer.tumblr'),
        'simplesharer_reddit' => $default_config->get('simplesharer.reddit'),
        'simplesharer_email' => $default_config->get('simplesharer.email'),
      );
    }

    /**
     * {@inheritdoc}
     */
    public function blockForm($form, FormStateInterface $form_state) {

      $config = $this->getConfiguration();
      $form['simplesharer_style_select'] = array(
        '#type' => 'select',
        '#title' => $this->t('Style'),
        '#description' => $this->t('Use an optional built-in CSS style.'),
        '#options' => array(
          'none' => $this->t('None: User Provided'),
          'ideas' => $this->t('Ideas By Elliot Style'),
          'workasone' => $this->t('Work As One Style'),
          'social-media-standard' => $this->t('Social Media Standard'),
          'awesome' => $this->t('Awesome Style'),
        ),
        '#default_value' => isset($config['simplesharer_style']) ? $config['simplesharer_style'] : NULL,
        '#required' => TRUE,
      );
      $form['simplesharer_library_select'] = array(
        '#type' => 'select',
        '#title' => $this->t('Library'),
        '#description' => $this->t('Use an optional icon library'),
        '#options' => array(
          'none' => $this->t('None: Plain Text'),
          'fontawesome' => $this->t('Font Awesome Icons'),
          'foundation' => $this->t('Foundation Icon Set'),
        ),
        '#default_value' => isset($config['simplesharer_library']) ? $config['simplesharer_library'] : NULL,
        '#required' => TRUE,
      );
      $form['simplesharer_facebook_check'] = array(
        '#type'           => 'checkbox',
        '#title'          => t('Enable Facebook'),
        '#description'    => t('Enable the Facebook Social SimpleSharer Button'),
        '#default_value' => isset($config['simplesharer_facebook']) ? $config['simplesharer_facebook'] : NULL,
      );
      $form['simplesharer_twitter_check'] = array(
        '#type'           => 'checkbox',
        '#title'          => t('Enable Twitter'),
        '#description'    => t('Enable the Twitter Social SimpleSharer Button'),
        '#default_value' => isset($config['simplesharer_twitter']) ? $config['simplesharer_twitter'] : NULL,
      );
      $form['simplesharer_pinterest_check'] = array(
        '#type'           => 'checkbox',
        '#title'          => t('Enable Pinterest'),
        '#description'    => t('Enable the Pinterest Social SimpleSharer Button'),
        '#default_value' => isset($config['simplesharer_pinterest']) ? $config['simplesharer_pinterest'] : NULL,
      );
      $form['simplesharer_linkedin_check'] = array(
        '#type'           => 'checkbox',
        '#title'          => t('Enable LinkedIN'),
        '#description'    => t('Enable the LinkedIN Social SimpleSharer Button'),
        '#default_value' => isset($config['simplesharer_linkedin']) ? $config['simplesharer_linkedin'] : NULL,
      );
      $form['simplesharer_tumblr_check'] = array(
        '#type'           => 'checkbox',
        '#title'          => t('Enable Tumblr'),
        '#description'    => t('Enable the Tumblr Social SimpleSharer Button'),
        '#default_value' => isset($config['simplesharer_tumblr']) ? $config['simplesharer_tumblr'] : NULL,
      );
      $form['simplesharer_reddit_check'] = array(
        '#type'           => 'checkbox',
        '#title'          => t('Enable Reddit'),
        '#description'    => t('Enable the Reddot Social SimpleSharer Button'),
        '#default_value' => isset($config['simplesharer_reddit']) ? $config['simplesharer_reddit'] : NULL,
      );
      $form['simplesharer_email_check'] = array(
        '#type'           => 'checkbox',
        '#title'          => t('Enable E-mail'),
        '#description'    => t('Enable the E-mail Social SimpleSharer Button'),
        '#default_value' => isset($config['simplesharer_email']) ? $config['simplesharer_email'] : NULL,
      );

      return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function blockSubmit($form, FormStateInterface $form_state) {
      $this->configuration['simplesharer_style']
        = $form_state->getValue('simplesharer_style_select');
      $this->configuration['simplesharer_library']
        = $form_state->getValue('simplesharer_library_select');
      $this->configuration['simplesharer_facebook']
        = $form_state->getValue('simplesharer_facebook_check');
      $this->configuration['simplesharer_twitter']
        = $form_state->getValue('simplesharer_twitter_check');
      $this->configuration['simplesharer_pinterest']
        = $form_state->getValue('simplesharer_pinterest_check');
      $this->configuration['simplesharer_linkedin']
        = $form_state->getValue('simplesharer_linkedin_check');
      $this->configuration['simplesharer_tumblr']
        = $form_state->getValue('simplesharer_tumblr_check');
      $this->configuration['simplesharer_reddit']
        = $form_state->getValue('simplesharer_reddit_check');
      $this->configuration['simplesharer_email']
        = $form_state->getValue('simplesharer_email_check');
    }

    /**
     * {@inheritdoc}
     */
    public function build() {
      return simplesharer_content($this->getConfiguration());
    }
  }
