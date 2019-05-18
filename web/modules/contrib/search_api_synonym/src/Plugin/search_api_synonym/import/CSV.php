<?php

namespace Drupal\search_api_synonym\Plugin\search_api_synonym\import;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\search_api_synonym\Import\ImportPluginBase;
use Drupal\search_api_synonym\Import\ImportPluginInterface;

/**
 * Import of CSV files.
 *
 * @SearchApiSynonymImport(
 *   id = "csv",
 *   label = @Translation("CSV"),
 *   description = @Translation("Synonym import plugin from CSV / delimited file.")
 * )
 */
class CSV extends ImportPluginBase implements ImportPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function parseFile(File $file, array $settings = []) {
    $data = [];
    $delimiter = $settings['delimiter'];
    $enclosure = $settings['enclosure'];
    $header_row = $settings['header_row'];
    
    $i = 1;
    if (($handle = fopen($file->getFileUri(), 'r')) !== FALSE) {
      while (($row = fgetcsv($handle, 1000, $delimiter, $enclosure)) !== FALSE) {
        if ($header_row && $i++ == 1) {
          continue;
        }

        if (!empty($row[0]) && !empty($row[1])) {
          $data[] = [
            'word' => $row[0],
            'synonym' => $row['1'],
            'type' => !empty($row['2']) ? $row['2'] : ''
          ];
        }
      }
      fclose($handle);
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $example_url = 'internal:' . base_path() . drupal_get_path('module', 'search_api_synonym') . '/examples/example.csv';
    $form['template'] = [
      '#type' => 'item',
      '#title' => $this->t('Example'),
      '#markup' => Link::fromTextAndUrl(t('Download example file'), Url::fromUri($example_url))->toString()
    ];
    $form['delimiter'] = [
      '#type' => 'select',
      '#title' => t('Delimiter'),
      '#description' => t('Field delimiter character used in the import file.'),
      '#options' => [
        ';' => $this->t('Semicolon'),
        ',' => $this->t('Comma'),
        '\t' => $this->t('Tab'),
        '|' => $this->t('Pipe'),
      ],
      '#default_value' => ';',
      '#required' => TRUE
    ];
    $form['enclosure'] = [
      '#type' => 'select',
      '#title' => t('Text qualifier'),
      '#description' => t('Field enclosure character used in import file.'),
      '#options' => [
        '"' => '"',
        "'" => "'",
        '' => $this->t('None'),
      ],
      '#default_value' => '"'
    ];
    $form['header_row'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Header row'),
      '#description' => $this->t('Does the file contain a header row that should be skipped in the import?'),
      '#default_value' => FALSE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
  }

  /**
   * {@inheritdoc}
   */
  public function allowedExtensions() {
    return ['csv txt'];
  }

}
