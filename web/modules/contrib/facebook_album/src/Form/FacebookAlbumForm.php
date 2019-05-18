<?php

/**
 * @file
 * Contains \Drupal\facebook_album\Form\FacebookAlbumForm.
 */

namespace Drupal\facebook_album\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\facebook_album\FacebookAlbumInterface;

/**
 * Configure facebook_album settings for this site.
 */
class FacebookAlbumForm extends ConfigFormBase {
  /**
   * The Drupal configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;
  /**
   * The FB Album controller.
   *
   * @var FacebookAlbumInterface
   */
  protected $facebook_album;

  /**
   * {@inheritdoc}
   *
   * @param FacebookAlbumInterface $facebook_album
   *   The controls of facebook album.
   */
  public function __construct(ConfigFactoryInterface $config_factory, FacebookAlbumInterface $facebook_album) {
    parent::__construct($config_factory);
    $this->configFactory = $config_factory;
    $this->facebook_album = $facebook_album;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('facebook_album.controller')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'facebook_album_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['facebook_album.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get module configuration.
    $config = $this->config('facebook_album.settings')->get();

    // @TODO: Style this, add more detail
    if (!empty($config['access_token'])) {
      $form['notice'] = [
        '#markup' => $this->t('You already have configured your application.')
      ];
    }

    $form['app_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Facebook App ID'),
      '#default_value' => $config['app_id'],
      '#required' => TRUE,
      '#description' => $this->t('The application ID specified in your Facebook App\'s dashboard page.'),
    ];
    $form['app_secret'] = [
      '#type' => 'password',
      '#title' => $this->t('Facebook App Secret'),
      '#default_value' => $config['app_secret'],
      '#required' => TRUE,
      '#description' => $this->t('The application secret specified in your Facebook App\'s dashboard page. This field remains blank for security purposes. If you have already saved your application secret, leave this field blank, unless you wish to update it.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Call Facebook and get an access token for the given app
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $app_id = $form_state->getValue('app_id');
    $app_secret = $form_state->getValue('app_secret');
    $auth_path = FACEBOOK_ALBUM_API_AUTH_PATH . 'access_token';

    $parameters = [
      'client_id'     => $app_id,
      'client_secret' => $app_secret,
      'grant_type'    => 'client_credentials',
    ];

    $response = $this->facebook_album->get($auth_path, $parameters);

    if (isset($response['error'])) {
      $message = $this->facebook_album->translate_error($response['error']['code'], $response['error']['message']);
      $form_state->setErrorByName('app_secret', $message);
    }
    else {
      $form_state->setValue('access_token', $response['access_token']);
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $page_id = $form_state->getValue('page_id');
    $app_id = $form_state->getValue('app_id');
    $app_secret = $form_state->getValue('app_secret');
    $access_token = $form_state->getValue('access_token');

    // Get module configuration.
    $this->config('facebook_album.settings')
      ->set('page_id', $page_id)
      ->set('app_id', $app_id)
      ->set('app_secret', $app_secret)
      ->set('access_token', $access_token)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
