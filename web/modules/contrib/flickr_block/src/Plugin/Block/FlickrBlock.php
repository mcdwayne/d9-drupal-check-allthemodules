<?php

namespace Drupal\flickr_block\Plugin\Block;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\flickr_block\FlickrAPI;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;

/**
 * Provides a 'FlickrBlock' block.
 *
 * @Block(
 *  id = "flickr_block",
 *  admin_label = @Translation("Flickr block"),
 * )
 */
class FlickrBlock extends BlockBase implements ContainerFactoryPluginInterface {


  /**
   * Flickr API.
   *
   * @var \Drupal\flickr_block\FlickrAPI
   */
  protected $flickrAPI;

  /**
   * ConfigFactory var.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * FlickrBlock constructor.
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              FlickrAPI $flickrAPI,
                              ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->flickrAPI = $flickrAPI;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('flickr.block.api'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'flickr_api_key' => '',
      'flickr_user_id' => '',
      'flickr_photoset_id' => '',
      'flickr_number_photos' => 9,
      'flickr_header_size_init' => $this->t('c'),
      'flickr_size_init' => $this->t('s'),
      'flickr_size_end' => $this->t('b'),
      'flickr_open_in_flickr' => FALSE,
      'flickr_image_class' => '',
      'flickr_link_class' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {

    $config = $this->configFactory->get('flickr_block.config');

    // API KEY.
    $form['flickr_api_key'] = [
      '#default_value' => $this->configuration['flickr_api_key'] ?: $config->get('flickr_api_key'),
      '#description' => $this->t('Flickr Api Key. <a href="https://www.flickr.com/services/apps/create/apply" target="_blank">More info</a>'),
      '#maxlength' => 40,
      '#required' => TRUE,
      '#size' => 64,
      '#title' => $this->t('API KEY'),
      '#type' => 'textfield',
    ];
    // USER ID.
    $form['flickr_user_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User ID'),
      '#description' => $this->t('Flickr user ID using format 12345678@N02. <a href="https://www.webpagefx.com/tools/idgettr/" target="_blank">How obtain from username?</a>'),
      '#default_value' => $this->configuration['flickr_user_id'] ?: $config->get('flickr_user_id'),
      '#maxlength' => 64,
      '#required' => TRUE,
      '#size' => 64,
    ];
    // PHOTOSET ID.
    $form['flickr_photoset_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Flickr Photoset ID'),
      '#description' => $this->t('Flickr Photoset ID. Is this field is filled this fotos will be loaded'),
      '#default_value' => $this->configuration['flickr_photoset_id'],
      '#maxlength' => 64,
      '#size' => 64,
    ];

    // SIZE OF THE IMAGE IN BLOCK.
    $form['flickr_header_size_init'] = [
      '#type' => 'select',
      '#title' => $this->t('Header Photo size'),
      '#description' => $this->t('If has header Photo choose a size'),
      '#options' => ['' => $this->t('No image en header')] + $this->flickrAPI->getPhotoSizes(),
      '#default_value' => $this->configuration['flickr_header_size_init'],
      '#size' => 1,
    ];
    // NUMBER OF PHOTOS.
    $form['flickr_number_photos'] = [
      '#type' => 'number',
      '#title' => $this->t('Numer of photos'),
      '#description' => $this->t('Number of photos to show in the block. Min 1 and Max 20'),
      '#default_value' => $this->configuration['flickr_number_photos'],
      '#min' => 1,
      '#max' => 20,
    ];
    // SIZE OF THE IMAGE IN BLOCK.
    $form['flickr_size_init'] = [
      '#type' => 'select',
      '#title' => $this->t('Initial size'),
      '#description' => $this->t('Size of the photos'),
      '#options' => $this->flickrAPI->getPhotoSizes(),
      '#default_value' => $this->configuration['flickr_size_init'],
      '#size' => 1,
    ];
    // SIZE OF THE IMAGE ON CLICK (IF NEXT OPTION IS NOT
    // CHECKED).
    $form['flickr_size_end'] = [
      '#type' => 'select',
      '#title' => $this->t('End size'),
      '#description' => $this->t('End size to link from the photos'),
      '#options' => $this->flickrAPI->getPhotoSizes(),
      '#default_value' => $this->configuration['flickr_size_end'],
      '#size' => 1,
    ];
    // OPEN PHOTO EN FLICKR PAGE.
    $form['flickr_open_in_flickr'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Open link in Flickr page?'),
      '#description' => $this->t('On click the image the largest image open in flickr page'),
      '#default_value' => $this->configuration['flickr_open_in_flickr'],
    ];
    // CLASS OR CLASSES FOR IMAGE.
    $form['flickr_image_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Image class/es'),
      '#description' => $this->t('Class/es of the image'),
      '#default_value' => $this->configuration['flickr_image_class'],
      '#maxlength' => 64,
      '#size' => 64,
    ];
    // CLASS OR CLASSES FOR LINK.
    $form['flickr_link_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link class/es'),
      '#description' => $this->t('Class/es of image link'),
      '#default_value' => $this->configuration['flickr_link_class'],
      '#maxlength' => 64,
      '#size' => 64,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {

    $params = $this->flickrAPI->generateParams([
      'flickr_api_key' => $form_state->getValue('flickr_api_key'),
      'flickr_user_id' => $form_state->getValue('flickr_user_id'),
      'flickr_number_photos' => $form_state->getValue('flickr_number_photos'),
      'flickr_photoset_id' => $form_state->getValue('flickr_photoset_id'),
    ]);

    $response = $this->flickrAPI->call($params);
    if (!$response) {
      $form_state->setErrorByName('',
        $this->t('An error has occurred whit the Flickr API response.'));
    }
    else {
      if (isset($response['stat']) && $response['stat'] == 'fail') {
        switch ($response['code']) {
          case 100:
            $form_state->setErrorByName('flickr_api_key',
              $response['message'] .
              ' [' . $response['code'] . ']');
            break;

          case 2:
            $form_state->setErrorByName('flickr_user_id', $response['message'] .
              ' [' . $response['code'] . ']');
            break;

          case 1:
            $form_state->setErrorByName('flickr_photoset_id', $response['message'] .
              ' [' . $response['code'] . ']');
            break;

          default:
            $form_state->setErrorByName('', $response['message'] .
              ' [' . $response['code'] . ']');
            break;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['flickr_api_key'] = $form_state->getValue('flickr_api_key');
    $this->configuration['flickr_user_id'] = $form_state->getValue('flickr_user_id');
    $this->configuration['flickr_photoset_id'] = $form_state->getValue('flickr_photoset_id');
    $this->configuration['flickr_number_photos'] = $form_state->getValue('flickr_number_photos');
    $this->configuration['flickr_header_size_init'] = $form_state->getValue('flickr_header_size_init');
    $this->configuration['flickr_size_init'] = $form_state->getValue('flickr_size_init');
    $this->configuration['flickr_size_end'] = $form_state->getValue('flickr_size_end');
    $this->configuration['flickr_open_in_flickr'] = $form_state->getValue('flickr_open_in_flickr');
    $this->configuration['flickr_image_class'] = $form_state->getValue('flickr_image_class');
    $this->configuration['flickr_link_class'] = $form_state->getValue('flickr_link_class');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $list = [];

    $params = $this->flickrAPI->generateParams($this->configuration);

    $response = $this->flickrAPI->call($params);

    if (!$response) {
      $build['API']['#markup'] = '<div class="error">' .
        $this->t('An error has occurred') . '</div>';
    }
    else {
      $photos = $this->configuration['flickr_photoset_id'] ? $response['photoset']['photo'] : $response['photos']['photo'];
      $isFirst = TRUE;
      foreach ($photos as $photo) {

        $imgClass = '';
        $linkClass = '';

        if ($isFirst) {
          $uri_image_init =
            $this->flickrAPI->generatePhotoUri($photo,
              $this->configuration['flickr_header_size_init']);
          $imgClass = 'flickr-block-header-img';
          $linkClass = 'flickr-block-header-link';
          $isFirst = FALSE;
        }
        else {
          $uri_image_init =
            $this->flickrAPI->generatePhotoUri($photo,
              $this->configuration['flickr_size_init']);
        }

        if ($this->configuration['flickr_open_in_flickr']) {
          $uri_end = $this->flickrAPI->generatePhotoUriFlickr($photo['id'],
            $this->configuration['flickr_user_id']);
          $target = "_blank";
        }
        else {
          $uri_end = $this->flickrAPI->generatePhotoUri($photo,
            $this->configuration['flickr_size_end']);
          $target = "_self";
        }

        $list[] = [
          '#title' => [
            '#theme' => 'image',
            '#uri' => $uri_image_init,
            '#alt' => $photo['title'],
            '#title' => $photo['title'],
            '#attributes' => [
              'class' => $this->configuration['flickr_image_class'] . ' ' . $imgClass,
            ],
          ],
          '#type' => 'link',
          '#url' => Url::fromUri($uri_end),
          '#attributes' => [
            'class' => $this->configuration['flickr_link_class'] . ' ' . $linkClass,
            'title' => $photo['title'],
            'target' => $target,
          ],
        ];
      }

      $build[] = [
        '#theme' => 'item_list',
        '#items' => $list,
        '#cache' => ['max-age' => 0],
        '#list_type' => 'ul',
        '#attributes' => ['class' => 'flickr-block'],
      ];

    }

    return $build;
  }

}
