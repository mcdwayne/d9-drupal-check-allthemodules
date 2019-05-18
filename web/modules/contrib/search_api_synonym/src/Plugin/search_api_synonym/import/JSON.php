<?php

namespace Drupal\search_api_synonym\Plugin\search_api_synonym\import;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Json AS SerializationJSON;
use Drupal\file\Entity\File;
use Drupal\search_api_synonym\Import\ImportPluginBase;
use Drupal\search_api_synonym\Import\ImportPluginInterface;

/**
 * Import of JSON files.
 *
 * @SearchApiSynonymImport(
 *   id = "json",
 *   label = @Translation("JSON"),
 *   description = @Translation("Synonym import plugin from JSON file.")
 * )
 */
class JSON extends ImportPluginBase implements ImportPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function parseFile(File $file, array $settings = []) {
    $data = [];
    $json = file_get_contents($file->getFileUri());

    if ($items = SerializationJSON::decode($json)) {
      foreach ($items as $item) {
        if (!empty($item['word']) && !empty($item['synonym'])) {
          $data[] = [
            'word' => $item['word'],
            'synonym' => $item['synonym'],
            'type' => !empty($item['type']) ? $item['type'] : ''
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
    $example_url = 'internal:' . base_path() . drupal_get_path('module', 'search_api_synonym') . '/examples/example.json';
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
    return ['json'];
  }

}
