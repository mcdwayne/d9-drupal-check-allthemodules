<?php

namespace Drupal\ib_dam_media\Plugin\EntityBrowser\Widget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\Token;
use Drupal\entity_browser\WidgetBase;
use Drupal\entity_browser\WidgetValidationManager;
use Drupal\ib_dam\Asset\Asset;
use Drupal\ib_dam\Asset\EmbedAssetInterface;
use Drupal\ib_dam\Asset\LocalAssetInterface;
use Drupal\ib_dam\AssetFormatter\AssetFormatterManager;
use Drupal\ib_dam\AssetValidation\AssetValidationManager;
use Drupal\ib_dam\AssetValidation\AssetValidationTrait;
use Drupal\ib_dam\Downloader;
use Drupal\ib_dam\Exceptions\AssetDownloaderBadResponse;
use Drupal\ib_dam\IbDamResourceModel as Model;
use Drupal\ib_dam_media\AssetStorage\MediaStorage;
use Drupal\ib_dam_media\Exceptions\MediaStorageUnableSaveMediaItem;
use Drupal\ib_dam_media\Exceptions\MediaTypeMatcherBadMediaTypeMatch;
use Drupal\ib_dam_media\MediaTypeMatcher;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * An Entity Browser widget to create media from the IntelligenceBank DAM.
 *
 * @EntityBrowserWidget(
 *   id = "ib_dam_search",
 *   label = @Translation("IntelligenceBank Asset Browser"),
 *   description = @Translation("Browse and import assets from IntelligenceBank DAM")
 * )
 */
class IbDamEbWidget extends WidgetBase {

  use AssetValidationTrait;
  use MessengerTrait;

  const ADMIN_PERMISSION = 'administer intelligencebank configuration';

  protected $mediaTypeMatcher;
  protected $mediaTypesConfig;
  protected $downloader;
  protected $assetValidationManager;

  /**
   * Debug state.
   *
   * @var bool
   */
  private $debug;

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
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

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
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Utility\Token $token
   *   The token utility.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory service.
   * @param \Drupal\ib_dam\AssetValidation\AssetValidationManager $asset_validation_manager
   *   The asset validation manager service.
   * @param \Drupal\ib_dam\Downloader $downloader
   *   The downloader service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\ib_dam_media\MediaTypeMatcher $mediaTypeMatcher
   *   The media type matcher service.
   */
  public function __construct(
      array $configuration,
      $plugin_id,
      $plugin_definition,
      EventDispatcherInterface $event_dispatcher,
      EntityTypeManagerInterface $entity_type_manager,
      WidgetValidationManager $validation_manager,
      AccountInterface $current_user,
      Token $token,
      LoggerChannelFactoryInterface $logger_factory,
      AssetValidationManager $asset_validation_manager,
      Downloader $downloader,
      ConfigFactoryInterface $config_factory,
      MediaTypeMatcher $mediaTypeMatcher
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher, $entity_type_manager, $validation_manager);
    $this->currentUser = $current_user;
    $this->token = $token;
    $this->logger = $logger_factory->get('ib_dam');
    $this->mediaTypesConfig = (array) $config_factory->get('ib_dam_media.settings')->get('media_types');
    $this->assetValidationManager = $asset_validation_manager;
    $this->downloader = $downloader;
    $this->mediaTypeMatcher = $mediaTypeMatcher;

