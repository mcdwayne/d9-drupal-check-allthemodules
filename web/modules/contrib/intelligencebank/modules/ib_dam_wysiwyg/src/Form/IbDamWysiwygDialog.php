<?php

namespace Drupal\ib_dam_wysiwyg\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\Token;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\ib_dam\Asset\Asset;
use Drupal\ib_dam\Asset\AssetInterface;
use Drupal\ib_dam\Asset\EmbedAssetInterface;
use Drupal\ib_dam\Asset\LocalAssetInterface;
use Drupal\ib_dam\AssetFormatter\AssetFormatterManager;
use Drupal\ib_dam\AssetValidation\AssetValidationManager;
use Drupal\ib_dam\AssetValidation\AssetValidationTrait;
use Drupal\ib_dam\Downloader;
use Drupal\ib_dam\Exceptions\AssetDownloaderBadResponse;
use Drupal\ib_dam\IbDamResourceModel as Model;
use Drupal\ib_dam_wysiwyg\AssetStorage\TextFilterStorage;
use Drupal\image\Entity\ImageStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class IbDamWysiwygDialog.
 *
 * Used to build form for wysiwyg dialog to navigate assets,
 * and set asset display settings.
 *
 * @package Drupal\ib_dam_wysiwyg\Form
 */
class IbDamWysiwygDialog extends FormBase {

  use AssetValidationTrait;

  const ADMIN_PERMISSION = 'administer intelligencebank configuration';

  protected $downloader;
  protected $token;
  protected $logger;
  protected $assetValidationManager;
  protected $currentUser;
  protected $debug;

