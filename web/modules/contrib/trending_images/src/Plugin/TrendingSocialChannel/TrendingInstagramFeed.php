<?php

namespace Drupal\trending_images\Plugin\TrendingSocialChannel;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\trending_images\TrendingImagesService;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'trending_instagram_feed' channel.
 *
 * @TrendingImagesSocialChannel(
 *   id = "instagram_channel",
 *   channel = "instagram",
 *   label = @Translation("Instagram"),
 * )
 */
class TrendingInstagramFeed extends PluginBase implements TrendingImagesInterface, ConfigurablePluginInterface, PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'access_token' => '',
      'instagram_tag' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // TODO: Use this form in configuration

    /*
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID (API key)'),
      '#required' => TRUE,
      '#default_value' => $this->getConfiguration()['api_key'],
    ];

    $form['api_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client secret (API secret)'),
      '#required' => TRUE,
      '#default_value' => $this->getConfiguration()['api_secret'],
    ];
    */

    $form['instagram_tag'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Instagram tag'),
      '#description' => $this->t('Instagram tag to pull images from.'),
      '#default_value' => $this->getConfiguration()['instagram_tag'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->setConfiguration($form_state->getValues());
  }

  /**
   * Gets the content of a social network channel.
   */

  public function getSocialNetworkFeed($amount, $settings, $timestamp){
    $pluginConfiguration = \Drupal::config('trending_images.config');
    $accessToken = $pluginConfiguration->get('instagram_authentication_token');
    $socialNetworkFeed = [];
    $tag = $pluginConfiguration->get('instagram_tag_'.$this->configuration['field_machine_name']);
    if(empty($tag)){
      $request = \Drupal::httpClient()->request('GET', 'https://api.instagram.com/v1/users/self/media/recent/?access_token='.$accessToken);
    }else{
      $request = \Drupal::httpClient()->request('GET', 'https://api.instagram.com/v1/tags/'.$tag.'/media/recent?access_token='.$accessToken);
    }

    $dataArray = json_decode($request->getBody());
    $data = $dataArray->data;
    foreach ($data as $image) {
      $link = $image->link;
      $likes = $image->likes->count;
      $comments = $image->comments->count;
      $fetchedImage = \Drupal::service('trending_images.service')->fetchImageFromUlr($image->images->standard_resolution, $settings);
      $socialNetworkFeed[] = [
        'link' => $link,
        'likes' => $likes,
        'comments' => $comments,
        'image' => $fetchedImage,
        'width' => $image->images->standard_resolution->width,
        'height' => $image->images->standard_resolution->height,
        'permanent' => 0,
        'channel' => $this->pluginDefinition['channel']
      ];
    }
    return $socialNetworkFeed;
  }

}
