<?php

namespace Drupal\search_api_synonym\Plugin\search_api_synonym\import;

use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\search_api_synonym\Import\ImportPluginBase;
use Drupal\search_api_synonym\Import\ImportPluginInterface;

/**
 * Import of Solr synonyms.txt files.
 *
 * @SearchApiSynonymImport(
 *   id = "solr",
 *   label = @Translation("Solr"),
 *   description = @Translation("Synonym import plugin from Solr synonyms.txt file.")
 * )
 */
class Solr extends ImportPluginBase implements ImportPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function parseFile(File $file, array $settings = []) {
    $data = [];

    // Read file.
    $rows = file($file->getFileUri());

    if (is_array($rows)) {
      foreach ($rows as $row) {
        $row = trim($row);

        // Skip comment lines
        if (empty($row) || substr($row, 0, 1) == '#') {
          continue;
        }

        $parts = explode('=>', $row);

        // Spelling error.
        if (count($parts) == 2) {
          $data[] = [
            'word' => trim($parts[0]),
            'synonym' => trim($parts['1']),
            'type' => 'spelling_error'
          ];
        }
        // Synonym.
        else {
          $data[] = [
            'word' => trim(substr($row, 0, strpos($row, ','))),
            'synonym' => trim(substr($row, strpos($row, ',') + 1)),
            'type' => 'synonym'
          ];
        }
      }
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $example_url = 'internal:' . base_path() . drupal_get_path('module', 'search_api_synonym') . '/examples/solr_synonyms.txt';
    $form['template'] = [
      '#type' => 'item',
      '#title' => $this->t('Example'),
      '#markup' => Link::fromTextAndUrl(t('Download example file'), Url::fromUri($example_url))->toString()
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function allowedExtensions() {
    return ['txt'];
  }

}