  /**
   * IbDamWysiwygDialog constructor.
   */
  public function __construct(
    AccountInterface $current_user,
    Token $token,
    LoggerChannelFactoryInterface $logger_factory,
    AssetValidationManager $asset_validation_manager,
    Downloader $downloader,
    ConfigFactoryInterface $config_factory
  ) {
    $this->currentUser = $current_user;
    $this->token = $token;
    $this->logger = $logger_factory->get('ib_dam');
    $this->assetValidationManager = $asset_validation_manager;
    $this->downloader = $downloader;

    $debug_mode  = (boolean) $config_factory->get('ib_dam.settings')->get('debug');
    $has_rights  = $this->currentUser->hasPermission(self::ADMIN_PERMISSION);
    $this->debug = $debug_mode && $has_rights ?: FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('token'),
      $container->get('logger.factory'),
      $container->get('plugin.manager.ib_dam.asset_validation'),
      $container->get('ib_dam.downloader'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'id_dam_wysiwyg_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, FilterFormat $filter_format = NULL) {
    $step = $form_state->get('step') ?: 'process';

    $form_state
      ->set('filter_format', $filter_format)
      ->setCached(FALSE);

    $form['#prefix'] = '<div id="ib-dam-wysiwyg-dialog-form">';
    $form['#suffix'] = '</div>';
    $form['#attached']['library'][] = 'editor/drupal.editor.dialog';

    $form['status_messages'] = [
      '#type' => 'status_messages',
      '#weight' => -10,
    ];

    if ($step === 'process') {
      $this->buildProcessStep($form, $form_state);
    }
    elseif ($step == 'configure') {
      $this->buildConfigureStep($form, $form_state);
    }

    $form['buttons']['save'] = [
      '#type' => 'submit',
      '#weight' => 16,
      '#value' => $this->t('Save'),
      '#submit' => [],
      '#attributes' => ['class' => ['ib-dam-browser-submit']],
      '#ajax' => [
        'callback' => '::ajaxSubmit',
        'event' => 'click',
        'wrapper' => 'ib-dam-wysiwyg-dialog-form',
      ],
    ];
    return $form;
  }

  /**
   * An AJAX submit callback to validate the WYSIWYG modal.
   */
  public function ajaxSubmit(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // No errors and we are on the last step.
    if (!$form_state->getErrors() && $form_state->get('step') === 'submit') {
      /* @var $asset  \Drupal\ib_dam\Asset\AssetInterface */
      /* @var $format \Drupal\filter\Entity\FilterFormat */
      $asset  = $form_state->get('ib_asset');
      $format = $form_state->get('filter_format');

      // Store possibly customized version of remote URL within the asset.
      if ($asset instanceof EmbedAssetInterface) {
        $remote_url = $form_state->getValue(['settings', 'remote_url']);
        if (!empty($remote_url)) {
          $asset->setUrl(urldecode($remote_url));
        }
      }

      $data = $this->createStorageItem($asset, $format);

      if (!$data) {
        $data['errors'] = implode('<br>',
          $this->messenger()->messagesByType(MessengerInterface::TYPE_ERROR)
        );
        $this->messenger()->deleteAll();
      }
      else {
        $data['display_settings'] = $form_state->getValue('settings');

        if (!empty($data['display_settings']['image_style'])) {
          $this->buildImagePreviewData($data, $asset);
        }
      }

      $response->addCommand(new EditorDialogSave($data));
      $response->addCommand(new CloseModalDialogCommand());
    }
    else {
      unset($form['#prefix'], $form['#suffix']);
      $response->addCommand(new HtmlCommand(NULL, $form));
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $step = $form_state->get('step');

    if ($step === 'process') {
      $asset_items = $form_state->getValue(['ib_dam_app_el', 'items'], []);
      // Allow only one asset per one WYSIWYG call, unlike in media integration.
      $response = is_array($asset_items)
        ? $asset_items[0]
        : $asset_items;

      $asset = $this->buildAsset($response);
      $this->validateProcessStep($asset, $form['ib_dam_app_el'], $form_state);

      // Move forward when no errors.
      if (empty($form_state->getErrors())) {
        $form_state->set('step', 'configure');
        $form_state->set('ib_asset', $asset);
      }
    }
    elseif ($step === 'configure') {
      // @todo: do we need to validate asset display settings form?
      $form_state->set('step', 'submit');
    }
    $form_state->setRebuild();

    if (!empty($form_state->getErrors())) {
      parent::validateForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   *
   * We don't need submit function, we do all the stuff in ajax callback.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

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
    // @todo: make Model class as typed data item.
    $model = Model::buildModel($response);
    $mime  = $model->getMimetype();
    $model->setType(Downloader::getSourceTypeFromMime($mime));

    $asset = Asset::createFromSource($model, $this->currentUser->id(), FALSE);

    $storage_type_id = implode(':', [
      TextFilterStorage::class,
      $asset->getSourceType(),
      $asset->getType(),
    ]);

    $asset->setStorageType($storage_type_id);
    return $asset;
  }

  /**
   * Create asset text representation for editor and text filter.
   *
   * @param \Drupal\ib_dam\Asset\AssetInterface $asset
   *   The asset.
   * @param \Drupal\filter\Entity\FilterFormat $format
   *   The filter format instance.
   *
   * @return mixed
   *   Ready to save or process asset storage item.
   */
  private function createStorageItem(AssetInterface $asset, FilterFormat $format) {
    $settings = $this->getTextFilterSettings($format);

    if ($this->debug) {
      $params = clone $asset->source();
      $params->removeAuthKey();

      $this->logger->debug('Saving media, params: @args', [
        '@args' => print_r(['model' => $params], TRUE),
      ]);
    }

    if ($asset instanceof LocalAssetInterface) {
      try {
        $asset->saveAttachments($this->downloader, $settings['upload_location']);
      }
      catch (AssetDownloaderBadResponse $e) {
        $e->logException()
          ->displayMessage();
      }

      if ($asset->localFile()->getFileUri()) {
        $asset->localFile()->setPermanent();
      }
    }

    return $asset->save();
  }

  /**
   * Helper function to validate "process" step.
   */
  private function validateProcessStep(AssetInterface $asset, &$element, FormStateInterface $form_state) {
    $filter_format = $form_state->get('filter_format');
    $settings = $this->getTextFilterSettings($filter_format);

    $validators[] = [
      'id' => 'file',
      'validators' => [
        'validateFileExtensions' => $settings['file_extensions'],
        'validateFileSize' => FALSE,
        'validateFileDirectory' => $settings['upload_location'],
      ],
    ];

    $validators[] = [
      'id' => 'resource',
      'validators' => [
        'validateIsAllowedResourceType' => [
          'type' => 'embed',
          'allowed' => $settings['allow_embed'],
        ],
      ],
    ];

    $validators[] = [
      'id' => 'api',
      'validators' => [
        'validateApiAuthKey'   => [],
      ],
    ];

    $this->validateAssets($validators, [$asset], $form_state, $element);
  }

  /**
   * {@inheritdoc}
   */
  protected function getAssetValidationManager() {
    return $this->assetValidationManager;
  }

  /**
   * Helper function to build "process" step.
   */
  private function buildProcessStep(&$form, FormStateInterface $form_state) {
    $filter_format = $form_state->get('filter_format');
    $settings      = $this->getTextFilterSettings($filter_format);

    $extensions_message = $this->t("<p>Please, configure allowed file types on field configuration pages.</p>Allowed file extensions to download:<br>@types", [
      '@types' => $settings['file_extensions'],
    ])->render();

    $form['ib_dam_app_el'] = [
      '#type' => 'ib_dam_app',
      '#weight' => -5,
      '#file_extensions' => explode(' ', $settings['file_extensions']),
      '#allow_embed' => isset($settings['allow_embed']) ? $settings['allow_embed'] : FALSE,
      '#submit_selector' => '.ib-dam-browser-submit',
      '#debug_response' => $this->debug,
      '#messages' => [
        [
          'id' => 'local',
          'once' => TRUE,
          'text' => $extensions_message,
          'title' => $this->t("This file isn't allowed to download."),
        ],
        [
          'id' => 'embed',
          'once' => TRUE,
          'text' => '',
          'title' => $this->t("You're not allowed to embed files."),
        ],
      ],
    ];
    $form_state->set('step', 'process');
  }

  /**
   * Helper function to build "configure" step.
   */
  private function buildConfigureStep(&$form, FormStateInterface $form_state) {
    /* @var $asset \Drupal\ib_dam\Asset\AssetInterface */
    $asset = $form_state->get('ib_asset');
    $formatter = AssetFormatterManager::create($asset);

    $form['settings'] = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#title' => $this->t('Settings'),
    ];
    $form['settings'] += $formatter->settingsForm($asset);
  }

  /**
   * Get text filter settings from editor.
   */
  private function getTextFilterSettings(FilterFormat $filter_format) {
    $editor = Editor::load($filter_format->id());
    $editor_settings = $editor->getSettings();

    $plugin_settings = NestedArray::getValue($editor_settings, [
      'plugins',
      'ib_dam_browser',
    ]);
    return $plugin_settings ? $plugin_settings : [];
  }

  /**
   * Build asset display options for image asset type.
   *
   * Force image style file to create to grab image dimensions.
   */
  private function buildImagePreviewData(array &$data, LocalAssetInterface $asset) {
    $display  =& $data['display_settings'];

    $image_uri = $asset->localFile()->getFileUri();
    $style     = ImageStyle::load($display['image_style']);

    $file_url  = $style->buildUrl($image_uri);
    $file_uri  = $style->buildUri($image_uri);
    $status    = $style->createDerivative($image_uri, $file_uri);

    list($width, $height) = getimagesize($file_uri);

    if ($status && !empty($width) && !empty($height)) {
      $display['width'] = $width;
      $display['height'] = $height;
    }
    $data['preview_uri'] = file_url_transform_relative($file_url);
  }

}
