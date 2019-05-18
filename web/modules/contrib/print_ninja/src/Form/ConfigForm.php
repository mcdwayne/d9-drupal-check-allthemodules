<?php
/**
 * Created by PhpStorm.
 * User: dev
 * Date: 20.03.18
 * Time: 17:18
 */

namespace Drupal\print_ninja\Form;

use Drupal\Core\Config\Config;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\field\FieldConfigInterface;
use Drupal\file\Entity\File;
use Drupal\node\Entity\NodeType;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ConfigForm extends ConfigFormBase {

  const PARENT_TYPE = 'pn_parent_content_type';

  const PARENT_IMAGE_FIELD = 'pn_parent_image_field';

  const PARENT_DOCUMENTS_FIELD = 'pn_parent_documents_field';

  const CHILD_TYPE = 'pn_child_content_type';

  const CHILD_TEXT_FIELD = 'pn_child_text_field';

  const CHILD_DOCUMENTS_FIELD = 'pn_child_docs_field';

  const SELECTED_PARENT_FIELDS_FOR_EXPORT = 'pn_selected_parent_fields_for_export';

  const SELECTED_CHILD_FIELDS_FOR_EXPORT = 'pn_selected_child_fields_for_export';

  const EDITABLE_CONFIG_NAME = 'print_ninja.settings';

  const USE_LOGO = 'use_logo';

  const LOGO_IMAGE = 'logo_image';

  const CSS_FILE = 'css_file';

  const USE_CSS_FILE = 'use_css_file';

  public static $_instance;

  /* @var $messageService Messenger */
  private $messageService;

  /* @var $config Config */
  private $config;


  public function getFormId() {
    if (!$this->messageService) {
      $this->messageService = \Drupal::service('messenger');
    }
    if (!$this->config) {
      $this->config = $this->configFactory->getEditable($this->getEditableConfigNames()[0]);
    }
    if (!self::$_instance) {
      self::$_instance = $this;
    }

    return 'print_ninja_config';
  }

  protected function getEditableConfigNames() {
    return [self::EDITABLE_CONFIG_NAME];
  }

  /**
   * Form, which contains the settings about the parent and child content
   * types, which are desired to be exported as PDF file
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (!$this->config) {
      $this->config = self::getInstance()->config;
    }

    // get all parent content types
    $nodeTypes = NodeType::loadMultiple();
    $optionTypes = [];
    foreach ($nodeTypes as $bundle => $nodeType) {
      /* @var $nodeType NodeType */
      $optionTypes[$bundle] = $nodeType->get('name');
    }

    $savedContentType = $this->config->get(self::PARENT_TYPE);
    if (empty($savedContentType)) {
      $savedContentType = array_keys($optionTypes)[0];
    }

    $listChildren = $this->getFieldsReferenceForDropdown($savedContentType);
    $form['parent'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Parent content type settings'),
      self::PARENT_TYPE => [
        '#type' => 'select',
        '#required' => TRUE,
        '#title' => $this->t('Enter machine name of the parent content type'),
        '#options' => $optionTypes,
        '#default_value' => $savedContentType,
        '#validated' => TRUE,
        '#ajax' => [
          'callback' => '::changeParentContentType',
          'wrapper' => 'settings-changable',
          'event' => 'change',
        ],
      ],
      'settings_wrapper' => [
        '#type' => 'fieldset',
        '#title' => $this->t('Settings'),
        '#prefix' => '<div id="settings-changable">',
        '#suffix' => '</div>',

        // here will be the listing for the parent fields as a checkboxes
        self::SELECTED_PARENT_FIELDS_FOR_EXPORT => [
          '#type' => 'fieldset',
          '#title' => $this->t('Select which fields to export in the PDF file from "PARENT CONTENT TYPE"'),
          '#prefix' => '<div id="parent-fields-selector">',
          '#suffix' => '</div>',
          'fields' => $this->getFieldsForSelectedContentType($savedContentType, FALSE),
        ],

        // child field selector
        self::CHILD_TYPE => [
          self::CHILD_TYPE . '_select' => [
            '#title' => $this->t('Select field, which contains reference to a child content type'),
            '#type' => 'select',
            '#validated' => TRUE,
            '#required' => FALSE,
            '#options' => $listChildren,
            '#default_value' => $this->config->get(self::CHILD_TYPE),
            '#ajax' => [
              'callback' => '::changeChildContentType',
              'wrapper' => 'child-field-list',
              'event' => 'change',
            ],
            '#attributes' => [
              'class' => !empty($listChildren) ? [] : ['hide-element'],
            ],
          ],
          'message' => [
            '#access' => empty($listChildren),
            '#markup' => $this->t('<h5>There is no fields, which are entity reference in the parent content type!</h5>'),
          ],
        ],
        self::SELECTED_CHILD_FIELDS_FOR_EXPORT => [
          '#type' => 'fieldset',
          '#title' => $this->t('SELECT WHICH FIELDS TO EXPORT IN THE PDF FILE FROM "CHILD CONTENT TYPE"'),
          '#prefix' => '<div id="child-field-list">',
          '#suffix' => '<div/>',
          'fields' => $this->getFieldsForSelectedChildContentType($savedContentType, $this->config->get(self::CHILD_TYPE)),
        ],
      ],
    ];

    $form[self::USE_LOGO] = [
      '#type' => 'checkbox',
      '#title' => t('Use logo on printing'),
      '#description' => t('Logo will be visible on pages, which are not file fields export'),
      '#default_value' => $this->config->get(self::USE_LOGO),
    ];
    $imageValue = $this->config->get(self::LOGO_IMAGE . '.0');
    $form[self::LOGO_IMAGE] = [
      '#type' => 'managed_file',
      '#title' => t('Logo image'),
      '#description' => t('Allowed file types: jpg png jpeg.'),
      '#default_value' => $imageValue ? [$imageValue] : NULL,
      '#upload_location' => 'public://print_ninja/images',
      '#upload_validators' => [
        'file_validate_extensions' => ['jpg png jpeg'],
      ],
    ];
    $form[self::USE_CSS_FILE] = [
      '#type' => 'checkbox',
      '#title' => t('Use custom css file'),
    ];
    $form[self::CSS_FILE] = [
      '#type' => 'file',
      '#title' => t('Css file'),
    ];
    $form['#attached']['library'][] = 'print_ninja/print-library';

    return parent::buildForm($form, $form_state);
  }

  /**
   * @return self
   */
  public static function getInstance() {
    return self::$_instance;
  }

  private function getFieldsReferenceForDropdown($type) {
    $entityManager = \Drupal::service('entity_field.manager');
    $fields = [];
    foreach ($entityManager->getFieldDefinitions('node', $type) as $fieldDefinition) {
      if ($fieldDefinition instanceof FieldConfigInterface
        && $fieldDefinition->getType() == 'entity_reference'
        && $fieldDefinition->getSettings()['target_type'] != 'taxonomy_term') {
        $fields[$fieldDefinition->getName()] = $fieldDefinition->getLabel();
      }
    }

    return $fields;
  }

  private function getFieldsForSelectedContentType($type, $withBundleName = TRUE, $buildRenderArray = TRUE, $toParentFields = TRUE) {
    /* @var $entityManager \Drupal\Core\Entity\EntityFieldManager */
    $entityManager = \Drupal::service('entity_field.manager');
    $fields = [];
    if (!$this->config) {
      $this->config = self::getInstance()->config;
    }
    $fieldNameKey = self::SELECTED_CHILD_FIELDS_FOR_EXPORT;
    if ($toParentFields) {
      $fieldNameKey = self::SELECTED_PARENT_FIELDS_FOR_EXPORT;
    }
    $possibleFields = $entityManager->getFieldDefinitions('node', $type);
    foreach ($possibleFields as $fieldDefinition) {
      if ($buildRenderArray) {
        if ($fieldDefinition instanceof FieldConfigInterface && $this->isFieldTypeAllowed($fieldDefinition->getType())) {
          $name = $fieldDefinition->getName();
          $savedConfigData = $this->config->get($fieldNameKey)[$fieldDefinition->get('bundle')];
          $fields[$fieldDefinition->get('bundle') . '_' . $name] = [
            '#type' => 'checkbox',
            '#title' => ($withBundleName === TRUE) ? $fieldDefinition->get('bundle') . '->' . $fieldDefinition->label() : $fieldDefinition->label(),
            '#default_value' => is_array($savedConfigData) && array_key_exists($name, $savedConfigData),
            '#attributes' => [
              'name' => $fieldNameKey . "[" . $fieldDefinition->get('bundle') . "][" . $name . "]",
            ],
          ];
        }
      }
    }

    return $fields;
  }

  /**
   * @param $type
   *
   * @return bool
   */
  private function isFieldTypeAllowed($type) {
    $allowedTypes = [
      'image',
      'file',
      'string',
      'string_long',
      'text',
      'text_long',
      'text_with_summary',
      'decimal',
      'float',
      'integer',
      'email',
      'link',
      'address',
      'address_country',
      'datetime',
    ];

    return in_array($type, $allowedTypes);
  }

  private function getFieldsForSelectedChildContentType($selectedParentContentType, $selectedChildContentType) {
    $fields = [];
    $entityManager = \Drupal::service('entity_field.manager');
    $childReferenceConfig = $entityManager->getFieldDefinitions('node', $selectedParentContentType)[$selectedChildContentType];
    if ($childReferenceConfig) {
      $childReferenceTypes = $childReferenceConfig->getSetting('handler_settings')['target_bundles'];
      foreach ($childReferenceTypes as $childReferenceType) {
        $fields += $this->getFieldsForSelectedContentType($childReferenceType, TRUE, TRUE, FALSE);
      }
    }

    return $fields;
  }

  /**
   * Saves the settings for desired parent and child content types
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $userInput = $form_state->getUserInput();
    if (!$this->config) {
      $this->config = self::getInstance()->config;
    }
    $contentTypes = array_keys(node_type_get_names());
    if (!in_array($userInput[self::PARENT_TYPE], $contentTypes)) {
      $this->messageService->addMessage('Not a valid parent content type', MessengerInterface::TYPE_ERROR);
    }
    else {
      /* @var $files \Symfony\Component\HttpFoundation\FileBag */
      $files = \Drupal::request()->files->get('files');
      if ($files['css_file'] && $files['css_file'] instanceof UploadedFile) {
        // css file is uploaded
        $cssFile = $files['css_file'];
        /* @var $cssFile UploadedFile */
        if ($cssFile->getClientMimeType() == 'text/css') {
          $fileName = basename($cssFile->getClientOriginalName());
          $path = __DIR__ . '/../../css/';
          if ($cssFile->move($path, $fileName)) {
            $this->config->set(self::CSS_FILE, $fileName);
          }
          else {
            $this->config->clear(self::CSS_FILE);
          }
        }
      }
      else {
        $this->config->clear(self::CSS_FILE);
      }

      $fileIdLogo = $form_state->getValue([self::LOGO_IMAGE, 0]);
      $file = File::load($fileIdLogo);
      $oldFileIDLogo = $this->config->get(self::LOGO_IMAGE . '.0');
      /* @var $fileService \Drupal\file\FileUsage\DatabaseFileUsageBackend */
      $fileService = \Drupal::service('file.usage');
      if ($file && $fileIdLogo != $oldFileIDLogo) {
        // New file is uploaded, and its different from the old one.
        $fileService->add($file, 'print_ninja', self::LOGO_IMAGE, $file->uuid());
        $file->setPermanent();
        $file->save();
        if ($oldFileIDLogo) {
          $oldFile = File::load($oldFileIDLogo);
          $fileService->delete($oldFile, 'print_ninja', self::LOGO_IMAGE, $oldFile->uuid());
          $oldFile->delete();
        }
      }
      if (!$file && $oldFileIDLogo) {
        // No new file, but have old one, delete the old one.
        $oldFile = File::load($oldFileIDLogo);
        $fileService->delete($oldFile, 'print_ninja', self::LOGO_IMAGE, $oldFile->uuid());
        $oldFile->delete();
      }

      $this->config->set(self::LOGO_IMAGE, $form_state->getValue(self::LOGO_IMAGE));

      $this->config->set(self::PARENT_TYPE, $userInput[self::PARENT_TYPE]);
      $this->config->set(self::CHILD_TYPE, $userInput[self::CHILD_TYPE . '_select']);
      $this->config->set(self::SELECTED_PARENT_FIELDS_FOR_EXPORT, $userInput[self::SELECTED_PARENT_FIELDS_FOR_EXPORT]);
      $this->config->set(self::USE_LOGO, $userInput[self::USE_LOGO]);
      $this->config->set(self::SELECTED_CHILD_FIELDS_FOR_EXPORT, $userInput[self::SELECTED_CHILD_FIELDS_FOR_EXPORT]);
      $fields = array_keys($this->getFieldsForSelectedContentType($userInput[self::PARENT_TYPE]));
      foreach ($fields as $field) {
        if (array_key_exists($field, $userInput) && !empty($userInput[$field])) {
          $this->config->set($field, $userInput[$field]);
        }
        else {
          $this->config->clear($field);
        }
      }
      $this->config->save();
      parent::submitForm($form, $form_state);
    }
  }

  public function changeParentContentType(&$form, FormStateInterface $form_state) {
    $userInput = $form_state->getUserInput();
    $selectedParentContentType = $userInput[self::PARENT_TYPE];
    $parentFields = $this->getFieldsForSelectedContentType($selectedParentContentType, FALSE);
    $listChildren = $this->getFieldsReferenceForDropdown($selectedParentContentType);
    $form['parent']['settings_wrapper'][self::SELECTED_PARENT_FIELDS_FOR_EXPORT]['fields'] = $parentFields;
    $form['parent']['settings_wrapper'][self::CHILD_TYPE][self::CHILD_TYPE . '_select']['#options'] = $listChildren;
    $form_state->setValueForElement($form['parent']['settings_wrapper'][self::CHILD_TYPE][self::CHILD_TYPE . '_select'], $listChildren);
    if (!empty($listChildren)) {
      // get allowed fields for the selected referenced field
      $selectedChildContentType = array_keys($listChildren)[0];
      $form['parent']['settings_wrapper'][self::CHILD_TYPE][self::CHILD_TYPE . '_select']['#access'] = TRUE;
      $form['parent']['settings_wrapper'][self::CHILD_TYPE][self::CHILD_TYPE . '_select']['#attributes'] = [
        'class' => [],
      ];

      $form['parent']['settings_wrapper'][self::CHILD_TYPE][self::CHILD_TYPE . '_select']['#value'] = $selectedChildContentType;
      $form['parent']['settings_wrapper'][self::CHILD_TYPE]['message']['#access'] = FALSE;
      $fields = $this->getFieldsForSelectedChildContentType($selectedParentContentType, $selectedChildContentType);
      $form['parent']['settings_wrapper'][self::SELECTED_CHILD_FIELDS_FOR_EXPORT]['fields'] = $fields;
    }
    else {
      // there is no fields, which are entity references, so hide the dropdown and show message instead
      $form['parent']['settings_wrapper'][self::CHILD_TYPE][self::CHILD_TYPE . '_select']['#access'] = FALSE;
      $form['parent']['settings_wrapper'][self::CHILD_TYPE]['message']['#access'] = TRUE;
      $form['parent']['settings_wrapper'][self::SELECTED_CHILD_FIELDS_FOR_EXPORT]['fields'] = [];
    }

    return $form['parent']['settings_wrapper'];
  }

  public function changeChildContentType(&$form, FormStateInterface $form_state) {
    $fields = $this->getFieldsForSelectedChildContentType($form_state->getValue(self::PARENT_TYPE), $form_state->getValue(self::CHILD_TYPE . '_select'));
    $form['parent']['settings_wrapper'][self::SELECTED_CHILD_FIELDS_FOR_EXPORT]['fields'] = $fields;
    return $form['parent']['settings_wrapper'][self::SELECTED_CHILD_FIELDS_FOR_EXPORT];
  }
}
