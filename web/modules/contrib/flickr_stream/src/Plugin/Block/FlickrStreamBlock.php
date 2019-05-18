<?php

namespace Drupal\flickr_stream\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\flickr_stream\FlickrStreamApi;
use Drupal\Core\Cache\Cache;

/**
 * Provides a 'FlickrStreamBlock' block.
 *
 * @Block(
 *  id = "flickr_stream_block",
 *  admin_label = @Translation("Flickr stream block"),
 * )
 */
class FlickrStreamBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Validation user id regex.
   */
  const VALIDATION_REGEX = '~(\d+@\w+?.\d)~';

  /**
   * The flickr api service.
   *
   * @var \Drupal\flickr_stream\FlickrStreamApi
   */
  protected $flickrApi;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FlickrStreamApi $flickrApi) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->flickrApi = $flickrApi;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('flickr.stream.api')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'flickr_user_id' => '',
      'flickr_photoset_id' => '',
      'flickr_number_photos' => 10,
      'flickr_image_style' => 'default',
      'flickr_image_class' => '',
      'flickr_wrapper_class' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $configuration = $this->getConfiguration();
    $styles = ['default' => 'Default'];
    foreach (array_keys(ImageStyle::loadMultiple()) as $style) {
      $styles[$style] = ucfirst($style);
    }

    $config = \Drupal::config('flickr_stream.settings');

    // Flickr user ID.
    $form['flickr_user_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User ID'),
      '#description' => $this->t('Flickr user ID.'),
      '#default_value' => $configuration['flickr_user_id'],
      '#maxlength' => 64,
      '#required' => TRUE,
      '#size' => 64,
    ];

    // Flickr album ID.
    $form['flickr_photoset_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Flickr Photoset ID'),
      '#description' => $this->t("Flickr Photoset ID. If leave field empty, last photos from user\'s account will be grab"),
      '#default_value' => $configuration['flickr_photoset_id'],
      '#maxlength' => 64,
      '#size' => 64,
    ];

    // Flickr images count.
    $form['flickr_number_photos'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of photos'),
      '#description' => $this->t('Number of photos to show in the block. Default 10, max 20'),
      '#default_value' => $configuration['flickr_number_photos'] ?: $config->get('flickr_stream_photo_count'),
      '#min' => 1,
      '#max' => 20,
    ];

    // Flickr images style.
    $form['flickr_image_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Images style'),
      '#description' => $this->t('Output image style'),
      '#options' => $styles,
      '#default_value' => $this->configuration['flickr_image_style'],
    ];

    // Flickr images classes.
    $form['flickr_image_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Image classes'),
      '#description' => $this->t('Classes of the image'),
      '#default_value' => $configuration['flickr_image_class'],
      '#maxlength' => 64,
      '#size' => 64,
    ];

    // Flickr images wrap classes.
    $form['flickr_wrapper_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Wrapper classes'),
      '#description' => $this->t('Classes of image wrap'),
      '#default_value' => $configuration['flickr_wrapper_class'],
      '#maxlength' => 64,
      '#size' => 64,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    parent::blockValidate($form, $form_state);
    // Validation user input data.
    if (!preg_match_all(self::VALIDATION_REGEX, $form_state->getValue('flickr_user_id'))) {
      $form_state->setErrorByName('flickr_user_id',
        $this->t('Invalid Flickr User ID, please check it.'));
    }
    if (!empty($form_state->getValue('flickr_photoset_id')) && !is_numeric($form_state->getValue('flickr_photoset_id'))) {
      $form_state->setErrorByName('flickr_photoset_id',
        $this->t('Invalid Flickr Album ID, please check it. <br>Must contain only digits'));
    }

    // Check API errors.
    if (!$form_state->hasAnyErrors()) {
      $flickr_conf = $this->flickrApi->setConfig(
        $form_state->getValue('flickr_user_id'),
        $form_state->getValue('flickr_photoset_id'),
        $form_state->getValue('flickr_number_photos')
      );
      if ($form_state->getValue('flickr_photoset_id')) {
        $response = $this->flickrApi->getAlbumPhotos($flickr_conf);
      }
      else {
        $response = $this->flickrApi->getUserPhotos($flickr_conf);
      }
      if (isset($response['stat']) && $response['stat'] == 'fail') {
        $form_state->setErrorByName('Flickr block api error',
          $response['message'] .
          ' [' . $response['code'] . ']');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();

    $this->configuration['flickr_user_id'] = $values['flickr_user_id'];
    $this->configuration['flickr_photoset_id'] = $values['flickr_photoset_id'];
    $this->configuration['flickr_number_photos'] = $values['flickr_number_photos'];
    $this->configuration['flickr_image_style'] = $values['flickr_image_style'];
    $this->configuration['flickr_image_class'] = $values['flickr_image_class'];
    $this->configuration['flickr_wrapper_class'] = $values['flickr_wrapper_class'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $build = [];
    $list = [];
    $conf = $this->getConfiguration();
    $flickr_conf = $this->flickrApi->setConfig(
      $conf['flickr_user_id'],
      $conf['flickr_photoset_id'],
      $conf['flickr_number_photos']
    );

    if ($conf['flickr_photoset_id']) {
      $response = $this->flickrApi->getAlbumPhotos($flickr_conf);
    }
    else {
      $response = $this->flickrApi->getUserPhotos($flickr_conf);
    }

    if (!$response) {
      $build['api_error'] = [
        '#markup' => $this->t('An error has occurred'),
        '#theme_wrappers' => [
          'container' => [
            '#attributes' => ['class' => ['error']],
          ],
        ],
      ];
    }
    else {
      $photos = $conf['flickr_photoset_id'] ? $response['photoset']['photo'] : $response['photos']['photo'];
      $style = ($conf['flickr_image_style'] != 'default') ? $conf['flickr_image_style'] : '';
      foreach ($photos as $key => $photo) {
        $photo_uri = $this->flickrApi->generatePhotoUri($photo);
        if ($style) {
          $list[$key] = [
            '#theme' => 'imagecache_external',
            '#uri' => $photo_uri,
            '#alt' => $photo['title'],
            '#style_name' => $style,
            '#attributes' => [
              'class' => $conf['flickr_image_class'],
            ],
          ];
        }
        else {
          $list[$key] = [
            '#theme' => 'image',
            '#uri' => imagecache_external_generate_path($photo_uri),
            '#alt' => $photo['title'],
            '#attributes' => [
              'class' => $conf['flickr_image_class'],
            ],
          ];
        }
      }
      $build[] = [
        '#theme' => 'item_list',
        '#items' => $list,
        '#cache' => [
          'contexts' => ['session'],
          'tags' => [],
          'max-age' => Cache::PERMANENT,
        ],
        '#list_type' => 'ul',
        '#attributes' => ['class' => $conf['flickr_wrapper_class']],
      ];
    }

    return $build;
  }

}
