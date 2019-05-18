<?php

namespace Drupal\devel_clipboard\Form;

use Drupal\devel\Form\ExecutePHP;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that allows privileged users to execute arbitrary PHP code.
 */
class AlterExecutePHP extends ExecutePHP {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('devel_clipboard.settings');
    $enabled = $config->get('enable');
    if ($enabled) {
      $code = [];
      $dir_uri = 'private://devel_clipboard';
      $file_list = file_scan_directory($dir_uri, '/.*\.txt$/', $options = [], $depth = 0);
      foreach ($file_list as $value) {
        $single_uri = $value->uri;
        $get_contents = file_get_contents($single_uri);
        $code[$value->name] = $get_contents;
      }
      krsort($code, SORT_NUMERIC);
      $static_arr = ['none' => '- None -'];
      $code = array_merge($static_arr, $code);
      $clipboardCount = \Drupal::config('devel_clipboard.settings')->get('clipboardCount');
      $clipboardCount += 1;
      $sliced_code = array_slice($code, 0, $clipboardCount);

      // Add a checkbox to registration form for terms.
      $form['clipboard_list'] = [
        '#type' => 'select',
        '#title' => t("Choose the code"),
        '#options' => $sliced_code,
        '#weight' => '-99',
        '#description' => $this->t('Choose the code from select list to auto paste into the textarea(listing in descending order).'),
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    parent::submitForm($form, $form_state);
    $config = $this->config('devel_clipboard.settings');
    $enabled = $config->get('enable');
    if ($enabled) {
      $form_state_val = $form_state->getValues();
      $data = $form_state_val['code'];
      $directory = 'private://devel_clipboard';
      $result = is_dir($directory);
      if (!$result) {
        $result = file_prepare_directory($directory, FILE_MODIFY_PERMISSIONS | FILE_CREATE_DIRECTORY);
        if (!$result) {
          drupal_set_message(t('Failed to create %directory. Please configure your private folder properly.', ['%directory' => $directory]), 'error');
        }
      }
      if ($result) {
        $request_time = \Drupal::time()->getCurrentTime();
        $destination = $directory . '/' . $request_time . '.txt';
        // With the unmanaged file we just get a filename back.
        $filename = file_unmanaged_save_data($data, $destination, FILE_EXISTS_REPLACE);
        $wrapper = \Drupal::service('stream_wrapper_manager')->getViaUri($directory);
        if ($filename) {
          $url = $wrapper->getExternalUrl($filename);
        }
        else {
          drupal_set_message(t('Failed to save the file'), 'error');
        }
      }
    }
  }

}
