<?php

namespace Drupal\setka_editor\Plugin\Field\FieldWidget;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Database\Connection;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextareaWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\State\State;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\setka_editor\Controller\SetkaEditorApiController;
use Drupal\setka_editor\Form\SettingsForm;
use Drupal\setka_editor\SetkaEditorHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'setka_editor_string_textarea' widget.
 *
 * @FieldWidget(
 *   id = "setka_editor_string_textarea",
 *   label = @Translation("Setka Editor String Textarea"),
 *   field_types = {
 *     "string_long",
 *   }
 * )
 */
class SetkaEditorStringTextareaWidget extends StringTextareaWidget implements ContainerFactoryPluginInterface {

  /**
   * Setka Editor config.
   *
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  protected $setkaEditorConfig;

  /**
   * Service to interact with $_SESSION.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $sessionStore;

  /**
   * Drupal database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Setka Editor helper service.
   *
   * @var \Drupal\setka_editor\SetkaEditorHelper
   */
  protected $setkaEditorHelper;

  /**
   * Drupal current user.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Drupal state service.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public function __construct(string $plugin_id,
                              $plugin_definition,
                              FieldDefinitionInterface $field_definition,
                              array $settings,
                              array $third_party_settings,
                              ConfigFactory $configFactory,
                              PrivateTempStoreFactory $privateTempstore,
                              Connection $database,
                              SetkaEditorHelper $setkaEditorHelper,
                              AccountProxy $currentUser,
                              State $state) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->setkaEditorConfig = $configFactory->get('setka_editor.settings');
    $this->sessionStore = $privateTempstore->get('setka_editor');
    $this->database = $database;
    $this->setkaEditorHelper = $setkaEditorHelper;
    $this->currentUser = $currentUser;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('config.factory'),
      $container->get('user.private_tempstore'),
      $container->get('database'),
      $container->get('setka_editor.helper'),
      $container->get('current_user'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $parentWidget = parent::formElement($items, $delta, $element, $form, $form_state);

    $isSetkaEditorFormat = TRUE;
    $currentValue = ($items->getEntity()->id() > 0) ? $items[$delta]->value : '';
    $decoded = FALSE;
    if (!empty($currentValue)) {
      if ($decoded = Json::decode($currentValue)) {
        if (!$decoded['postTheme'] || !$decoded['postGrid'] || !$decoded['postHtml']) {
          $isSetkaEditorFormat = FALSE;
        }
        elseif (!empty($decoded['postUuid'])) {
          $entityUuid = $decoded['postUuid'];
        }
      }
      else {
        $isSetkaEditorFormat = FALSE;
      }
    }

    $setkaCompanyJson = SettingsForm::getConfigValue($this->setkaEditorConfig, $this->state, 'setka_company_json');
    $setkaCompanyCss = SettingsForm::getConfigValue($this->setkaEditorConfig, $this->state, 'setka_company_css');
    $setkaEditorPublicToken = $this->setkaEditorConfig->get('setka_editor_public_token');
    if ($isSetkaEditorFormat) {
      if (!empty($setkaCompanyJson) && !empty($setkaCompanyCss) && !empty($setkaEditorPublicToken)) {
        $setkaEditorInitError = $this->setkaEditorHelper->checkPostMeta($this->setkaEditorConfig->get('setka_company_meta_data'), $decoded['postTheme'], $decoded['postGrid']);
        if ($this->currentUser->hasPermission('access toolbar')) {
          $setkaEditorHeaderTopOffset = SetkaEditorHelper::SETKA_EDITOR_TOOLBAR_OFFSET;
        }
        else {
          $setkaEditorHeaderTopOffset = 0;
        }
        $formBuildInfo = $form_state->getBuildInfo();
        /** @var \Drupal\Core\Entity\EntityForm $callbackObject */
        $callbackObject = $formBuildInfo['callback_object'];
        /** @var \Drupal\Core\Entity\Entity $entity */
        $entity = $callbackObject->getEntity();
        $entityId = $entity->id();
        $entityType = $entity->getEntityType()->id();

        $element = $parentWidget['value'];
        $entityImages = [];
        if ($entityId && $entityType) {
          $query = $this->database->select('file_usage', 'u');
          $query->leftJoin('file_managed', 'f', 'u.fid = f.fid');
          $query->condition('u.id', $entityId);
          $query->condition('u.module', 'setka_editor');
          $query->condition('f.status', 1);
          $query->condition('f.filemime', SetkaEditorApiController::SETKA_ALLOWED_MIME_TYPES, 'IN');
          $query->fields('f', ['fid', 'filename', 'uri', 'alt']);
          $result = $query->execute()->fetchAll();
          foreach ($result as $imageItem) {
            $imageUrl = file_create_url($imageItem->uri);
            $entityImages[] = [
              'id' => $imageItem->fid,
              'name' => $imageItem->filename,
              'url' => $imageUrl,
              'thumbUrl' => $imageUrl,
              'alt' => $imageItem->alt ?? '',
            ];
          }
        }
        $entityUuid = $entityUuid ?? $entity->uuid();
        if (!$entityId) {
          $validUuids = $this->sessionStore->get('setka_editor_valid_uuids') ?? [];
          if (!in_array($entityUuid, $validUuids)) {
            $validUuids[] = $entityUuid;
          }
          $this->sessionStore->set('setka_editor_valid_uuids', $validUuids);
        }
        $element['#suffix'] = '<div id="setka-editor" class="stk-editor"></div>';
        $element['#attached'] = [
          'library' => [
            'setka_editor/setka-editor',
            'setka_editor/setka-styles',
            'setka_editor/setka-widget',
          ],
          'drupalSettings' => [
            'setkaEditorMetaFile' => $setkaCompanyJson,
            'setkaEditorPublicToken' => $setkaEditorPublicToken,
            'setkaEditorEntityId' => $entityId,
            'setkaEditorEntityType' => $entityType,
            'setkaEditorEntityUuid' => $entityUuid,
            'setkaEditorEntityImages' => $entityImages,
            'setkaEditorInitError' => $setkaEditorInitError,
            'setkaEditorHeaderTopOffset' => $setkaEditorHeaderTopOffset,
            'setkaEditorUploadMaxSize' => SetkaEditorHelper::getUploadMaxSize(),
          ],
        ];
        $element['#attributes']['style'] = 'display:none;';
        $element['#attributes']['setka-editor'] = 'true';
        $element['#element_validate'][] = [
          'Drupal\setka_editor\Validate\SetkaEditorValidate',
          'validate',
        ];
        $result = ['value' => $element];
      }
      else {
        $this->messenger()->addWarning(
          $this->t('Setka Editor initialization error. Please check your license key.')
        );
        $result = $parentWidget;
      }
    }
    else {
      $this->messenger()->addWarning(
        $this->t('You can not use Setka Editor here because this content was generated in another editor.')
      );
      $result = $parentWidget;
    }

    $result['#setka_editor_widget'] = TRUE;
    return $result;
  }

}
