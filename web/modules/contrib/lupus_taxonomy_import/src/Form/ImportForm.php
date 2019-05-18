<?php

namespace Drupal\lupus_taxonomy_import\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\lupus_taxonomy_import\Service\Importer;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Form to import taxonomies via csv file.
 */
class ImportForm extends FormBase implements ContainerInjectionInterface {

  /**
   * Uploaded file entity.
   *
   * @var \Drupal\file\Entity\File
   */
  protected $file;

  /**
   * Import service.
   *
   * @var \Drupal\lupus_taxonomy_import\Service\Importer
   */
  protected $importer;

  /**
   * ImportForm constructor.
   *
   * @param \Drupal\lupus_taxonomy_import\Service\Importer $importer
   *   Import service.
   */
  public function __construct(Importer $importer) {
    $this->importer = $importer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('lupus_taxonomy_import.importer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lupus_taxonomy_import_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $vocabularies = Vocabulary::loadMultiple();
    $vocabulary_options = [];
    foreach ($vocabularies as $vocabulary) {
      $vocabulary_options[$vocabulary->id()] = $vocabulary->get('name');
    }

    $form['vocabulary_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Vocabulary'),
      '#required' => TRUE,
      '#empty_value' => '',
      '#empty_option' => $this->t('- Select -'),
      '#options' => $vocabulary_options,
    ];

    $validators = [
      'file_validate_extensions' => ['csv'],
      'file_validate_size' => [file_upload_max_size()],
    ];

    $example_with_hierarchy = Link::createFromRoute('Example with Hierarchy', 'lupus_taxonomy_import.csv_import.example', ['type' => 'example_with_hierarchy']);
    $example_ingredients = Link::createFromRoute('Example for Ingredients', 'lupus_taxonomy_import.csv_import.example', ['type' => 'example_ingredients']);

    $form['csv_file'] = [
      '#type' => 'file',
      '#title' => $this->t('CSV file'),
      '#upload_validators'  => $validators,
      '#description' => $this->t('The csv file containing the taxonomy terms. 
        It must contain a header with a number for each term-column starting with 0, 
        optional taxonomy fields can be set by adding a fieldname (e.g: 0, 1, 2, status, weight).
        Make sure the fields exists in the vocabulary or it will be skipped. CSV Examples:') . ' ' .
      $example_with_hierarchy->toString()->getGeneratedLink() . ', ' .
      $example_ingredients->toString()->getGeneratedLink(),
    ];

    $form['purge_vocabulary'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Purge existing terms from vocabulary'),
      '#description' => $this->t("WARNING: If there are currently terms in the vocabulary they will be removed before the import and all content using these terms will lose the reference. Use only if you are sure there are no references to the existing terms. If this option is not selected, terms from the csv will be added to the taxonomy instead (which could introduce duplicate entries.) Updating existing terms is not supported."),
    ];

    $form['actions']['#type'] = 'actions';
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $this->file = file_save_upload('csv_file', $form['csv_file']['#upload_validators'], FALSE, 0);
    if (empty($this->file)) {
      $form_state->setErrorByName('csv_file', $this->t('CSV file field is required.'));
    }

    if ($errors = $this->importer->validate($this->file)) {
      $form_state->setErrorByName('csv_file', $this->t('Invalid file format. Check the example file for proper formatting.'));
      foreach ($errors as $error) {
        $this->messenger()->addWarning($error);
      }
    }

    $vocabulary_id = $form_state->getValue('vocabulary_id');
    $vocabularies = Vocabulary::loadMultiple([$vocabulary_id]);
    if (empty($vocabularies)) {
      $form_state->setErrorByName('vocabulary_id', $this->t("Vocabulary %id not found.", ['%id' => $vocabulary_id]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (!isset($this->file)) {
      $this->messenger()->addWarning($this->t('No valid file was found.'));
      return;
    }

    $vocabulary_id = $form_state->getValue('vocabulary_id');
    $purge_vocabulary = (bool) $form_state->getValue('purge_vocabulary');
    $success = $this->importer->importFromCsv($this->file, $vocabulary_id, $purge_vocabulary);
    // Remove temporary file.
    file_delete($this->file->id());

    if ($success) {
      $url = Url::fromUri("base:/admin/structure/taxonomy/manage/{$vocabulary_id}/overview");
      $form_state->setRedirectUrl($url);
    }
    else {
      $this->messenger()->addWarning($this->t('Error during the import.'));
    }
  }

}
