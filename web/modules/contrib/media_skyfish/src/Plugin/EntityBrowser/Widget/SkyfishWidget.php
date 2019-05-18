<?php

namespace Drupal\media_skyfish\Plugin\EntityBrowser\Widget;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\Token;
use Drupal\entity_browser\Plugin\EntityBrowser\Widget\Upload;
use Drupal\entity_browser\WidgetValidationManager;
use Drupal\media_skyfish\ApiService;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Plugin implementation of the 'skyfish' widget.
 *
 * @EntityBrowserWidget(
 *   id = "skyfishwidget",
 *   label = @Translation("Skyfish"),
 *   description = "Adds Skyfish upload integration.",
 *   auto_select = FALSE
 * )
 */
class SkyfishWidget extends Upload {

  /**
   * Drupal logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Skyfish api service.
   *
   * @var \Drupal\media_skyfish\ApiService
   */
  protected $connect;

  /**
   * Http client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * SkyfishWidget constructor.
   *
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher, EntityTypeManagerInterface $entity_type_manager, WidgetValidationManager $validation_manager, ModuleHandlerInterface $module_handler, Token $token, Client $client, AccountInterface $account, LoggerInterface $logger, ApiService $api_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher, $entity_type_manager, $validation_manager, $module_handler, $token);

    $this->logger = $logger;
    $this->connect = $api_service;
    $this->client = $client;
    $this->user = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('event_dispatcher'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.entity_browser.widget_validation'),
      $container->get('module_handler'),
      $container->get('token'),
      $container->get('http_client'),
      $container->get('current_user'),
      $container->get('logger.channel.media_skyfish'),
      $container->get('media_skyfish.apiservice')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Since we extend default entity_browser Upload widget,
    // it contains unused configuration fields, so we remove them.
    unset($form['upload_location'], $form['extensions'], $form['multiple']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $additional_widget_parameters) {
    $form = parent::getForm($original_form, $form_state, $additional_widget_parameters);

    // Since we extend default entity_browser Upload widget,
    // it contains unused upload field, so we remove it.
    unset($form['upload']);

    $folders = $this->connect->getFolders();

    // Show message if there is an error while getting folders.
    if (!is_array($folders) || empty($folders)) {
      $error_message = $folders->message ? $this->t($folders->message) : $this->t('Error while getting data');
      drupal_set_message($error_message, 'error');

      return $form;
    }

    // Create VerticalTabs element to recreate folder structure in skyfish.
    $form['skyfish'] = [
      '#type' => 'vertical_tabs',
      '#default_tab' => str_replace('_', '-', 'edit_folder_' . $folders[0]->id),
      '#attributes' => [
        'class' => [
          'skyfish',
        ],
      ],
    ];

    // Parse through each of the folders that are available.
    foreach ($folders as $folder) {
      $images = $this->connect->getImagesInFolder($folder->id);

      // If there are no images, break the loop for particular folder.
      if (empty($images)) {
        continue;
      }

      // Create single vertical tab for particular folder.
      $form['folder_' . $folder->id] = [
        '#type' => 'details',
        '#group' => 'skyfish',
        '#title' => $folder->name,
        '#attributes' => [
          'class' => [
            'skyfish__folder',
            'folder',
          ],
        ],
      ];

      // Create list of imags in the folder.
      foreach ($images as $image) {
        $form['folder_' . $folder->id][$image->unique_media_id] = [
          '#type' => 'checkbox',
          '#title' => '<img src="' . $image->thumbnail_url . '" class="image__thumbnail">',
          '#attributes' => [
            'class' => [
              'folder__image',
              'image',
            ],
          ],
        ];
      }
    }

    // Attach pager library to display images properly with pager settings.
    $form['#attached']['drupalSettings']['media_skyfish']['pager']['media_skyfish_items_per_page'] = $this->connect->config->getItemsPerPage();
    $form['#attached']['library'][] = 'media_skyfish/pager';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array &$element, array &$form, FormStateInterface $form_state) {
    $form_values = $form_state->getValues();
    $media = [];

    // Get images from Skyfish API and map their metadata with selected values.
    $this->selectImagesFromFolders($media, $form_values);

    // Get images metadata and save them.
    $images_with_metadata = $this->connect->getImagesMetadata($media);
    $saved_images = $this->saveImages($images_with_metadata);
    // Pass seleted images to the entity they are to be added to.
    $this->selectEntities($saved_images, $form_state);
  }

  /**
   * Get images from Skyfish API folders.
   *
   * @param array $media
   *   Array of mapped images metadata from Skyfish API.
   * @param array $form_values
   *   Array of selected images to be downloaded from Skyfish API.
   */
  protected function selectImagesFromFolders(array &$media, array $form_values) {
    $folders = $this->connect->getFolders();

    foreach ($folders as $folder) {
      $images = $this->connect->getImagesInFolder($folder->id);
      $this->selectImagesFromFormValues($media, $form_values, $images);
    }
  }

  /**
   * Map Skyfish API images metadata with selected values in form.
   *
   * @param array $media
   *   Array of mapped images metadata from Skyfish API.
   * @param array $form_values
   *   Array of selected images to be downloaded from Skyfish API.
   * @param array $images
   *   Array of images metadata from Skyfish API.
   */
  protected function selectImagesFromFormValues(array &$media, array $form_values, array $images) {
    foreach ($images as $image) {
      if (isset($form_values[$image->unique_media_id]) &&
        $form_values[$image->unique_media_id] === 1) {
        $media[$image->unique_media_id] = $image;
      }
    }
  }

  /**
   * Save images in array.
   *
   * @param array $images
   *   Skyfish images.
   *
   * @return array $images
   *   Array of images.
   */
  protected function saveImages(array $images) {
    foreach ($images as $image_id => $image) {
      $images[$image_id] = $this->saveFile($image);
    }

    return $images;
  }

  /**
   * Default system file scheme.
   *
   * @return array|mixed|null
   *   Default scheme.
   */
  public function fileDefaultScheme() {
    return \Drupal::config('system.file')->get('default_scheme');
  }

  /**
   * Save file in the system.
   *
   * @param \stdClass $image
   *   Skyfish image.
   *
   * @return \Drupal\file\FileInterface|false $file
   *   Saved image.
   */
  protected function saveFile(\stdClass $image) {
    $folder = $this->fileDefaultScheme() . '://media-skyfish/' . $this->user->id() . '/';
    $destination = $folder . $image->filename;

    // Create directory for user images.
    file_prepare_directory($folder, FILE_CREATE_DIRECTORY);
    // Save file in the system from the url.
    $file = system_retrieve_file($image->download_url, $destination, TRUE, FILE_EXISTS_RENAME);

    // If file was not saved, throw an error.
    if (!$file) {
      $this->logger->error('Unable to save file for @image', ['@image' => $image->filename]);
    }
    return $file;
  }

}
