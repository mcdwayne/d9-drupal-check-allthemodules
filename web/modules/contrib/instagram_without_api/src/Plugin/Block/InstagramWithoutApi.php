<?php

namespace Drupal\instagram_without_api\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\ServerRequest;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;

/**
 * Provides an Instagram block.
 *
 * @Block(
 *   id = "instagram_without_api",
 *   admin_label = @Translation("Instagram Without API"),
 *   category = @Translation("Social")
 * )
 */
class InstagramWithoutApi extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'name' => 'thethirstysix',
      'count' => 4,
      'width' => 200,
      'height' => 200,
      'cache' => 1440,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Instagram User Name'),      
      '#description' => $this->t('The unique Instagram user name of the account to be used for this block. [Eg. thethirstysix]'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['name'],
    );

    $form['count'] = array(
      '#type' => 'number',
      '#title' => $this->t('Number of images to display'),
      '#default_value' => $this->configuration['count'],
    );

    $form['width'] = array(
      '#type' => 'number',
      '#title' => $this->t('Image width in pixels'),
      '#default_value' => $this->configuration['width'],
    );

    $form['height'] = array(
      '#type' => 'number',
      '#title' => $this->t('Image height in pixels'),
      '#default_value' => $this->configuration['height'],
    );

    $form['cache'] = array(
      '#type' => 'number',
      '#title' => $this->t('Cache time in minutes'),
      '#description' => $this->t("Default is 1440 - 24 hours."),
      '#default_value' => $this->configuration['cache'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    if ($form_state->hasAnyErrors()) {
      return;
    }
    else {
      $this->configuration['name'] = $form_state->getValue('name');
      $this->configuration['count'] = $form_state->getValue('count');
      $this->configuration['width'] = $form_state->getValue('width');
      $this->configuration['height'] = $form_state->getValue('height');
      $this->configuration['cache'] = $form_state->getValue('cache');    
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Build a render array to return the Instagram Images.
    $build = array();
    if ($this->configuration['name']) {
      
      $url = "https://www.instagram.com/{$this->configuration['name']}";
     
      // Get Response file_get_contents($url);
      $instagram_url = \Drupal::httpClient()->get($url);
      $response = (string) $instagram_url->getBody();
      if (empty($response)) {
        //Can't find Url
        return FALSE; 
      }

      // the start position
      $start_position = strpos($response ,'window._sharedData = ');
      // string length to trim before
      $start_positionlength = strlen('window._sharedData = ');
      // trim preceding content
      $trimmed_before = trim(substr($response, ($start_position + $start_positionlength)));
      // end position
      $end_position = strpos($trimmed_before, '</script>');
      // trim content
      $trimmed = trim( substr($trimmed_before, 0, $end_position));
      // remove extra trailing ";"
      $jsondata = substr($trimmed, 0, -1);
      // JSON decode
      $obj = Json::decode($jsondata, true);

      if(isset($obj['entry_data']['ProfilePage']['0']['graphql']['user']['edge_owner_to_timeline_media']['edges'])) {
        $variable = $obj['entry_data']['ProfilePage']['0']['graphql']['user']['edge_owner_to_timeline_media']['edges'];

        $slice_variable = array_slice($variable, 0, $this->configuration['count']);

        foreach ($slice_variable as $key => $value) {
          // Generate path
          $shortcode = $value['node']['shortcode'];
          // Image source
          $src = $value['node']['thumbnail_src'];
          $data[] = array('image' => $src, 'path' => 'https://www.instagram.com/p/'.$shortcode);
        }

        $build = [
          '#theme' => 'instagram_without_api_image',
          '#data' => $data,
          '#width' => $this->configuration['width'],
          '#height' => $this->configuration['height'],      
        ]; 

        // Add CSS
        $build['#attached']['library'][] = 'instagram_without_api/iwa_styles';

        // Cache
        $build['#cache']['context'][] = 'languages:language_content';
        $build['#cache']['max_age'] = $this->configuration['cache'] * 60;
      }      
    }
    return $build;
  }

}
