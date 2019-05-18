<?php

namespace Drupal\commerce_export\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

/**
 * Upload source CSV files to start commerce product imports.
 */
class SourceFiles extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_export_source_files';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $upload_location = 'public://import';

    $form[] = [
      '#type' => 'item',
      '#title' => t('Here you can upload your product information in CSV format.'),
      '#description' => t('Refer to the example_csv directory for CSV file format. If your project requires changes to the CSV columns, then corresponding changes to the migration files will be necessary.'),
    ];

    $form['product_csv'] = [
      '#type' => 'managed_file',
      '#title' => t('Products'),
      '#upload_location' => $upload_location,
      '#multiple' => FALSE,
      '#description' => t('All products and product variations with taxonomy, attribute values, and image filenames.'),
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
      ],
      // To make this a required field uncomment the following line, remove the
      // trailing period and adjust formatting.
      // '#required' => TRUE.
    ];
    $form['submit'] = [
      '#value' => t('Upload'),
      '#type' => 'submit',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $files = $form_state->getValues();

    $products = $files['product_csv'];

    // Upload product CSV.
    if (isset($products[0])) {
      $product_filename = 'product_details.csv';
      $fid = $products[0];
      $this->createPermanentSourceFile($fid, $product_filename);
    }
    drupal_set_message(t('Files uploaded successfully. Recommend to use drush to start the import.'), 'status');

    // Redirect to migration page.
    $form_state->setRedirect('entity.migration.list', [
      'migration_group' => 'commerce_export',
    ]);
  }

  /**
   * Make temp source files permanent with filename as per migration yaml.
   *
   * @param int $fid
   *   File entity ID.
   * @param string $filename
   *   File name.
   */
  protected function createPermanentSourceFile($fid, $filename) {
    $csv = File::load($fid);
    $old_uri = $csv->getFileUri();
    $path = pathinfo($old_uri);
    $new_uri = $path['dirname'] . '/' . $filename;

    if ($old_uri != $new_uri) {
      $csv->setFilename($filename);
      $csv->setFileUri($new_uri);
      file_unmanaged_copy($old_uri, $new_uri, FILE_EXISTS_REPLACE);
      $csv->setPermanent();
      $csv->save();

      $file_usage = \Drupal::service('file.usage');
      $file_usage->add($csv, 'commerce_export', 'file', $fid);
    }
  }

}
