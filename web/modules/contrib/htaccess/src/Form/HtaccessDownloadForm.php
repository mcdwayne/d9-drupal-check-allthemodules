<?php

/**
 * @file
 * Contains Drupal\htaccess\Form\HtaccessDownloadForm.
 */

namespace Drupal\htaccess\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Defines a form to configure RSVP List module settings
 */
class HtaccessDownloadForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'htaccess_admin_download';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL){
    $path = $request->getPathInfo();

    $parts = explode('/', $path);

    $id = $parts[7];

    $htaccess_get = Database::getConnection()->select('htaccess', 'h');
    $htaccess_get->fields('h');
    $htaccess_get->condition('id', $id);
    $results = $htaccess_get->execute();

    $result = $results->fetch();

    $htaccess_content = $result->htaccess;

    // Remove utf8-BOM
    $htaccess_content = str_replace("\xEF\xBB\xBF",'', $htaccess_content);

    $file_name = $result->name.'.htaccess';

    $htaccess_folder = 'public://htaccess';

    $htaccess_file = file_save_data($htaccess_content, "$htaccess_folder/$file_name", FILE_EXISTS_RENAME);

    return new BinaryFileResponse($htaccess_file->getFileUri(), 200, array(
      'Content-Type' => 'application/octet-stream',
      'Content-disposition' => 'attachment; filename='.$file_name));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state){


  }
}
