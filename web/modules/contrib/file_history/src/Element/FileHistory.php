<?php

namespace Drupal\file_history\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Url;
use Drupal\file\Entity\File;

/**
 * Provides an widget with memory of uploaded file.
 *
 * @FormElement("file_history")
 */
class FileHistory extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processFileHistory'],
      ],
      '#theme_wrappers' => ['form_element'],
      '#progress_indicator' => 'throbber',
      '#progress_message' => NULL,
      '#upload_validators' => [],
      '#upload_location' => NULL,
      '#size' => 22,
      '#multiple' => FALSE,
      '#extended' => FALSE,
      '#attached' => [
        'library' => ['file/drupal.file'],
      ],
      '#accept' => NULL,
      '#content_validator' => [],
      '#create_missing' => FALSE,
      '#no_upload' => FALSE,
      '#no_use' => FALSE,
      '#no_download' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {

    // If no upload_location, no field.
    if ($element['#upload_location'] == NULL) {
      drupal_set_message("'#upload_location' attribute are mandatory in file_history definition.", 'error');
      return;
    }

    $is_multiple = (isset($element['#multiple']) && $element['#multiple'] === TRUE);

    // If there is input.
    if ($input !== FALSE) {
      // We upload files.
      self::uploadFile($element, $input, $form_state);
      // @TODO : Manage upload status
    }

    // Prepare default values.
    $values = self::makeValues($element, $form_state);

    // Bind buttons.
    $button = $form_state->getTriggeringElement();

    if (strstr($button["#name"], $element['#name'])) {

      // Manage Select/deselect.
      if (strstr($button["#name"], 'select_file_button_')) {
        self::manageSelected($button["#name"], $values, $is_multiple);
      }

      // Manage Delete.
      if (strstr($button["#name"], 'delete_button_')) {
        self::deleteFile($button["#name"]);
      }
    }

    // Return default selected value.
    return ['selected' => $values];
  }

  /**
   * Render API callback: Expands the managed_file element type.
   *
   * Expands the file type to include Upload and Remove buttons, as well as
   * support for a default value.
   */
  public static function processFileHistory(&$element, FormStateInterface $form_state, &$complete_form) {
    // If no upload_location, no field.
    if ($element['#upload_location'] == NULL) {
      return;
    }

    $no_upload = (isset($element['#no_upload']) && $element['#no_upload'] === TRUE);
    $no_download = (isset($element['#no_download']) && $element['#no_download'] === TRUE);
    $no_use = (isset($element['#no_use']) && $element['#no_use'] === TRUE);
    $create_missing = (isset($element['#create_missing']) && $element['#create_missing'] === TRUE);

    // Prepare upload fields.
    // This is used sometimes so let's implode it just once.
    $parents_prefix = implode('_', $element['#parents']);
    $element['#tree'] = TRUE;

    $file_extension_mask = '/./';
    $extension_list = '';
    // Add the extension list to the page as JavaScript settings.
    if (isset($element['#upload_validators']['file_validate_extensions'][0])) {
      $extension_list = implode(',', array_filter(explode(' ', $element['#upload_validators']['file_validate_extensions'][0])));
      $file_extension_mask = '/.*\.' . str_replace(' ', '|', $element['#upload_validators']['file_validate_extensions'][0]) . '/';
    }

    if (!$no_upload) {
      // Add Upload field.
      $element['upload'] = [
        '#name' => 'files[' . $parents_prefix . ']',
        '#type' => 'file',
        '#title' => t('Choose a file'),
        '#title_display' => 'invisible',
        '#size' => $element['#size'],
        '#multiple' => $element['#multiple'],
        '#theme_wrappers' => [],
        '#weight' => -10,
        '#error_no_message' => TRUE,
        '#attached' => ['drupalSettings' => ['file' => ['elements' => ['#' . $element['#id'] => $extension_list]]]],
      ];

      // Add upload button.
      $element[$parents_prefix . '_upload_button'] = [
        '#name' => $parents_prefix . '_upload_button',
        '#type' => 'submit',
        '#value' => t('Upload'),
        '#validate' => [],
        '#submit' => ['file_history_submits'],
        '#limit_validation_errors' => [],
        '#weight' => -5,
      ];
    }

    // Add Table Header.
    $header = [
      'fid' => '',
      'name' => t('Name'),
      'filename' => t('Filename'),
      'weight' => t('Weight'),
      'uploaded' => t('Uploaded at'),
      'activ' => t('Is active file ?'),
      'operation' => t('Operations'),
      'selected' => '',
    ];

    if ($no_use) {
      unset($header['activ']);
      unset($header['selected']);
    }

    // Wait alterations of headers.
    \Drupal::moduleHandler()->invokeAll(
      'file_history_' . $element['#name'] . '_headers_alter',
      [&$header, $element['#name']]
    );

    // Prepare table rows.
    $rows = [];

    // List only files with correct extensions.
    $already_load_files = file_scan_directory($element['#upload_location'], $file_extension_mask);

    // Manage activ files.
    $currentFiles = (is_array($element['#value']['selected'])) ? $element['#value']['selected'] : [];

    // For Each files.
    foreach ($already_load_files as $file) {

      $fObj = self::getFileFromUri($file->uri);

      // If the file aren't know by Drupal.
      if ($fObj == NULL) {
        // And field is configure as autocreate.
        if ($create_missing) {
          // We save new file.
          $realpath = \Drupal::service('file_system')->realpath($file->uri);
          $values = [
            'uid' => 0,
            'filename' => $file->filename,
            'uri' => $file->uri,
            'filesize' => filesize($realpath),
            'status' => FILE_STATUS_PERMANENT,
          ];
          $values['filemime'] = \Drupal::service('file.mime_type.guesser')->guess($file->filename);
          $new_file = File::create($values);
          $new_file->save();
          if ($new_file->id() == NULL) {
            // If the file aren't save, pass to next.
            continue;
          }
          else {
            $fObj = $new_file;
          }
        }
        else {
          // Pass to next file.
          continue;
        }
      }

      // Process File for Table.
      $fid = $fObj->id();
      $realpath = \Drupal::service('file_system')->realpath($fObj->getFileUri());
      $fileRow = [];
      $fileRow['fid'] = [
        '#type' => 'hidden',
        '#value' => $fid,
      ];
      $fileRow['name'] = ['#markup' => $file->name];
      $fileRow['filename'] = ['#markup' => $fObj->getFilename()];
      $fileRow['weight'] = ['#markup' => format_size(filesize($realpath))];
      $fileRow['uploaded'] = ['#markup' => date('Y-m-d H:i', $fObj->getCreatedTime())];

      $isCurrentFile = (in_array($fid, $currentFiles));
      if (!$no_use) {
        $fileRow['activ'] = ['#markup' => $isCurrentFile ? t('Yes') : ''];
      }

      if (!$no_use) {
        // Prepare select/unselect submit.
        if ($isCurrentFile === TRUE) {
          $link_title = t('Unselect file');
          $route_target = 'unselect_file';
        }
        else {
          $link_title = t('Select file');
          $route_target = 'select_file';
        }

        // Add select/unselect submit.
        $fileRow['operation'][] = [
          '#name' => $element['#name'] . '_' . $route_target . '_button_' . $fid,
          '#type' => 'submit',
          '#value' => $link_title,
          '#validate' => [],
          '#submit' => ['file_history_submits'],
          '#limit_validation_errors' => [],
          '#weight' => -5,
        ];
      }

      if (!$isCurrentFile) {
        // Add delete submit.
        $fileRow['operation'][] = [
          '#name' => $element['#name'] . '_delete_button_' . $fid,
          '#type' => 'submit',
          '#value' => t('Delete'),
          '#validate' => [],
          '#submit' => ['file_history_submits'],
          '#limit_validation_errors' => [],
          '#weight' => -5,
        ];
      }

      if (!$no_download) {
        // Add download button.
        $url = self::makeDownloadLink($fObj);
        $links = [
          'title' => t('Download'),
          'url' => $url,
        ];
        $fileRow['operation'][] = [
          'item' =>
            [
              '#type' => 'dropbutton',
              '#links' => [$links],
            ],
        ];
      }

      if (!$no_use) {
        // Add hidden boolean value.
        $fileRow['selected'] = [
          '#type' => 'hidden',
          '#value' => $isCurrentFile ? 1 : 0,
        ];
      }

      // Attach rows to table.
      $rows[$fObj->getCreatedTime()] = $fileRow;
    }

    // We sort files by upload time.
    krsort($rows);
    $sorted_rows = array_values($rows);

    // Wait alterations of rows.
    \Drupal::moduleHandler()->invokeAll(
      'file_history_' . $element['#name'] . '_rows_alter',
      [&$sorted_rows, $element['#name']]
    );

    // Make table.
    $element['table'] = [
      '#type' => 'table',
      '#header' => $header,
    ];

    // Add rows.
    foreach ($sorted_rows as $row) {
      $element['table'][] = $row;
    }

    // Return form item.
    return $element;
  }

  /**
   * Method to retrieve a file object given an uri.
   *
   * @param string $uri
   *   Uri of file.
   *
   * @return \Drupal\file\FileInterface|null
   *   returns a file given the uri.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public static function getFileFromUri($uri) {
    $files = \Drupal::entityTypeManager()->getStorage('file')->loadByProperties(['uri' => $uri]);
    if (!empty($files)) {
      $fileArray = array_values($files);
      return $fileArray[0];
    }
    return NULL;
  }

  /**
   * Generate Download URL.
   *
   * @param \Drupal\file\Entity\File $file
   *   File object.
   *
   * @return \Drupal\Core\Url
   *   Url object
   */
  public static function makeDownloadLink(File $file) {
    $path = $file->getFileUri();

    $scheme = \Drupal::service('file_system')->uriScheme($path);

    if ($scheme == 'public') {
      $filepath = file_create_url($path);
      return Url::fromUri($filepath);
    }
    else {
      return Url::fromRoute('file_history.download_nonpublic_file',
        [
          'file' => $file->id(),
        ]);
    }
  }

  /**
   * Upload file.
   *
   * @param mixed $element
   *   Element.
   * @param mixed $input
   *   Input.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   FormState.
   *
   * @return bool
   *   Status of upload.
   */
  public static function uploadFile(&$element, $input, FormStateInterface $form_state) {

    $all_files = \Drupal::request()->files->get('files', []);
    $upload_name = $element['#name'];
    /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $uploaded_file */
    $uploaded_file = $all_files[$upload_name];

    if (is_array($uploaded_file) && isset($uploaded_file[0])) {
      $uploaded_file = $uploaded_file[0];
    }

    // If a file are uploaded.
    if ($uploaded_file != NULL && file_exists($uploaded_file)) {

      // $uploaded_file = $all_files[$upload_name][0];
      // If isset file content validation.
      if (is_callable($element['#content_validator'], FALSE, $validation_callback)) {

        $file_data_for_validation = [
          'file_original_name' => $uploaded_file->getClientOriginalName(),
          'file_original_extension' => $uploaded_file->getClientOriginalExtension(),
          'file_size' => $uploaded_file->getClientSize(),
          'file_path' => $uploaded_file->getRealPath(),
        ];

        $return_status = $validation_callback($file_data_for_validation);

        if (!isset($return_status['status'])) {
          drupal_set_message("Validation callback need return at least a status 'return ['status' => Boolean]'", 'error');
          return FALSE;
        }

        $status = 'status';
        if ($return_status['status'] === FALSE) {
          $status = 'error';
        }

        if (isset($return_status['message']) && $return_status['message'] != '') {
          drupal_set_message($return_status['message'], $status);
        }

        // If validation failed.
        if ($return_status['status'] === FALSE) {
          return FALSE;
        }
      }

      // If validation pass, we save file.
      $destination = isset($element['#upload_location']) ? $element['#upload_location'] : NULL;

      file_prepare_directory($destination, FILE_CREATE_DIRECTORY);

      if (!$files = file_save_upload($upload_name, $element['#upload_validators'], $destination)) {
        \Drupal::logger('file')->notice('The file upload failed. %upload', ['%upload' => $upload_name]);
        $form_state->setError($element, t('Files in the @name field were unable to be uploaded.', ['@name' => $element['#title']]));
        return FALSE;
      }
      else {
        // Set file as permanent.
        /** @var \Drupal\file\Entity\File $file */
        foreach ($files as $file) {
          if ($file != NULL && $file->isTemporary()) {
            $file->setPermanent();
            $file->save();
          }
        }
      }
    }
    return TRUE;
  }

  /**
   * Extract values of field.
   *
   * @param mixed $element
   *   Element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array
   *   List of selected Fids.
   */
  public static function makeValues($element, FormStateInterface $form_state) {

    $input = $form_state->getUserInput();
    $values = [];
    if (isset($input[$element['#name']]['table'])) {
      $table_data = $input[$element['#name']]['table'];

      foreach ($table_data as $row_id => $data) {
        if (isset($data['selected']) && $data['selected'] == 1) {
          $values[] = $data['fid'];
        }
      }
    }
    else {
      if (isset($element["#default_value"])) {
        $values = $element["#default_value"];
      }
      else {
        $values = [];
      }
    }
    return $values;
  }

  /**
   * Manage select/unselect files.
   *
   * @param string $button_name
   *   Name of clicked button.
   * @param array $values
   *   Default list of Fids.
   * @param bool $is_multiple
   *   Boolean.
   */
  public static function manageSelected($button_name, array &$values, $is_multiple = FALSE) {

    if (strstr($button_name, '_unselect_file_button_')) {
      list($element_name, $fid) = explode('_unselect_file_button_', $button_name);

      foreach ($values as $key => $value) {
        if ($value == $fid) {
          unset($values[$key]);
        }
      }
    }
    if (strstr($button_name, '_select_file_button_')) {
      list($element_name, $fid) = explode('_select_file_button_', $button_name);
      if ($is_multiple == TRUE) {
        $values[] = $fid;
      }
      else {
        $values = [$fid];
      }

    }
  }

  /**
   * Delete file action.
   *
   * @param string $button_name
   *   Name of button.
   */
  public static function deleteFile($button_name) {

    if (strstr($button_name, '_delete_button_')) {
      list($element_name, $fid) = explode('_delete_button_', $button_name);

      $file = File::load($fid);
      $file->delete();
    }
  }

}
