<?php

namespace Drupal\image_export_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\image_export_import\EntitySaveHelper;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Session\AccountProxy;

/**
 * Class EntitySaveForm.
 *
 * @package Drupal\image_export_import\Form
 */
class BulkImportImagesForm extends FormBase {

  /**
   * Drupal File System.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * A instance of the image_export_import helper services.
   *
   * @var \Drupal\image_export_import\EntitySaveHelper
   */
  protected $imageHelper;

  /**
   * A instance of the EntityTypeManagerInterface.
   *
   * @var \Drupal\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public function __construct(FileSystem $file_system, EntitySaveHelper $imageHelper, EntityTypeManagerInterface $entityTypeManager, AccountProxy $current_user) {
    $this->fileSystem = $file_system;
    $this->imageHelper = $imageHelper;
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('file_system'), $container->get('image_export_import.batch_import_export'), $container->get('entity_type.manager'), $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bulk_image_export_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Choose action type.
    $form['import_type'] = [
      '#title' => 'Action Type',
      '#type' => 'select',
      '#weight' => 1,
      '#description' => $this->t('Please choose your action like: export, import and delete unused files from CMS.'),
      '#options' => [
        'export' => 'Export',
        'import' => 'Import',
        'delete' => 'Delete unused',
      ],
    ];
    // Upload csv file.
    $form['importimage_csv'] = [
      '#type' => 'managed_file',
      '#title' => 'Choose CSV File',
      '#description' => $this->t('Please upload csv file to create.'),
      '#upload_location' => 'public://imported-images/',
      '#weight' => 2,
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
      ],
    ];
    $form['upload_zip'] = [
      '#title' => $this->t('Upload image zip file'),
      '#type' => 'managed_file',
      '#weight' => 2.5,
      '#upload_location' => 'public://migrate_images/',
      '#upload_validators' => [
        'file_validate_extensions' => ['zip tar tar.gz'],
      ],
      '#description' => $this->t('Create package of media files and upload to Drupal. Valid extensions are .zip, .tar.gz, .tar. All files will be extracted to public://migrate_images/'),
    ];
    // Get content types from CMS.
    $content_types = $this->imageHelper->getAllContentTypes();
    $selected = !empty($form_state->getValue('content_types')) ? $form_state->getValue('content_types') : key($content_types);
    $form['content_types'] = [
      '#title' => 'Content types',
      '#type' => 'select',
      '#required' => TRUE,
      '#weight' => 3,
      '#default_value' => $selected,
      '#description' => $this->t('Please select content type for import.'),
      '#options' => $content_types,
      '#states' => [
        'invisible' => [':input[name="import_type"]' => ['value' => 'delete']],
      ],
      '#ajax' => [
        'callback' => '::ajaxExampleDependentDropdownCallback',
        'wrapper' => 'dropdown-second-replace',
      ],
    ];
    // Get content types image fields.
    $form['image_fields'] = [
      '#title' => $content_types[$selected] . ' Image field',
      '#type' => 'select',
      '#options' => $this->imageHelper->getAllImageFields($selected, 'node'),
      '#default_value' => $selected,
      '#weight' => 4,
      '#description' => $this->t('Please select image field from  content type. Incase of Media field only select image type media not video, audio or other.'),
      '#prefix' => '<div id="dropdown-second-replace">',
      '#suffix' => '</div>',
      '#states' => [
        'invisible' => [':input[name="import_type"]' => ['value' => 'delete']],
      ],
    ];

    // Download sample files.
    $form['delete_confirm'] = [
      '#type' => 'checkbox',
      '#weight' => 5,
      '#description' => $this->t('This action cannot be undone.'),
      '#title' => "Are you sure want to delete ?",
      '#states' => [
        'visible' => [':input[name="import_type"]' => ['value' => 'delete']],
      ],
    ];

    // Export body summary.
    $form['export_body'] = [
      '#type' => 'checkbox',
      '#weight' => 6,
      '#description' => $this->t('Include Body and summary as well (In case of import action Body, Summary will update).'),
      '#title' => "Body and Summary.",
      '#states' => [
        'invisible' => [':input[name="import_type"]' => ['value' => 'delete']],
      ],
    ];

    // Create node based on title.
    $form['new_node'] = [
      '#type' => 'checkbox',
      '#weight' => 7,
      '#description' => $this->t('If nid not present in csv then create new node with title, summary,body and images fields.'),
      '#title' => "Create new node",
      '#states' => [
        'visible' => [':input[name="import_type"]' => ['value' => 'import']],
      ],
    ];

    // Import csv button.
    $form['import']['#type'] = 'actions';
    $form['import']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#button_type' => 'primary',
    ];

    // Attach library on form.
    $form['#attached']['library'][] = 'image_export_import/import_images';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxExampleDependentDropdownCallback(array &$form, FormStateInterface $form_state) {
    return $form['image_fields'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Delete confirmation validation.
    $delete_confirm = $form_state->getValue('delete_confirm');
    $import_type = $form_state->getValue('import_type');
    if (empty($delete_confirm) && $import_type == 'delete') {
      $form_state->setErrorByName('delete_confirm', 'Please check confirmation.');
    }
    // Import csv field validation.
    $importimage_csv = $form_state->getValue('importimage_csv');
    if (empty($importimage_csv) && $import_type == 'import') {
      $form_state->setErrorByName('importimage_csv', 'Please upload csv file.');
    }
    // Import zip file field validation.
    $upload_zip = $form_state->getValue('upload_zip');
    if (empty($upload_zip) && $import_type == 'import') {
      $form_state->setErrorByName('upload_zip', 'Please upload zip file.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Checked logged in admin user.
    $current = $this->currentUser->id();
    $import_type = $form_state->getValue('import_type');
    $content_types = $form_state->getValue('content_types');
    $image_fields = $form_state->getValue('image_fields');
    $export_body = $form_state->getValue('export_body');
    switch ($import_type) {
      case "export":
        // Check media field or only image field.
        $image_media_fields = explode("|", $image_fields);
        $image_field_name = ($image_media_fields[0] == 'media') ? $image_media_fields[2] : $image_media_fields[0];
        // Create filename based on your select export option.
        $filename = (!empty($export_body)) ? $content_types . '_' . $image_field_name . '_body_' . $current . '.csv' : $content_types . '_' . $image_field_name . '_' . $current . '.csv';
        $path = $this->imageHelper->createCsvFileExportData($filename, $content_types, $image_media_fields, $export_body);
        // Check file and export as csv format.
        if (is_file($path)) {
          $response = new BinaryFileResponse($path);
          $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename
          );
          $form_state->setResponse($response);
        }
        break;

      case "import":
        // Get fields value from $form_state.
        $new_node = $form_state->getValue('new_node');
        $result = 0;
        // Get Zip file and extract to detination directory.
        $upload_zip = $form_state->getValue('upload_zip');
        $upload_zip_file = $this->entityTypeManager->getStorage('file')->load($upload_zip[0]);
        // Load the appropriate archiver and extarct the archive.
        $archiver = $this->imageHelper->getArchiver($this->fileSystem->realpath($upload_zip_file->getFileUri()));
        $result_data = $archiver->extractTo('public://migrate_images/');
        // If zip succcessfullt extracted.
        if ($result_data === TRUE) {
          $csv_file = $form_state->getValue('importimage_csv');
          $file = $this->entityTypeManager->getStorage('file')->load($csv_file[0]);
          $handle = $this->imageHelper->getFileHandler($file->getFileUri());
          // Get fieldname from csv, we assume its first row.
          $this->imageHelper->getCsvData($handle);
          $operations = [];
          while (($data = $this->imageHelper->getCsvData($handle)) !== FALSE) {
            $image_data = [
              'data' => $data,
              'content_type' => $content_types,
              'new_node' => $new_node,
              'image_field' => $image_fields,
              'save_body' => $export_body,
              'result' => ++$result,
            ];
            // \Drupal\image_export_import\EntitySaveHelper::importImageFromCsv($image_data);
            $operations[] = [
              '\Drupal\image_export_import\EntitySaveHelper::importImageFromCsv',
              [$image_data],
            ];
          }
          // Execute batch oprations.
          $this->batchOprations($operations, $handle);
          // Delete the archive file that was uploaded.
          file_delete($upload_zip_file->id());
          file_delete($file->id());
        }
        else {
          drupal_set_message($this->t('There is some problem related to extraction
          of the file. Please upload and try again.'), 'error', FALSE);
        }

        break;

      case "delete":
        $this->imageHelper->deleteOrphanFiles();
        drupal_set_message($this->t("Unused file deleted from CMS."));
        break;
    }
  }

  /**
   * Batch operation execution.
   *
   * @param mixed $operations
   *   Object contains all information related to batch.
   * @param mixed $handle
   *   Manage batch operation.
   */
  public function batchOprations($operations, $handle) {
    if (count($operations)) {
      // Once everything is gathered and ready to be processed.
      $batch = [
        'title' => $this->t('Importing CSV...'),
        'operations' => $operations,
        'finished' => '\Drupal\image_export_import\EntitySaveHelper::importImageFromCsvFinishedCallback',
        'error_message' => $this->t('The import has encountered an error.'),
        'progress_message' => $this->t('Imported @current of @total rows.'),
      ];
      batch_set($batch);
      fclose($handle);
    }
  }

}
