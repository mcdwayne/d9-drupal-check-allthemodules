<?php

namespace Drupal\hierarchical_taxonomy_importer\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\hierarchical_taxonomy_importer\services\ImporterService;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Class TaxonomyImporterForm.
 */
class TaxonomyImporterForm extends FormBase {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Drupal\hierarchical_taxonomy_importer\services\ImporterService definition.
   *
   * @var \Drupal\hierarchical_taxonomy_importer\services\ImporterService
   */
  protected $hierarchicalTaxonomyImporter;

  /**
   * Constructs a new TaxonomyImporterForm object.
   */
  public function __construct(
  EntityTypeManager $entity_type_manager, ImporterService $hierarchical_taxonomy_importer_importer
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->hierarchicalTaxonomyImporter = $hierarchical_taxonomy_importer_importer;
  }

  /**
   *
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'), $container->get('hierarchical_taxonomy_importer.importer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'taxonomy_importer_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $output = [
     ['Honda', '', ''],
     ['', 'Honda', ''],
     ['', '', '100'],
     ['', '', '200'],
     ['', '', '300'],
     ['', '', '400'],
     ['', '', '500'],
     ['', 'Londa', ''],
     ['', '', '600'],
     ['', '', '700'],
     ['', '', '800'],
     ['', '', '900'],
    ];
    // $this->hierarchicalTaxonomyImporter->import('new_test', $output);.
    $form['vocabulary'] = [
     '#type' => 'select',
     '#title' => $this->t('Vocabularies'),
     '#description' => $this->t('Select a vocabulary to import taxonomies terms from CSV file.'),
     '#options' => $this->getVocabularies(),
     '#required' => TRUE,
     '#size' => 1,
    ];
    $form['csv_file'] = [
     '#type' => 'file',
     '#title' => $this->t('CSV File'),
     '#description' => $this->t('Upload a CSV file with taxonomy information.'),
    ];
    $form['submit'] = [
     '#type' => 'submit',
     '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    // Getting file array.
    $files = $this->getRequest()->files->get('files', []);
    // Checking if file is uploaded.
    if(!empty($files['csv_file'])) {
      $file_upload = $files['csv_file'];
      if($file_upload->isValid()) {
        $form_state->setValue('csv_file', $file_upload->getRealPath());
        return;
      }
    }

    $form_state->setErrorByName('csv_file', $this->t('The file could not be uploaded.'));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $csv_file_path = $form_state->getValue('csv_file');
    if(($handle = fopen($csv_file_path, "r")) !== FALSE) {
      // taxonomy_' . time();
      $vid = $form_state->getValue('vocabulary');
      $output = [];
      while(($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $output[] = $data;
      }

      $result = $this->hierarchicalTaxonomyImporter->import($vid, $output);
    }

    drupal_set_message(t('All terms have been imported successfully.'));
  }

  /**
   * This method returns the list of existing vocabularies.
   *
   * @return mixed
   *   List of existing Vocabularies in an associative array's form.
   */
  public function getVocabularies() {
    $output = [];
    // Loading existing vocabularies.
    $vocabularies = Vocabulary::loadMultiple();
    // If vocabularies are not empty then load them to an array one by one
    // Vocabulary ID would be used as offset and label as value of array on that
    // offset.
    if(!empty($vocabularies)) {
      // Traversal of vocabularies is taking place and preparing an options array.
      foreach($vocabularies as $vocabulary) {
        $output[$vocabulary->id()] = $vocabulary->get('name');
      }
    }
    // Return the output back to the form.
    return $output;
  }

}
