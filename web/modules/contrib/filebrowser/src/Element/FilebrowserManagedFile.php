<?php

namespace Drupal\filebrowser\Element;

use Drupal\file\Element\ManagedFile;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Site\Settings;
use Drupal\file\Entity\File;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * Provides an AJAX/progress aware widget for uploading and saving a file.
 *
 * @FormElement("filebrowser_managed_file")
 */
class FilebrowserManagedFile extends ManagedFile {
  /**
   * @inheritDoc
   */
  public function getInfo() {
    $array = parent::getInfo();
    $array['#multiple'] = true;
    return $array;
  }

  protected static function custom_file_managed_file_save_upload($element, FormStateInterface $form_state) {
    $upload_name = implode('_', $element['#parents']);
    $all_files = \Drupal::request()->files->get('files', array());
    if (empty($all_files[$upload_name])) {
      return FALSE;
    }
    $file_upload = $all_files[$upload_name];

    $destination = isset($element['#upload_location']) ? $element['#upload_location'] : NULL;
    if (isset($destination) && !file_prepare_directory($destination, FILE_CREATE_DIRECTORY)) {
      \Drupal::logger('file')->notice('The upload directory %directory for the file field %name could not be created or is not accessible. A newly uploaded file could not be saved in this directory as a consequence, and the upload was canceled.', array('%directory' => $destination, '%name' => $element['#field_name']));
      $form_state->setError($element, t('The file could not be uploaded.'));
      return FALSE;
    }

    // Save attached files to the database.
    $files_uploaded = $element['#multiple'] && count(array_filter($file_upload)) > 0;
    $files_uploaded |= !$element['#multiple'] && !empty($file_upload);
    if ($files_uploaded) {
      $nid = \Drupal::routeMatch()->getParameter('nid');
      if(ctype_digit($nid)) {
        $node = Node::load($nid);
      }
      else {
        $node = null;
      }
      if ($node instanceof NodeInterface) {
        $config = \Drupal::config('filebrowser.settings');
        $config = $config->get('filebrowser');
        $nodeValues = isset($node->filebrowser) ? $node->filebrowser : null;
        $allowOverwrite = isset($nodeValues->allowOverwrite) ? $nodeValues->allowOverwrite : $config['uploads']['allow_overwrite'];
        if($allowOverwrite) {
          $files = file_save_upload($upload_name, $element['#upload_validators'], $destination, null, FILE_EXISTS_REPLACE);
        }
        else {
          $files = file_save_upload($upload_name, $element['#upload_validators'], $destination);
        }
      }
      else {
        $files = file_save_upload($upload_name, $element['#upload_validators'], $destination);
      }
      if (!$files) {
        \Drupal::logger('file')->notice('The file upload failed. %upload', array('%upload' => $upload_name));
        $form_state->setError($element, t('Files in the @name field were unable to be uploaded.', array('@name' => $element['#title'])));
        return array();
      }

      // Value callback expects FIDs to be keys.
      $files = array_filter($files);
      $fids = array_map(function($file) {
        return $file->id();
      }, $files);

      return empty($files) ? array() : array_combine($fids, $files);
    }

    return array();
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    // Find the current value of this field.
    $fids = !empty($input['fids']) ? explode(' ', $input['fids']) : [];
    foreach ($fids as $key => $fid) {
      $fids[$key] = (int) $fid;
    }
    $force_default = FALSE;

    // Process any input and save new uploads.
    if ($input !== FALSE) {
      $input['fids'] = $fids;
      $return = $input;

      // Uploads take priority over all other values.
      if ($files = self::custom_file_managed_file_save_upload($element, $form_state)) {
        if ($element['#multiple']) {
          $fids = array_merge($fids, array_keys($files));
        }
        else {
          $fids = array_keys($files);
        }
      }
      else {
        // Check for #filefield_value_callback values.
        // Because FAPI does not allow multiple #value_callback values like it
        // does for #element_validate and #process, this fills the missing
        // functionality to allow File fields to be extended through FAPI.
        if (isset($element['#file_value_callbacks'])) {
          foreach ($element['#file_value_callbacks'] as $callback) {
            $callback($element, $input, $form_state);
          }
        }

        // Load files if the FIDs have changed to confirm they exist.
        if (!empty($input['fids'])) {
          $fids = [];
          foreach ($input['fids'] as $fid) {
            if ($file = File::load($fid)) {
              $fids[] = $file->id();
              // Temporary files that belong to other users should never be
              // allowed.
              if ($file->isTemporary()) {
                if ($file->getOwnerId() != \Drupal::currentUser()->id()) {
                  $force_default = TRUE;
                  break;
                }
                // Since file ownership can't be determined for anonymous users,
                // they are not allowed to reuse temporary files at all. But
                // they do need to be able to reuse their own files from earlier
                // submissions of the same form, so to allow that, check for the
                // token added by $this->processManagedFile().
                elseif (\Drupal::currentUser()->isAnonymous()) {
                  $token = NestedArray::getValue($form_state->getUserInput(), array_merge($element['#parents'], ['file_' . $file->id(), 'fid_token']));
                  if ($token !== Crypt::hmacBase64('file-' . $file->id(), \Drupal::service('private_key')->get() . Settings::getHashSalt())) {
                    $force_default = TRUE;
                    break;
                  }
                }
              }
            }
          }
          if ($force_default) {
            $fids = [];
          }
        }
      }
    }

    // If there is no input or if the default value was requested above, use the
    // default value.
    if ($input === FALSE || $force_default) {
      if ($element['#extended']) {
        $default_fids = isset($element['#default_value']['fids']) ? $element['#default_value']['fids'] : [];
        $return = isset($element['#default_value']) ? $element['#default_value'] : ['fids' => []];
      }
      else {
        $default_fids = isset($element['#default_value']) ? $element['#default_value'] : [];
        $return = ['fids' => []];
      }

      // Confirm that the file exists when used as a default value.
      if (!empty($default_fids)) {
        $fids = [];
        foreach ($default_fids as $fid) {
          if ($file = File::load($fid)) {
            $fids[] = $file->id();
          }
        }
      }
    }

    $return['fids'] = $fids;
    return $return;
  }
}