    $debug_mode  = (boolean) $config_factory->get('ib_dam.settings')->get('debug');
    $has_rights  = $this->currentUser->hasPermission(self::ADMIN_PERMISSION);
    $this->debug = $debug_mode && $has_rights ?: FALSE;
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
      $container->get('current_user'),
      $container->get('token'),
      $container->get('logger.factory'),
      $container->get('plugin.manager.ib_dam.asset_validation'),
      $container->get('ib_dam.downloader'),
      $container->get('config.factory'),
      $container->get('ib_dam_media.media_type_matcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'upload_location' => 'public://intelligencebank',
    ] + parent::defaultConfiguration();
  }

  /**
   * Build allowed file extensions list for a given media types.
   *
   * @param array $media_type_ids
   *   An array of media type ids.
   *
   * @return array
   *   An array of allowed file extensions.
   */
  private function getAllowedFileExtensionsList(array $media_type_ids = []) {
    $supported_media_type_ids = array_column(
      $this->mediaTypesConfig,
      'media_type'
    );

    if (!$media_type_ids) {
      $media_type_ids = $supported_media_type_ids;
    }
    else {
      $media_type_ids = array_intersect(
        $supported_media_type_ids,
        $media_type_ids
      );
    }
    return $this->mediaTypeMatcher->getAllowedFileExtensions($media_type_ids, FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['upload_location'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Upload location'),
      '#default_value' => $this->configuration['upload_location'],
    ];

    if (empty($this->mediaTypesConfig)) {
      $this->getMediaTypesError(TRUE);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $additional_widget_parameters) {
    $form = parent::getForm($original_form, $form_state, $additional_widget_parameters);
    $step = $form_state->get('step') ?: 'process';

    if ($step === 'process') {
      $form_state->set('step', $step);
      $this->buildProcessStep($form, $form_state);
    }
    elseif ($step === 'configure') {
      $this->buildConfigureStep($form, $form_state);
    }

    return $form;
  }

  /**
   * Build process step form.
   *
   * @param array &$form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  protected function buildProcessStep(array &$form, FormStateInterface $form_state) {
    $allow_embed     = FALSE;
    $is_entity_embed = FALSE;
    $target_bundles  = [];
    $widget_context  = $form_state->get(['entity_browser', 'widget_context']);

    if (!empty($widget_context['ib_dam_media']['allowed_media_types'])) {
      $target_bundles = array_keys($widget_context['ib_dam_media']['allowed_media_types']);
    }

    $file_extensions = $this->getAllowedFileExtensionsList($target_bundles);

    if (!empty($this->mediaTypesConfig['embed']['media_type'])) {
      $embed_media_type_id = $this->mediaTypesConfig['embed']['media_type'];
      $allow_embed = in_array($embed_media_type_id, $target_bundles);
    }

    if (empty($target_bundles)) {
      if (!empty($widget_context['ib_dam_media']['field'])) {
        $this->showEmptyTargetBundlesError($widget_context['ib_dam_media']['field']);
      }
      elseif (!empty($widget_context['ib_dam_media']['is_entity_embed'])) {
        $is_entity_embed = TRUE;
        $this->showEntityEmbedError();
      }
    }

    if (!$file_extensions) {
      $this->getMediaTypesError(TRUE);
    }

    if ((!$file_extensions && !$allow_embed) || $is_entity_embed) {
      $form['actions']['submit']['#disabled'] = TRUE;
      return;
    }

    static::setWidgetSetting($form_state, 'configured_extensions', $file_extensions);
    static::setWidgetSetting($form_state, 'allow_embed', $allow_embed);

    $extensions_message = $this->t("<p>Please, configure allowed file types on field configuration pages.</p>Allowed file extensions to download:<br>@types", [
      '@types' => implode(', ', $file_extensions),
    ])->render();

    $media_types_message = $this->getMediaTypesError()->render();

    $form['ib_dam_app_el'] = [
      '#type' => 'ib_dam_app',
      '#file_extensions' => $file_extensions,
      '#allow_embed' => $allow_embed,
      '#debug_response' => $this->debug,
      '#messages' => [
        [
          'id' => 'local',
          'once' => TRUE,
          'text' => $extensions_message . '<p>' . $media_types_message . '</p>',
          'title' => $this->t("This file isn't allowed to download."),
        ],
        [
          'id' => 'embed',
          'once' => TRUE,
          'text' => $media_types_message,
          'title' => $this->t("You're not allowed to embed files."),
        ],
      ],
      '#submit_selector' => '.is-entity-browser-submit',
    ];
  }

  /**
   * Build configure step form.
   *
   * @param array &$form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  protected function buildConfigureStep(array &$form, FormStateInterface $form_state) {
    /* @var $asset \Drupal\ib_dam\Asset\AssetInterface */
    $asset     = $this->getAssets($form_state, TRUE);
    $formatter = AssetFormatterManager::create($asset);

    $form['settings'] = [
      '#type'  => 'fieldset',
      '#tree'  => TRUE,
      '#title' => $this->t('Settings'),
    ];
    $form['settings'] += $formatter->settingsForm($asset);
  }

  /**
   * Creates asset object from iframe app response item.
   *
   * @param \stdClass $response
   *   Asset data for a Model class.
   *
   * @return \Drupal\ib_dam\Asset\AssetInterface
   *   Returns an asset instance.
   */
  private function buildAsset(\stdClass $response) {
    $model = Model::buildModel($response);
    $mime  = $model->getMimetype();
    $model->setType(Downloader::getSourceTypeFromMime($mime));

    $asset = Asset::createFromSource($model, $this->currentUser->id());

    $source_type = $asset->getSourceType() == 'embed'
      ? 'embed'
      : $model->getType();

    $media_type_id = $this->mediaTypeMatcher->matchType($source_type, 'source_type');

    if (empty($media_type_id)) {
      $source = clone $asset->source();
      $source->removeAuthKey();

      (new MediaTypeMatcherBadMediaTypeMatch($source_type, $source))
        ->logException()
        ->displayMessage();

      return NULL;
    }
    else {
      $storage_type_id = implode(':', [
        MediaStorage::class,
        $source_type,
        $media_type_id,
      ]);
      $asset->setStorageType($storage_type_id);
    }

    return $asset;
  }

  /**
   * Helper function to iterate over response items and build asset instances.
   *
   * @param array $items
   *   An array of response objects.
   *
   * @return \Drupal\ib_dam\Asset\AssetInterface[]
   *   An array of asset objects.
   */
  private function prepareAssets(array $items = []) {
    $assets = [];
    foreach ($items as $item) {
      if ($asset = $this->buildAsset($item)) {
        $assets[] = $asset;
      }
    }
    return array_filter($assets);
  }

  /**
   * Fetch assets from the $form_state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   * @param bool $first
   *   Optional. Return only first asset, instead of returning array.
   *   Defaults to FALSE.
   *
   * @return \Drupal\ib_dam\Asset\AssetInterface[]|\Drupal\ib_dam\Asset\AssetInterface
   *   Assets array or the first asset.
   */
  private function getAssets(FormStateInterface $form_state, bool $first = FALSE) {
    $items = $form_state->getValue(['ib_dam_app_el', 'items'], []);
    $assets = $this->prepareAssets($items);
    return !$first ? $assets : reset($assets);
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array &$form, FormStateInterface $form_state) {
    // Prepare all required data.
    $step    = $form_state->get('step');
    $assets  = $form_state->get('assets') ?? $this->getAssets($form_state);
    $browser = $form_state->get('browser') ?? $form['widget']['ib_dam_app_el'];

    // Skip second step for non-embed assets.
    if (!reset($assets) instanceof EmbedAssetInterface) {
      $step = 'configure';
    }

    // For the process step just store required data and rebuild the form.
    if ($step === 'process') {
      // Update step.
      $form_state->set('step', 'configure');
      // Store data in order to make it available on the next step(s).
      $form_state->set('browser', $browser);
      $form_state->set('assets', $assets);
      // Rebuild the form.
      $form_state->setRebuild();
    }
    elseif ($step === 'configure') {
      $validators[] = [
        'id'         => 'file',
        'validators' => [
          'validateFileExtensions' => static::getWidgetSetting($form_state, 'configured_extensions'),
          'validateFileDirectory'  => $this->getUploadLocation(),
          'validateFileSize'       => FALSE,
        ],
      ];

      $validators[] = [
        'id'         => 'resource',
        'validators' => [
          'validateIsAllowedResourceType' => [
            'type'    => 'embed',
            'allowed' => static::getWidgetSetting($form_state, 'allow_embed'),
          ],
        ],
      ];

      $validators[] = [
        'id'         => 'api',
        'validators' => [
          'validateApiAuthKey'   => [],
        ],
      ];

      $this->validateAssets($validators, $assets, $form_state, $browser);

      if (empty($form_state->getErrors())) {
        $this->validateAndSaveAssets($assets, $browser, $form_state);
      }
    }
  }

  /**
   * Validate, save assets list.
   *
   * Run save process on a list of assets.
   *
   * @param \Drupal\ib_dam\Asset\AssetInterface[] $assets
   *   The list of assets to operate on them.
   * @param array &$element
   *   The reference to the ib_dam browser form element.
   *   Used to mark elements with errors.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state instance.
   *
   * @return bool
   *   The result of operation.
   */
  public function validateAndSaveAssets(array $assets, array &$element, FormStateInterface $form_state) {
    /* @var $assets \Drupal\ib_dam\Asset\AssetInterface[] */
    foreach ($assets as $asset) {
      if ($asset instanceof LocalAssetInterface) {
        try {
          $asset->saveAttachments($this->downloader, $this->getUploadLocation());
        }
        catch (AssetDownloaderBadResponse $e) {
          $e->logException()
            ->displayMessage();
        }
      }
      elseif ($asset instanceof EmbedAssetInterface) {
        // Apply overrides from form state to the asset.
        $asset->setUrl($form_state->getValue(['settings', 'remote_url']));

        $width  = $form_state->getValue(['settings', 'width']) ?? 0;
        $height = $form_state->getValue(['settings', 'height']) ?? 0;
        $alt    = $form_state->getValue(['settings', 'alt']);
        $title  = $form_state->getValue(['settings', 'title']);

        if (!empty($alt)) {
          $asset->setDescription($alt);
        }

        if (!empty($title)) {
          $asset->setName($title);
        }

        if ($width > 0 && $height > 0) {
          $asset->setDisplaySettings(['width' => $width, 'height' => $height]);
        }
      }

      if ($this->debug) {
        $params = clone $asset->source();
        $params->removeAuthKey();

        $this->logger->debug('Saving media, params: @args', [
          '@args' => print_r([
            'storage_type' => $asset->getStorageType(),
            'model' => $params,
          ], TRUE),
        ]);
      }

      $media = $asset->save();

      try {
        $media->save();
        $media_list[] = $media;
      }
      catch (\Error $e) {
        (new MediaStorageUnableSaveMediaItem($e->getMessage()))
          ->logException()
          ->displayMessage();
      }
    }

    if (empty($media_list)) {
      $form_state->setError($element, 'No media items was saved. See errors above.');
      return FALSE;
    }
    $form_state->setValue('ib_assets', $media_list);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array &$element, array &$form, FormStateInterface $form_state) {
    $this->selectEntities($form_state->getValue('ib_assets', []), $form_state);
    $this->clearFormValues($element, $form_state);
  }

  /**
   * Clear values from Iframe response form element.
   *
   * @param array $element
   *   Upload form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  protected function clearFormValues(array &$element, FormStateInterface $form_state) {
    if (isset($element['ib_dam_app_el'])) {
      $form_state->setValueForElement($element['ib_dam_app_el'], '');
      NestedArray::setValue($form_state->getUserInput(), $element['ib_dam_app_el']['#parents'], '');
    }
    $form_state->setValue('ib_assets', NULL);
  }

  /**
   * Return upload location for an assets.
   */
  protected function getUploadLocation() {
    return $this->token->replace($this->configuration['upload_location']);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    $dependencies['module'][] = 'media';
    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareEntities(array $form, FormStateInterface $form_state) {}

  /**
   * Helper method to show error when there are no available file extensions.
   */
  private function getMediaTypesError($display = FALSE) {
    // @todo: check configuration permission.
    $message = $this->t("Check media types mapping <a href=':link' target='_blank'>on configuration form</a> to upload different file types.", [
      ':link' => Url::fromRoute('ib_dam_media.configuration_form')->toString(),
    ]);

    if (!$display) {
      return $message;
    }

    $this->messenger()->addWarning($message);
    return NULL;
  }

  /**
   * Helper method to show error when there are no enabled target bundles.
   */
  private function showEmptyTargetBundlesError(array $field) {
    // @todo: add link to the field configuration page.
    $this->messenger()->addWarning(
      $this->t("You should allow at least one target bundle in %field field settings on %bundle in %entity_type", [
        '%field' => $field['name'],
        '%bundle' => $field['entity_bundle_id'],
        '%entity_type' => $field['entity_type_id'],
      ]));
  }

  /**
   * Helper function showing an entity embed error.
   *
   * We restrict for the moment using entity browser widget inside entity embed.
   */
  private function showEntityEmbedError() {
    $this->messenger()->addWarning(
      $this->t("At this point using IntelligenceBank Asset Browser inside Entity Embed is disallowed")
    );
  }

  /**
   * Extract given setting from widget_context.
   *
   * Widget context used to pass information about calling context.
   *
   * @see ib_dam_media_field_widget_entity_browser_entity_reference_form_alter()
   * @see ib_dam_media_form_entity_embed_dialog_alter()
   */
  private static function getWidgetSetting(FormStateInterface $form_state, $setting) {
    $parents = ['entity_browser', 'widget_context', 'ib_dam_media'];
    $parents[] = $setting;
    return $form_state->get($parents);
  }

  /**
   * Set setting in the widget_context.
   */
  private static function setWidgetSetting(FormStateInterface &$form_state, $setting, $value) {
    $parents = ['entity_browser', 'widget_context', 'ib_dam_media'];
    $parents[] = $setting;
    $form_state->set($parents, $value);
  }

  /**
   * Getter for trait validation functionality.
   *
   * @return \Drupal\ib_dam\AssetValidation\AssetValidationManager
   *   The AssetValidationManager instance.
   */
  protected function getAssetValidationManager() {
    return $this->assetValidationManager;
  }

}
