<?php

namespace Drupal\merlinone\Plugin\EntityBrowser\Widget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\Token;
use Drupal\entity_browser\WidgetBase;
use Drupal\entity_browser\WidgetValidationManager;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use Drupal\merlinone\MerlinOneApiInterface;
use Drupal\merlinone\Plugin\media\Source\MerlinOneMediaSourceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * An Entity Browser widget to create media from the MerlinOne library.
 *
 * @EntityBrowserWidget(
 *   id = "merlinone_search",
 *   label = @Translation("MerlinOne search"),
 *   description = @Translation("Search and import from the MerlinOne library")
 * )
 */
class MerlinOneSearch extends WidgetBase {

  /**
   * The MerlinOne API Service.
   *
   * @var \Drupal\merlinone\MerlinOneApiInterface
   */
  protected $merlinOneApi;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The file system interface.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Constructs widget plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\entity_browser\WidgetValidationManager $validation_manager
   *   The Widget Validation Manager service.
   * @param \Drupal\merlinone\MerlinOneApiInterface $merlinOneApi
   *   The MerlinOne API service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system interface.
   * @param \Drupal\Core\Utility\Token $token
   *   The token utility.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher, EntityTypeManagerInterface $entity_type_manager, WidgetValidationManager $validation_manager, MerlinOneApiInterface $merlinOneApi, AccountInterface $current_user, FileSystemInterface $file_system, Token $token) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher, $entity_type_manager, $validation_manager);
    $this->merlinOneApi = $merlinOneApi;
    $this->currentUser = $current_user;
    $this->fileSystem = $file_system;
    $this->token = $token;
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
      $container->get('merlinone.api'),
      $container->get('current_user'),
      $container->get('file_system'),
      $container->get('token')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'media_type' => NULL,
      'upload_location' => 'public://merlinone/[date:custom:Y]-[date:custom:m]',
    ] + parent::defaultConfiguration();
  }

  /**
   * Returns the media type that this widget creates.
   *
   * @return \Drupal\media\MediaTypeInterface
   *   Media type.
   */
  protected function getType() {
    return $this->entityTypeManager
      ->getStorage('media_type')
      ->load($this->configuration['media_type']);
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $additional_widget_parameters) {
    $form = parent::getForm($original_form, $form_state, $additional_widget_parameters);

    $mx_url = $this->merlinOneApi->getMxUrl();

    // Embedded search.
    $form['merlinone_search'] = [
      '#type' => 'html_tag',
      '#tag' => 'iframe',
      '#attributes' => [
        'src' => $mx_url,
        'class' => 'merlinone-search-iframe',
        'width' => '100%',
        'height' => 410,
        'frameborder' => 0,
        'style' => 'padding: 0; width: 1px !important; min-width: 100% !important; overflow: hidden !important;',
      ],
    ];

    // Attach library and pass domain settings.
    $form['#attached']['library'][] = 'merlinone/entity_browser';
    $form['#attached']['drupalSettings']['merlinone']['entity_browser']['mx_host'] = parse_url($mx_url)['host'];

    // Hold the response from the search.
    $form['merlinone_response'] = [
      '#type' => 'hidden',
      '#default_value' => '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $configuration = $this->configuration;

    $form['upload_location'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Upload location'),
      '#default_value' => $configuration['upload_location'],
    ];

    $form['media_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Media type'),
      '#required' => TRUE,
      '#description' => $this->t('The type of media entity to create from the uploaded file(s).'),
    ];

    $type = $this->getType();
    if ($type) {
      $form['media_type']['#default_value'] = $type->id();
    }

    // Find media types that implement MerlinOneMediaSourceInterface.
    $types = [];
    foreach ($this->entityTypeManager->getStorage('media_type')->loadMultiple() as $type) {
      if ($type->getSource() instanceof MerlinOneMediaSourceInterface) {
        $types[] = $type;
      }
    }

    if (!empty($types)) {
      foreach ($types as $type) {
        $form['media_type']['#options'][$type->id()] = $type->label();
      }
    }
    else {
      $form['media_type']['#disabled'] = TRUE;
      $form['media_type']['#description'] = $this->t('You must @create_type before using this widget.', [
        '@create_type' => Link::createFromRoute($this->t('create a media type'), 'entity.media_type.add_form')->toString(),
      ]);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();

    // Depend on the media type this widget creates.
    $type = $this->getType();
    $dependencies[$type->getConfigDependencyKey()][] = $type->getConfigDependencyName();
    $dependencies['module'][] = 'media';

    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareEntities(array $form, FormStateInterface $form_state) {
    $media = [];
    $type = $this->getType();
    $plugin = $type->getSource();
    $source_field = $plugin->getConfiguration()['source_field'];

    if ($merlinResponse = $form_state->getValue('merlinone_response')) {
      $items = json_decode($merlinResponse);
      foreach ($items as $item) {

        // Prepare destination.
        $uploadDir = $this->getUploadLocation($item);
        $file = $this->merlinOneApi->createFileFromItem($item, $uploadDir);

        if ($file) {
          $file->setOwnerId($this->currentUser->id());
          $file->save();

          $media[] = Media::create([
            'bundle' => $type->id(),
            $source_field => $file->id(),
            'name' => !empty($item->headline) ? $item->headline : NULL,
            // Keep original item to use when filling in mapped media fields.
            'original_item' => $item,
          ]);
        }
      }
    }

    // Keep reference to the prepared entities.
    $form_state->set('merlinone_prepared_entities', $media);

    return $media;
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array &$element, array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\media\MediaInterface[] $media */
    $media = $form_state->get('merlinone_prepared_entities');

    array_walk($media, function (MediaInterface $media_item) {
      $media_item->save();
    });
    $this->selectEntities($media, $form_state);
    $this->clearFormValues($element, $form_state);
  }

  /**
   * Clear values from Merlin response form element.
   *
   * @param array $element
   *   Upload form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  protected function clearFormValues(array &$element, FormStateInterface $form_state) {
    // The hidden value and user input needs to be removed otherwise files will
    // re-download when the selection form is submitted.
    $form_state->setValueForElement($element['merlinone_response'], '');
    NestedArray::setValue($form_state->getUserInput(), $element['merlinone_response']['#parents'], '');
  }

  /**
   * Gets upload location.
   *
   * @param object $item
   *   The item from the MerlinOne response JSON. This parameter is unused here,
   *   but can be used in an extending class to change the location depending on
   *   metadata in the incoming file.
   *
   * @return string
   *   Destination folder URI.
   */
  protected function getUploadLocation($item) {
    $uploadDir = $this->token->replace($this->configuration['upload_location']);
    if (!file_exists($uploadDir)) {
      $this->fileSystem->mkdir($uploadDir, NULL, TRUE);
    }

    return $uploadDir;
  }

}
