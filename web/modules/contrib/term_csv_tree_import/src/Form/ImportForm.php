<?php

namespace Drupal\term_csv_tree_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\term_csv_tree_import\Service\CollectCsvDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ImportForm.
 *
 * @package Drupal\term_csv_tree_import\Form
 */
class ImportForm extends FormBase {

  /**
   * CollectCsvData service.
   *
   * @var \Drupal\term_csv_tree_import\Service\CollectCsvDataInterface
   */
  protected $collectCsvData;

  /**
   * The Collect csv data service.
   *
   * @var \Drupal\term_csv_tree_import\Service\CollectCsvData
   */
  public function __construct(CollectCsvDataInterface $collectCsvData) {
    $this->collectCsvData = $collectCsvData;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('term_csv_tree_import.collectCsvData')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'default_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['term_import'] = [
      '#type' => 'file',
      '#title' => $this->t('Choose a file'),
    ];

    $form['vocabulary'] = array(
      '#type' => 'select',
      '#title' => $this->t('Taxonomy'),
      '#options' => taxonomy_vocabulary_get_names(),
    );

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $target_file = 'temporary://';
    // Set validator for extension and max size.
    $validator['file_validate_extensions'] = ['csv'];
    $validator['file_validate_size'] = [file_upload_max_size()];
    // File upload.
    if ($file = file_save_upload('term_import', $validator, $target_file)) {
      for ($key = 0; $key < count($file); $key++) {
        $filename = $file[$key]->getFilename();
        $filepath = $file[$key]->getFileUri();
      }
      drupal_set_message($this->t("The file @file_name has been successfully uploaded.",
        [
          '@file_name' => $filename,
        ]
      ));
    }
    else {
      drupal_set_message($this->t("Sorry, there was an error uploading your file."), "error");
      return;
    }

    // Call service to create term and sub term with custom fields, if any.
    $status = $this->collectCsvData->loadData($filepath, $form_state->getValue('vocabulary'));
    drupal_set_message($this->t($status));
  }

}
