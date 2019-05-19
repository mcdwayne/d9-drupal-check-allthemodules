<?php

/**
 * @file
 * Contains \Drupal\tweetbutton\Plugin\Block\TweetbuttonTweetBlock.
 */

namespace Drupal\tweetbutton\Plugin\Block;

use Drupal\Core\Session\AccountInterface;
use Drupal\block\Annotation\Block;
use Drupal\block\BlockBase;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Provides a 'Tweetbutton Tweet' block.
 *
 * @Block(
 *   id = "tweetbutton_tweet_block",
 *   admin_label = @Translation("Tweetbutton Tweet"),
 *   module = "tweetbutton"
 *)
 */
class TweetbuttonTweetBlock extends BlockBase {


  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    return $account->hasPermission('access tweetbutton');
  }

  /**
   * Overrides \Drupal\block\BlockBase::defaultConfiguration().
   */
  public function defaultConfiguration() {
    $config = \Drupal::config('tweetbutton.settings');
    $default = array(
      'label_display' => FALSE,
      'text'          => '',
      'count'         => 'horizontal',
      'lang'          => 'en',
      'size'          => 'medium',
      'dnt'           => FALSE,
    );

    return $default;
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockForm().
   */
  public function blockForm($form, &$form_state) {
    $form['tweet'] = array(
      '#type' => 'fieldset',
      '#title' => t('Button settings'),
      '#collapsible' => FALSE,
    );
    $form['tweet']['text'] = array(
      '#type' => 'textfield',
      '#title' => t('Tweet text'),
      '#default_value' => $this->configuration['text'],
    );
    $form['tweet']['count'] = array(
      '#type' => 'select',
      '#title' => t('Count box position'),
      '#options' => array(
        'none'       => t('None'),
        'horizontal' => t('Horizontal'),
        'vertical'   => t('Vertical'),
      ),
      '#default_value' => $this->configuration['count'],
    );
    $form['tweet']['size'] = array(
      '#type' => 'select',
      '#title' => t('Button Size'),
      '#options' => array(
        'medium' => t('Medium'),
        'large' => t('Large'),
      ),
      '#default_value' => $this->configuration['size'],
    );
    $form['tweet']['dnt'] = array(
      '#type' => 'checkbox',
      '#title' => t('Opt-out of tailoring Twitter'),
      '#default_value' => $this->configuration['dnt'],
    );

    return $form;
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockSubmit().
   */
  public function blockSubmit($form, &$form_state) {
    $settings = $form_state['values']['tweet'];

    $this->configuration = array(
      'text' => $settings['text'],
      'count' => $settings['count'],
      'size'  => $settings['size'],
      'dnt'   => $settings['dnt'],
    );
  }

  /**
   * Implements \Drupal\block\BlockBase::blockBuild().
   */
  public function build() {
    return array(
      '#theme' => 'tweetbutton_tweet_display',
      '#options' => array(
        'text' => $this->configuration['text'],
        'count' => $this->configuration['count'],
        'size'  => $this->configuration['size'],
        'dnt'   => $this->configuration['dnt'],
      ),
    );
  }

}
