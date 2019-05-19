<?php

/**
 * @file
 * Contains \Drupal\tweetbutton\Plugin\Block\TweetbuttonFollowBlock.
 */

namespace Drupal\tweetbutton\Plugin\Block;

use Drupal\Core\Session\AccountInterface;
use Drupal\block\Annotation\Block;
use Drupal\block\BlockBase;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Provides a 'Tweetbutton Follow' block.
 *
 * @Block(
 *   id = "tweetbutton_follow_block",
 *   admin_label = @Translation("Tweetbutton Follow"),
 *   module = "tweetbutton"
 *)
 */
class TweetbuttonFollowBlock extends BlockBase {

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
      'label_display'    => FALSE,
      'show_count'       => FALSE,
      'lang'             => 'en',
      'width'            => '',
      'align'            => 'none',
      'show_screen_name' => TRUE,
      'size'             => 'medium',
      'dnt'              => FALSE,
    );

    if (!empty($config->get('tweetbutton_follow_screen_name'))) {
      $default['screen_name'] = $config->get('tweetbutton_follow_screen_name');
    }

    return $default;
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockForm().
   */
  public function blockForm($form, &$form_state) {
    $form['follow'] = array(
      '#type' => 'fieldset',
      '#title' => t('Button settings'),
      '#collapsible' => FALSE,
    );
    $form['follow']['show_count'] = array(
      '#type' => 'checkbox',
      '#title' => t('Followers count display'),
      '#default_value' => $this->configuration['show_count'],
    );
    $form['follow']['lang'] = array(
      '#type' => 'select',
      '#title' => t('Language'),
      '#options' => array(
        'en'   => t('English'),
        'fr'   => t('French'),
        'de'   => t('German'),
        'es'   => t('Spanish'),
        'ja'   => t('Japanese'),
        'auto' => t('Automatic'),
      ),
      '#description' => t('This is the language that the button will render in on your website. People will see the Tweet dialog in their selected language for Twitter.com.'),
      '#default_value' => $this->configuration['lang'],
    );
    $form['follow']['width'] = array(
      '#type' => 'textfield',
      '#title' => t('Width'),
      '#description' => t('Enter the width of the block in pixels.'),
      '#default_value' => $this->configuration['width'],
    );
    $form['follow']['align'] = array(
      '#type' => 'select',
      '#title' => t('Alignment'),
      '#options' => array(
        'none'   => t('None'),
        'left'   => t('Left'),
        'right'   => t('Right'),
      ),
      '#default_value' => $this->configuration['align'],
    );
    $form['follow']['show_screen_name'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show Screen Name'),
      '#default_value' => $this->configuration['show_screen_name'],
    );
    $form['follow']['screen_name'] = array(
      '#type' => 'textfield',
      '#title' => t('Screen Name'),
      '#default_value' => $this->configuration['screen_name'],
      '#description' => t('If you leave the field blank, it will use the global setting of screen name.'),
    );
    $form['follow']['size'] = array(
      '#type' => 'select',
      '#title' => t('Button Size'),
      '#options' => array(
        'medium' => t('Medium'),
        'large' => t('Large'),
      ),
      '#default_value' => $this->configuration['size'],
    );
    $form['follow']['dnt'] = array(
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
    $settings = $form_state['values']['follow'];

    $this->configuration = array(
      'show_count'       => $settings['show_count'],
      'lang'             => $settings['lang'],
      'width'            => $settings['width'],
      'align'            => $settings['align'],
      'show_screen_name' => $settings['show_screen_name'],
      'screen_name'      => $settings['screen_name'],
      'size'             => $settings['size'],
      'dnt'              => $settings['dnt'],
    );
  }

  /**
   * Implements \Drupal\block\BlockBase::blockBuild().
   */
  public function build() {
    return array(
      '#theme' => 'tweetbutton_follow_display',
      '#options' => array(
        'show_count'       => $this->configuration['show_count'],
        'lang'             => $this->configuration['lang'],
        'width'            => $this->configuration['width'],
        'align'            => $this->configuration['align'],
        'show_screen_name' => $this->configuration['show_screen_name'],
        'screen_name'      => $this->configuration['screen_name'],
        'size'             => $this->configuration['size'],
        'dnt'              => $this->configuration['dnt'],
      ),
    );
  }

}
