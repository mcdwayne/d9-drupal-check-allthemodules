<?php

/**
 * @file
 * Contains \Drupal\webform_submission_files_download\Controller\DefaultController.
 */

namespace Drupal\webform_submission_files_download\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\file\Entity\File;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\Entity\Webform;
use PclZip;

/**
 * Default controller for the webform_submission_files_download module.
 */
class DefaultController extends ControllerBase {

  public function download_download($webform, $webform_submission) {
    $filename = $webform . '-' . $webform_submission . '-files';
    $webform = Webform::load($webform);
    $webform_submission = WebformSubmission::load($webform_submission);
    $elements_managed_files = $webform->getElementsManagedFiles();
    $fids = [];
    foreach($elements_managed_files as $k=>$element_name) {
      $fid = $webform_submission->getElementData($element_name);
      if(!empty($fid)) {
        $fids[] = $webform_submission->getElementData($element_name);
      }
    }
    $file_entities = File::loadMultiple($fids);
    $files = [];
    foreach($file_entities as $file_entity) {
      $files[] = \Drupal::service("file_system")->realpath($file_entity->getFileUri());
    }

    $filename = $filename . '.zip';
    $tmp_file = file_save_data('', 'private://' . $filename);
    $tmp_file->status = 0;
    $tmp_file->save();

    $archive = new PclZip(\Drupal::service("file_system")->realpath($tmp_file->getFileUri()));
    $archive->add($files, PCLZIP_OPT_REMOVE_ALL_PATH);

    header("Content-Type: application/force-download");
    header('Content-Description: File Transfer');
    header('Content-Disposition: inline; filename=' . $filename);
    readfile(\Drupal::service("file_system")->realpath($tmp_file->getFileUri()));
    exit();
  }

}
