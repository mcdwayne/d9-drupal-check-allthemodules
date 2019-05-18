<?php

namespace Drupal\bynder\Plugin\EntityBrowser\Widget;

use Drupal\bynder\BynderApiInterface;
use Drupal\bynder\Exception\BrandNotSetException;
use Drupal\bynder\Exception\BundleNotBynderException;
use Drupal\bynder\Exception\BundleNotExistException;
use Drupal\bynder\Exception\BynderException;
use Drupal\bynder\Exception\UnableToConnectException;
use Drupal\bynder\Exception\UploadPermissionException;
use Drupal\bynder\Exception\UploadFailedException;
use Drupal\bynder\Plugin\media\Source\Bynder;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Url;
use Drupal\entity_browser\WidgetValidationManager;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Drupal\Core\Entity\EntityStorageException;

/**
 * Uses upload to create media entities.
 *
 * @EntityBrowserWidget(
 *   id = "bynder_upload",
 *   label = @Translation("Bynder upload"),
 *   description = @Translation("Uploads files to Bynder and creates wrapping media entities.")
 * )
 */
class BynderUpload extends BynderWidgetBase {

  /**
   * Number of times to try fetching an asset during the batch.
   */
  const FAIL_LIMIT = 30;

  /**
   * The session service.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * Upload constructor.
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
   * @param \Drupal\bynder\BynderApiInterface $bynder_api
   *   Bynder API service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Logger factory.
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The session service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher, EntityTypeManagerInterface $entity_type_manager, WidgetValidationManager $validation_manager, BynderApiInterface $bynder_api, ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory, SessionInterface $session, LanguageManagerInterface $language_manager, RequestStack $request_stack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher, $entity_type_manager, $validation_manager, $bynder_api, $logger_factory, $language_manager, $request_stack, $config_factory);
    $this->session = $session;
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
      $container->get('bynder_api'),
      $container->get('config.factory'),
      $container->get('logger.factory'),
      $container->get('session'),
      $container->get('language_manager'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'brand' => NULL,
      'extensions' => 'jpg jpeg png gif',
      'dropzone_description' => $this->t('Drop files here to upload them.'),
      'tags' => [],
      'metaproperty_options' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $additional_widget_parameters) {
    $form = parent::getForm($original_form, $form_state, $additional_widget_parameters);
    $has_upload_permissions = $this->bynderApi->hasUploadPermissions();

    if (empty($this->configuration['brand'])) {
      (new BrandNotSetException($this->configuration['entity_browser_id']))->logException()->displayMessage();
      return [];
    }
    elseif (!$has_upload_permissions) {
      $form['actions']['submit']['#access'] = FALSE;
      (new UploadPermissionException($this->configuration['entity_browser_id']))->logException();
      $form['message'] = [
        '#markup' => $this->t("Unable to upload files to Bynder. Make sure your user account has enough permissions."),
      ];
    }
    else {
      if ($form_state->getValue('errors')) {
        $form['actions']['submit']['#access'] = FALSE;
        return $form;
      }

      $form['upload'] = [
        '#title' => $this->t('File upload'),
        '#type' => 'dropzonejs',
        '#dropzone_description' => $this->getConfiguration()['settings']['dropzone_description'],
      ];

      $form['description'] = [
        '#title' => $this->t('Description'),
        '#type' => 'textarea',
        '#description' => $this->t('Description text to be added to the assets.'),
      ];

      $this->session->set('upload_permissions', $has_upload_permissions);
      if ($uploaded_assets = $this->session->get('bynder_upload_batch_result', [])) {
        $form_state->set('uploaded_entities', $uploaded_assets);
        $this->session->remove('bynder_upload_batch_result');
        $form['upload']['#access'] = FALSE;
        $form['description']['#access'] = FALSE;
        $form['#attached']['library'][] = 'bynder/upload';
        $form['actions']['submit']['#attributes']['class'][] = 'visually-hidden';
        $form['message']['#markup'] = $this->t('Finishing upload. Please wait...');
      }
      else {
        $form['actions']['submit']['#eb_widget_main_submit'] = FALSE;
        $form['actions']['submit']['#bynder_upload_submit'] = TRUE;
      }

    }
    return $form;

  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntities(array $form, FormStateInterface $form_state) {
    if ($entities = $form_state->get('uploaded_entities')) {
      return $entities;
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array &$element, array &$form, FormStateInterface $form_state) {
    if (!empty($form_state->getTriggeringElement()['#bynder_upload_submit'])) {
      /** @var \Drupal\media\MediaTypeInterface $type */
      $type = $this->entityTypeManager->getStorage('media_type')
        ->load($this->configuration['media_type']);

