<?php

namespace Drupal\youtube_block\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;


/**
 * Configure youtube_block settings for this site.
 */
class YoutubeBlockForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'youtube_block_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['youtube_block.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get module configuration.
    $config = $this->config('youtube_block.settings');

//    $options = [
//      'query' => [
//        'client_id' => '759ec610e0c1416baa8a8a6b41552087',
//        'redirect_uri' => 'http://youtube.yanniboi.com/configure/youtube',
//        'response_type' => 'code',
//      ],
//    ];

    $url = Url::fromUri('https://support.google.com/youtube/answer/6224202?hl=en');
    $link = Link::fromTextAndUrl('here', $url)->toRenderable();
    $link['#attributes']['target'] = '_blank';


//    $form['authorise'] = array(
//      '#markup' => t('To configure your youtube account you need to authorise your account.  To do this, click %link.', array('%link' => render($link))),
//    );

    $form['authorise'] = array(
      '#markup' => t('Each YouTube channel has a unique feed URL. These are used to refer to the channel in certain apps and services.  To find the feed URL for your channel, sign into Youtube and check your advanced account settings page.   To do this, click %link.', array('%link' => render($link))),
    );

    $form['feed_url'] = array(
      '#type' => 'textfield',
      '#title' => t('Feed URL'),
      '#description' => t('Your unique Youtube feed URL. Eg. https://www.youtube.com/feeds/videos.xml?user=username'),
      '#default_value' => $config->get('feed_url'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $feed_url = $form_state->getValue('feed_url');

    // Get module configuration.
    $this->config('youtube_block.settings')
      ->set('feed_url', $feed_url)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
