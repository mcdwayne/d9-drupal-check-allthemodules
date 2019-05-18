<?php
/**
 * @file
 * Contains \Drupal\data_import\Controller\DataImportAdmin.
 */

namespace Drupal\data_import\Controller;
 
use Drupal\Core\Controller\ControllerBase;
use Drupal\data_import\Form;

class DataImportAdmin extends ControllerBase {
  // main settings
  public function main() {
    $form = \Drupal::formBuilder()->getForm('\Drupal\data_import\Form\dataImportMainForm');
    return $form;
  }

  // create new importer
  public function createImporter() {
    $form = \Drupal::formBuilder()->getForm('\Drupal\data_import\Form\importerSettingsForm');
    return $form;
  }

  // mapping
  public function mapping($importer_id) {
    $form = \Drupal::formBuilder()->getForm('\Drupal\data_import\Form\importerMappingForm', $importer_id);
    return $form;
  }

  // edit importer
  public function editImporter($importer_id) {
    $form = \Drupal::formBuilder()->getForm('\Drupal\data_import\Form\importerSettingsForm', $importer_id);
    return $form;
  }
  
  // delete importer
  public function deleteImporter($importer_id) {
    $form = \Drupal::formBuilder()->getForm('\Drupal\data_import\Form\importerDeleteForm', $importer_id);
    return $form;
  }

  // log
  public function log() {
    $form = \Drupal::formBuilder()->getForm('\Drupal\data_import\Form\importerLogForm');
    return $form;
  }
}