<?php

namespace Drupal\synhelper\Hook;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Markup;

/**
 * FormFieldConfigEditFormAlter.
 */
class FormFieldConfigEditFormAlter extends ControllerBase {

  /**
   * Hook.
   */
  public static function hook(&$form, $form_state, $form_id) {

    if (isset($form['settings']['file_directory'])) {
      $msg = [];
      // File path.
      $current_path = \Drupal::service('path.current')->getPath();
      $path_args = explode('/', $current_path);
      $node_type = $path_args[5];
      $path = $node_type . '/[date:custom:Y]';
      $type = strstr($current_path, 'field_', FALSE);
      if ($type == 'field_gallery') {
        $path = $node_type . '-gallery/[date:custom:Y]';
        $msg[] = t('Gallery custom config applied');
      }
      elseif ($type == 'field_attach') {
        $form['settings']['description_field']['#default_value'] = TRUE;
        $msg[] = t('Attached files custom config applied');
      }

      // File Directory.
      if (isset($form['settings']['file_directory']['#default_value'])) {
        $dir_path = $form['settings']['file_directory']['#default_value'];
        if (!strpos("+$dir_path", $node_type)) {
          $msg[] = t("FIX: wrong file_directory! @a=>@b", ['@a' => $dir_path, '@b' => $path]);
          $form['settings']['file_directory']['#default_value'] = $path;
        }
        if (empty($form['settings']['file_directory']['#default_value'])) {
          $form['settings']['file_directory']['#default_value'] = $path;
        }
      }

      // Image.
      if (isset($form['settings']['max_resolution'])) {
        $msg[] = t("Image detected!");
        if (empty($form['settings']['max_resolution']['x']['#default_value'])) {
          $form['settings']['max_resolution']['x']['#default_value'] = '2000';
        }
        if (empty($form['settings']['max_resolution']['y']['#default_value'])) {
          $form['settings']['max_resolution']['y']['#default_value'] = '1300';
        }
        if (empty($form['settings']['min_resolution']['x']['#default_value'])) {
          $form['settings']['min_resolution']['x']['#default_value'] = '800';
        }
        if (empty($form['settings']['min_resolution']['y']['#default_value'])) {
          $form['settings']['min_resolution']['y']['#default_value'] = '600';
        }
        if (empty($form['settings']['max_filesize']['#default_value'])) {
          $form['settings']['max_filesize']['#default_value'] = '5 MB';
        }
        if (empty($form['settings']['file_extensions']['#default_value'])) {
          $file_types = 'gif, jpg, jpeg, png';
          $form['settings']['file_extensions']['#default_value'] = $file_types;
        }
        // OFF: Alt Field Required.
        if (!empty($form['settings']['alt_field_required']['#default_value'])) {
          $msg[] = t("OFF: Alt Field Required");
          $form['settings']['alt_field_required']['#default_value'] = FALSE;
        }
      }
      // File.
      elseif (isset($form['settings']['max_filesize']['#default_value'])) {
        $msg[] = t("File detected!");
        if (empty($form['settings']['max_filesize']['#default_value'])) {
          $form['settings']['max_filesize']['#default_value'] = '25 MB';
        }
        if (empty($form['settings']['file_extensions']['#default_value'])) {
          $file_types = 'txt, jpg, jpeg, png, doc, docx, pdf, ods, xlsx, xls, zip, rar';
          $form['settings']['file_extensions']['#default_value'] = $file_types;
        }
      }
      if (!empty($msg)) {
        $msg[] = t("<small>@class</small>", ['@class' => __CLASS__]);
        $message = implode("<br>", $msg);
        drupal_set_message(Markup::create($message), 'warning');
      }
    }
  }

}
