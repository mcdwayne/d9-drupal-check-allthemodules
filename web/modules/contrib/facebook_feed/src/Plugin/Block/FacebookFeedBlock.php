<?php

namespace Drupal\facebook_feed\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Serialization\Json;

/**
 * Provides a 'FacebookFeedBlock' block.
 *
 * @Block(
 *  id = "facebook_feed_block",
 *  admin_label = @Translation("Facebook feed"),
 * )
 */
class FacebookFeedBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'page_id' => '',
      'access_token' => '',
      'show_socials' => TRUE,
      'limit' => 10,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['feed_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Feed settings'),
      '#weight' => '5',
    ];

    $form['feed_settings']['page_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Page ID'),
      '#description' => $this->t('ID of the page'),
      '#default_value' => $this->configuration['page_id'],
      '#maxlength' => 64,
      '#size' => 15,
      '#required' => TRUE,
    ];

    $form['feed_settings']['page_id_info'] = [
      '#type' => 'details',
      '#title' => $this->t('What is my page ID?'),
      '#weight' => '6',
    ];
    $form['feed_settings']['page_id_info']['summary'] = [
      '#markup' => '<p>If you have a Facebook <b>page</b> with a URL like this: <code>https://www.facebook.com/your_page_name</code> then the Page ID is just <b>your_page_name</b>.</p>',
    ];

    $form['feed_settings']['access_token'] = [
      '#type' => 'textarea',
      '#title' => $this->t('(optional) Access token'),
      '#description' => $this->t('Access token needed to deal with the Facebook API'),
      '#default_value' => $this->configuration['access_token'],
      '#weight' => '7',
    ];

    $form['feed_settings']['access_token_info'] = [
      '#type' => 'details',
      '#title' => $this->t('What is an access token?'),
      '#weight' => '8',
    ];
    $form['feed_settings']['access_token_info']['summary'] = [
      '#markup' => '<p>A Facebook Access Token is not required to use this module, but we recommend it so that you are not reliant on the token built into the module.</p>'
      . '<p>If you have your own token then you can enter it here.</p>'
      . '<p>To get your own Access Token you can follow these step-by-step instructions.</p>',
    ];

    $form['feed_settings']['show_socials'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show social media stats'),
      '#description' => $this->t('Whether the number of likes, comments and shares of each post should be shown.'),
      '#default_value' => $this->configuration['show_socials'],
      '#weight' => '9',
    ];

    $form['feed_settings']['limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Posts limit'),
      '#description' => $this->t('The maximum number of posts that will be fetched.'),
      '#default_value' => $this->configuration['limit'],
      '#min' => 0,
      '#max' => 100,
      '#step' => 1,
      '#size' => 3,
      '#weight' => '10',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['page_id'] = $form_state->getValue('feed_settings')['page_id'];
    $this->configuration['access_token'] = $form_state->getValue('feed_settings')['access_token'];
    $this->configuration['show_socials'] = $form_state->getValue('feed_settings')['show_socials'];
    $this->configuration['limit'] = $form_state->getValue('feed_settings')['limit'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $posts = [];

    try {
      $posts = $this->getPosts();
    }
    catch (\Exception $e) {
      \Drupal::messenger()->addMessage(t('Facebook error.'), 'error');
    }

    $build = [
      '#prefix' => '<div class="facebook_feed">',
      '#suffix' => '</div>',
    ];

    $build['#attached']['library'][] = 'facebook_feed/display';
    $build['#attached']['library'][] = 'facebook_feed/font_awesome';

    if (!$posts) {
      return $build;
    }

    foreach ($posts['data'] as $post) {
      $build[$post['id']] = $this->themePost($post);
    }

    return $build;
  }

  /**
   * Fetches a list of page posts using Facebook's Graph API.
   *
   * @return array
   *   An array of objects containing post data.
   */
  private function getSettings() {

    $postFields = [
      'id',
      'created_time',
      'message',
      'picture',
      'link',
      'comments.summary(true)',
      'reactions.summary(true)',
      'likes.summary(true)',
      'shares',
    ];

    $settings = [
      'page_id' => $this->configuration['page_id'],
      'feedType' => 'feed',
      'limit' => $this->configuration['limit'],
      'access_token' => $this->getAccessToken(),
      'fields' => implode(',', $postFields),
    ];
    return $settings;
  }

  /**
   * Fetches a list of page posts using Facebook's Graph API.
   *
   * @return mixed
   *   An array of objects containing post data.
   */
  private function getPosts() {

    $settings = $this->getSettings();

    $uri = 'https://graph.facebook.com/'
      . $settings['page_id'] . '/'
      . $settings['feedType']
      . '?summary=true&limit=' . $settings['limit']
      . '&access_token=' . $settings['access_token']
      . '&fields=' . $settings['fields'];

    $response = \Drupal::httpClient()->get($uri, [
      'headers' => ['Accept' => 'text/plain'],
    ]);

    return Json::decode($response->getBody());
  }

  /**
   * Get Access token.
   *
   * @return string|null
   *   Token.
   */
  private function getAccessToken() {
    $accessToken = $this->configuration['access_token'];

    if (!$accessToken) {
      return NULL;
    }

    return $accessToken;
  }

  /**
   * Creates a themable array of post data.
   *
   * @param array $post
   *   Object containing post data from a call to Facebook's Graph API.
   *
   * @return array
   *   A renderable array.
   */
  private function themePost(array $post) {

    return [
      '#theme' => 'facebook_post',
      '#id' => $post['id']?: '',
      '#created_time' => $post['created_time']?: '',
      '#message' => $post['message'] ?: '',
      '#picture' => $post['picture'] ?: '',
      '#link' => $post['link'] ?: '',
      '#show_socials' => $this->configuration['show_socials'],
      '#num_likes' => \array_key_exists('likes', $post) ? $post['likes']['summary']['total_count'] : 0,
      '#num_comments' => \array_key_exists('comments', $post) ? $post['comments']['summary']['total_count'] : 0,
      '#num_reactions' => \array_key_exists('reactions', $post) ? $post['reactions']['summary']['total_count'] : 0,
      '#num_shares' => \array_key_exists('shares', $post) ? $post['shares']['count'] : 0,
    ];
  }

}
