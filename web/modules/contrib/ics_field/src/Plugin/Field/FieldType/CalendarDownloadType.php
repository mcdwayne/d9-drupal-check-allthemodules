<?php

namespace Drupal\ics_field\Plugin\Field\FieldType;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TraversableTypedDataInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\Utility\Token;
use Drupal\file\FileUsage\FileUsageInterface;
use Drupal\file\Entity\File;
use Drupal\ics_field\IcsFileManager;

/**
 * Plugin implementation of the 'calendar_download_type' field type.
 *
 * @FieldType(
 *   id = "calendar_download_type",
 *   label = @Translation("Calendar download"),
 *   category = @Translation("Media"),
 *   description = @Translation("Provides a dynamically generated .ics file
 *   download"), default_widget = "calendar_download_default_widget",
 *   default_formatter = "calendar_download_default_formatter"
 * )
 */
class CalendarDownloadType extends FieldItemBase {

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $tokenService;

  /**
   * The file.usage service.
   *
   * @var \Drupal\file\FileUsage\FileUsageInterface
   */
  protected $fileUsageService;

  /**
   * The ics_field.file_manager service.
   *
   * @var \Drupal\ics_field\IcsFileManager
   */
  protected $icsFileManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(DataDefinitionInterface $definition,
                              $name = NULL,
                              TypedDataInterface $parent = NULL,
                              Token $tokenService,
                              FileUsageInterface $fileUsageService,
                              IcsFileManager $icsFileManager) {
    parent::__construct($definition, $name, $parent);
    $this->tokenService = $tokenService;
    $this->fileUsageService = $fileUsageService;
    $this->icsFileManager = $icsFileManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance($definition,
                                        $name = NULL,
                                        TraversableTypedDataInterface $parent = NULL) {
    return new static(
      $definition,
      $name,
      $parent,
      \Drupal::token(),
      \Drupal::service('file.usage'),
      \Drupal::service('ics_field.file_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'is_ascii'             => FALSE,
      'uri_scheme'           => 'public',
      'file_directory'       => 'icsfiles',
      'date_field_reference' => NULL,
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $fieldDefinition) {
    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['summary'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Summary'))
      ->setRequired(TRUE);
    $properties['description'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Description'))
      ->setRequired(TRUE);
    $properties['url'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('URL'));
    $properties['fileref'] = DataDefinition::create('string')
      ->setComputed(TRUE)
      ->setLabel(new TranslatableMarkup('ics File reference'));

    return $properties;
  }

  /**
   * Execute actions before the entity containing the field is saved.
   *
   * We use this to create a new managed ics file, and
   * saving the file reference to the generated file.
   */
  public function preSave() {
    // The current fielditem belongs to a fielditemlist,
    // that in turn belongs to a fieldable entity.
    $entity = $this->getParent()->getParent()->getValue();
    if ($entity->isNew()) {
      // We can't directly create the file, if this is a new entity, since some
      // token values, that might be used in the field settings, such as entity
      // url, rely on the entity id and are thus only available after the entity
      // has been saved. Thus we'll create an empty file now so that we can save
      // the file id and update the file with the correct content during
      // postSave().
      $fileref = $this->icsFileManager->createIcalFile($entity, $this->getFieldDefinition(), $this->getValue());
    }
    else {
      $fileref = $this->icsFileManager->updateIcalFile($entity, $this->getFieldDefinition(), $this->getValue());
    }
    $this->set('fileref', $fileref);
    parent::preSave();
  }

  /**
   * Execute actions after the entity containing the field is saved.
   *
   * We use this to create a new entry in the file_usage table,
   * linking the new entity with the generated managed ics file.
   *
   * @param bool $update
   *   A flag showing if this is an entity create or update.
   */
  public function postSave($update) {
    if (!$update) {
      // The current fielditem belongs to a fielditemlist,
      // that in turn belongs to a fieldable entity.
      $entity = $this->getParent()->getParent()->getValue();
      $this->icsFileManager->updateIcalFile($entity, $this->getFieldDefinition(), $this->getValue());
      $file = File::load($this->get('fileref')->getValue());
      $this->fileUsageService->add($file, 'ics_field', 'node', $entity->id());
    }
    parent::postSave($update);
  }

  /**
   * Execute actions after the entity containing the field is saved.
   *
   * We use this to remove an entry from the file_usage table.
   * This should cause the file to be deleted during the next cron run,
   * taking system.file.yml:temporary_maximum_age into account.
   */
  public function delete() {
    // The current fielditem belongs to a fielditemlist,
    // that in turn belongs to a fieldable entity.
    $entity = $this->getParent()->getParent()->getValue();
    $file = File::load($this->get('fileref')->getValue());
    $this->fileUsageService->delete($file, 'ics_field', 'node', $entity->id());
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function schema(FieldStorageDefinitionInterface $fieldDefinition) {
    $targetTypeInfo = \Drupal::entityTypeManager()->getDefinition('file');
    $schema = [
      'columns' => [
        'summary'     => [
          'description' => 'The SUMMARY field of a VEVENT.',
          'type'        => 'varchar',
          'length'      => 255,
          'not null'    => FALSE,
        ],
        'description' => [
          'description' => 'The DESCRIPTION field of a VEVENT.',
          'type'        => 'text',
          'size'        => 'big',
          'not null'    => FALSE,
        ],
        'url'         => [
          'description' => 'The URL field of a VEVENT.',
          'type'        => 'varchar',
          'length'      => 255,
          'not null'    => FALSE,
        ],
        'fileref'     => [
          'description' => 'The ID of the target ics file entity.',
          'type'        => 'varchar_ascii',
          // If the target entities act as bundles for another entity type,
          // their IDs should not exceed the maximum length for bundles.
          'length'      => $targetTypeInfo->getBundleOf() ?
          EntityTypeInterface::BUNDLE_MAX_LENGTH : 255,
        ],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form,
                                      FormStateInterface $formState,
                                      $hasData) {
    $elements = [];
    $fieldDefinitions = $this->getEntity()->getFieldDefinitions();
    $dateFields = [];
    foreach ($fieldDefinitions as $fieldName => $fieldDefinition) {
      if ($fieldDefinition->getType() === 'datetime') {
        $dateFields[$fieldName] = $fieldDefinition->getLabel();
      }
    }
    $elements['date_field_reference'] = [
      '#type'          => 'select',
      '#options'       => $dateFields,
      '#title'         => $this->t('Date field'),
      '#required'      => TRUE,
      '#empty_option'  => $this->t('- Select -'),
      '#default_value' => $this->getSetting('date_field_reference') ?: '',
      '#description'   => $this->t('Select the date field that will define when the calendar\'s events take place.'),
    ];

    $elements['file_directory'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('File directory'),
      '#description'   => 'Optional subdirectory within the upload destination where files will be stored. Do not include preceding or trailing slashes. This field supports tokens.',
      '#default_value' => $this->getSetting('file_directory') ?: 'icsfiles',
    ];

    $form['#validate'][] = [$this, 'checkWriteableDirectory'];

    return $elements;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   * @throws \InvalidArgumentException
   */
  public function isEmpty() {
    $value = $this->get('summary')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * A function that checks if the default directory for ics files is writable.
   *
   * @param array $element
   *   The field element.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state of the form the field element is part of.
   */
  public function checkWriteableDirectory(array $element,
                                          FormStateInterface $formState) {
    $uriScheme = $this->getSetting('uri_scheme');
    $fileDirectory = $formState->getValue(['settings', 'file_directory']);
    $uploadLocation = $this->tokenService->replace($uriScheme . '://' .
                                                   $fileDirectory);

    if (!file_prepare_directory($uploadLocation, FILE_CREATE_DIRECTORY)) {
      $formState->setError($element,
                           $this->t('Cannot create folder for ics files [@upload_location]',
                                    ['@upload_location' => $uploadLocation]));
    }
  }

}