      if ($type && ($type->getSource()) instanceof Bynder) {
        $form_state->setRebuild();
        $batch = [
          'title' => $this->t('Uploading assets to Bynder'),
          'init_message' => $this->t('Initializing upload.'),
          'progress_message' => $this->t('Processing (@percentage)...'),
          'operations' => [],
          'finished' => [static::class, 'batchFinish'],
        ];
        foreach ((array) $form_state->getValue(['upload', 'uploaded_files'], []) as $file) {
          $batch['operations'][] = [
            [static::class, 'batchUploadFiles'],
            [
              $file,
              $this->configuration['brand'],
              $form_state->getValue('description', ''),
              $this->configuration['tags'],
              $this->configuration['metaproperty_options'],
            ],
          ];
        }

        foreach ((array) $form_state->getValue(['upload', 'uploaded_files'], []) as $file) {
          $batch['operations'][] = [
            [static::class, 'batchCreateEntities'],
            [
              $file,
              $type->get('source_configuration')['source_field'],
              $type->id(),
            ],
          ];
        }

        // Batch redirect callback needs UUID so we save it into the session.
        $this->session->set('bynder_upload_batch_uuid', $form_state->get(['entity_browser', 'instance_uuid']));
        batch_set($batch);

        // Now that the batch is set manually set source URL which will ensure
        // that we persist all needed query arguments when redirected back to
        // the form.
        if (\Drupal::request()->query->count()) {
          $batch = &batch_get();
          $source_url = Url::fromRouteMatch(\Drupal::routeMatch());
          $source_url->setOption('query', \Drupal::request()->query->all());
          $batch['source_url'] = $source_url;
        }
      }
      else {
        if (!$type) {
          (new BundleNotExistException($this->configuration['media_type']))->logException()->displayMessage();
        }
        else {
          (new BundleNotBynderException($type->label()))->logException()->displayMessage();
        }

      }
    }
    elseif (!empty($form_state->getTriggeringElement()['#eb_widget_main_submit'])) {
      try {
        $media = $this->prepareEntities($form, $form_state);
        array_walk($media, function (MediaInterface $item) {
          if (!$item->id()) {
            // Some race conditions might occur in some circumstances and could
            // try to save this entity twice.
            try {
              $item->save();
            }
            catch (EntityStorageException $e) {
            }
          }
        });
        $this->selectEntities($media, $form_state);
        $form_state->set('uploaded_assets', NULL);
        $this->clearFormValues($element, $form_state);
      }
      catch (BynderException $e) {
        $e->displayMessage();
        return;
      }
    }
  }

  /**
   * Upload batch operation callback which uploads assets to Bynder.
   */
  public static function batchUploadFiles($file, $brand, $description, $tags, $metaproperty_options, &$context) {
    try {

      $data = [
        'filePath' => \Drupal::service('file_system')->realpath($file['path']),
        'brandId' => $brand,
        'name' => $file['filename'],
      ];

      if ($description) {
        $data['description'] = $description;
      }

      if ($tags) {
        $data['tags'] = implode(',', $tags);
      }

      if ($metaproperty_options) {
        foreach ($metaproperty_options as $metaproperty => $options) {
          $data['metaproperty.' . $metaproperty] = implode(',', $options);
        }
      }

      if (isset($context['results']['accessRequestId'])) {
        $data['accessRequestId'] = $context['results']['accessRequestId'];
      }
      $result = \Drupal::service('bynder_api')->uploadFileAsync($data);
      $context['results']['accessRequestId'] = $result['accessRequestId'];
      $context['results'][$file['path']] = $result['mediaid'];
      file_unmanaged_delete($file['path']);
      $context['message'] = t('Uploaded @file to Bynder.', ['@file' => $file['filename']]);
    }
    catch (\Exception $e) {
      // If fetching failed try few more times. If waiting doesn't help fail the
      // batch eventually.
      if (empty($context['sandbox']['fails'])) {
        $context['sandbox']['fails'] = 0;
      }
      $context['sandbox']['fails']++;
      $context['finished'] = 0;
      $context['message'] = t('Uploading @file to Bynder.', ['@file' => $file['filename']]);

      if ($context['sandbox']['fails'] >= static::FAIL_LIMIT) {
        throw $e;
      }
    }
  }

  /**
   * Upload batch operation callback which creates media entities.
   */
  public static function batchCreateEntities($file, $source_field, $bundle, &$context) {
    try {
      if (\Drupal::service('session')->get('upload_permissions', FALSE) != 'MEDIAUPLOADFORAPPROVAL') {
        // Let's try to fetch the uploaded resource from the API as we will be
        // able to save it only if that succeeds.
        $uuid = $context['results'][$file['path']];
        \Drupal::service('bynder_api')->getMediaInfo($uuid);

        $entity = Media::create([
          'bundle' => $bundle,
          $source_field => $uuid,
        ]);
        unset($context['results'][$file['path']]);
        $context['results'][] = $entity;
        $context['message'] = t('Mapped @file locally.', ['@file' => $file['filename']]);
      }
      else {
        drupal_set_message(t("Your file was uploaded to Bynder but needs to be approved before you can use it. Please go to your Bynder waiting room and review the uploaded assets."), 'warning');
      }
      $context['finished'] = 1;
    }
    catch (\Exception $e) {
      // If fetching failed try few more times. If waiting doesn't help fail the
      // batch eventually.
      if (empty($context['sandbox']['fails'])) {
        $context['sandbox']['fails'] = 0;
      }
      $context['sandbox']['fails']++;
      $context['finished'] = 0;
      $context['message'] = t('Mapping @file locally.', ['@file' => $file['filename']]);
      sleep(3);

      if ($context['sandbox']['fails'] >= static::FAIL_LIMIT) {
        (new UploadFailedException(t("There was an unexpected error after uploading the file to Bynder.")))->logException()->displayMessage();
        drupal_set_message(t("There was an unexpected error after uploading the file to Bynder. Please contact your site administrator for more info."), 'warning');
      }
    }
  }

  /**
   * Upload batch finish callback.
   *
   * Stores results (media entities) into the session for the form to be able to
   * pick them up.
   */
  public static function batchFinish($success, $results, $operations) {
    if (\Drupal::service('session')->get('upload_permissions', FALSE) != 'MEDIAUPLOADFORAPPROVAL') {
      unset($results['accessRequestId']);
      // Save results into the form state to make them available in the form.
      \Drupal::service('session')->set('bynder_upload_batch_result', $results);
    }
  }

  /**
   * Clear values from upload form element.
   *
   * @param array $element
   *   Upload form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  protected function clearFormValues(array &$element, FormStateInterface $form_state) {
    $form_state->setValueForElement($element['upload']['uploaded_files'], '');
    NestedArray::setValue($form_state->getUserInput(), $element['upload']['uploaded_files']['#parents'], '');
    $form_state->set('uploaded_entities', NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    foreach ($this->entityTypeManager->getStorage('media_type')->loadMultiple() as $type) {
      /** @var \Drupal\media\MediaTypeInterface $type */
      if ($type->getSource() instanceof Bynder) {
        $form['media_type']['#options'][$type->id()] = $type->label();
      }
    }

    if (empty($form['media_type']['#options'])) {
      $form['media_type']['#disabled'] = TRUE;
      $form['media_type']['#description'] = $this->t('You must @create_bundle before using this widget.', [
        '@create_bundle' => Link::createFromRoute($this->t('create a Bynder media type'), 'media.bundle_add')->toString(),
      ]);
    }

    $brand_options = [];
    try {
      foreach ($this->bynderApi->getBrands() as $brand) {
        $brand_options[$brand['id']] = $brand['name'];
        foreach ($brand['subBrands'] as $sub_brand) {
          $brand_options[$sub_brand['id']] = '- ' . $sub_brand['name'];
        }
      }
    }
    catch (RequestException $e) {
      (new UnableToConnectException())->displayMessage();
    }

    $form['brand'] = [
      '#type' => 'select',
      '#title' => $this->t('Brand'),
      '#default_value' => $this->configuration['brand'],
      '#required' => TRUE,
      '#options' => $brand_options,
    ];

    if (empty($this->configuration['brand'])) {
      $form['brand']['#empty_option'] = $this->t('- Set brand -');
    }

    $form['extensions'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allowed file extensions'),
      '#desciption' => $this->t('A space separated list of file extensions'),
      '#default_value' => $this->configuration['extensions'],
    ];

    $form['dropzone_description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Dropzone drag-n-drop zone text'),
      '#default_value' => $this->configuration['dropzone_description'],
    ];

    $form['tags'] = [
      '#type' => 'textfield',
      '#description' => $this->t('Comma-separated list of tags that should be assigned to all uploaded assets.'),
      '#title' => $this->t('Tags'),
      '#default_value' => implode(', ', $this->configuration['tags']),
    ];

    $metaproperties = $this->bynderApi->getMetaproperties();
    $metaproperty_options = [];
    foreach ($metaproperties as $metaproperty) {
      $metaproperty_options[$metaproperty['id']] = $metaproperty['label'];
    }

    $form['metaproperties'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Metaproperties'),
      '#options' => $metaproperty_options,
      '#description' => $this->t('Select metaproperties whose options should be added to all uploaded assets. Options will be selected in the next step.'),
      '#default_value' => array_keys($this->configuration['metaproperty_options']),
      '#ajax' => [
        'callback' => 'Drupal\bynder\Plugin\EntityBrowser\Widget\BynderUpload::ajaxMetaproperties',
        'wrapper' => 'metaproperty-options-wrapper',
      ],
    ];

    $parents = ['table', $this->uuid, 'form', 'metaproperties'];
    $enabled_metaproperties = $form_state->getValue($parents) ?: array_keys($this->configuration['metaproperty_options']);
    $enabled_metaproperties = array_filter(array_values($enabled_metaproperties));
    $form['metaproperty_options'] = [
      '#type' => 'details',
      '#title' => $this->t('Metaproperty options'),
      '#attributes' => ['id' => 'metaproperty-options-wrapper'],
      '#open' => TRUE,
    ];

    if (empty($enabled_metaproperties)) {
      $form['metaproperty_options']['#attributes']['class'][] = 'visually-hidden';
    }

    foreach ($metaproperties as $metaproperty) {
      if (in_array($metaproperty['id'], $enabled_metaproperties)) {
        $options = [];
        foreach ($metaproperty['options'] as $option) {
          $options[$option['id']] = $option['displayLabel'];
        }

        $form['metaproperty_options'][$metaproperty['id']] = [
          '#type' => 'select',
          '#multiple' => TRUE,
          '#title' => $metaproperty['label'],
          '#options' => $options,
          '#default_value' => $this->configuration['metaproperty_options'][$metaproperty['id']],
        ];
      }
    }

    return $form;
  }

  /**
   * Ajax callback for metaproperties configuration.
   */
  public static function ajaxMetaproperties(array &$form, FormStateInterface &$form_state, $request) {
    $parents = array_slice($form_state->getTriggeringElement()['#array_parents'], 0, -2);
    $parents[] = 'metaproperty_options';
    return NestedArray::getValue($form, $parents);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['tags'] = explode(',', $this->configuration['tags']);
    $this->configuration['tags'] = array_map('trim', $this->configuration['tags']);
    $this->configuration['metaproperty_options'] = array_filter($this->configuration['metaproperty_options']);
    unset($this->configuration['metaproperties']);
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array &$form, FormStateInterface $form_state) {
    try {
      parent::validate($form, $form_state);
    }
    catch (BynderException $e) {
      $e->displayMessage();
      return;
    }
  }

}
