<?php

namespace Drupal\facets_custom_label\Plugin\facets\processor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;

/**
 * Provides a processor for FacetsCustomLabelProcessor.
 *
 * @FacetsProcessor(
 *   id = "facets_custom_label",
 *   label = @Translation("Facets custom label processor"),
 *   description = @Translation("Replaces the default label by a custom label for a specified raw or display value. Please make sure the facet processor order is correct."),
 *   stages = {
 *     "build" = 50
 *   }
 * )
 */
class FacetsCustomLabelProcessor extends ProcessorPluginBase implements BuildProcessorInterface {

  const SEPARATOR = '|';

  const ORIGIN__RAW = 'r';
  const ORIGIN__DISPLAY = 'd';

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results) {
    $replacementValues = $this->getConfiguration()['replacement_values'];

    // TODO
    // As a possible performance upgrade, we could save the result of the
    // mapping processing as a serialized value in cache.
    // Split per line.
    $replacementValues_rows = preg_split('/\r\n|\r|\n/', $replacementValues);
    $replacementValues_keyedByRawRows = [];
    $replacementValues_keyedByValueRows = [];

    foreach ($replacementValues_rows as $row) {
      // TODO
      // This currently does not support display values which contain pipes.
      if (substr_count($row, self::SEPARATOR) < 2) {
        // Need at least three parts in the row.
        continue;
      }

      // Get the type of origin.
      $origin = mb_strstr($row, self::SEPARATOR, TRUE);

      if (mb_strlen($origin) == 0) {
        // No origin flag set. Do nothing for this row.
        continue;
      }

      $remaining = mb_substr($row, mb_strlen($origin) + mb_strlen(self::SEPARATOR));
      $originalValue = mb_strstr($remaining, self::SEPARATOR, TRUE);
      $remaining = mb_substr($remaining, mb_strlen($originalValue) + mb_strlen(self::SEPARATOR));
      $newLabel = $remaining;

      if (mb_strpos($origin, self::ORIGIN__RAW) !== FALSE) {
        $replacementValues_keyedByRawRows[$originalValue] = $newLabel;
        continue;
      }

      if (mb_strpos($origin, self::ORIGIN__DISPLAY) !== FALSE) {
        $replacementValues_keyedByValueRows[$originalValue] = $newLabel;
        continue;
      }
    }

    /* @var \Drupal\facets\Result\Result $result */
    foreach ($results as $result) {
      if (isset($replacementValues_keyedByRawRows[$result->getRawValue()])) {
        $result->setDisplayValue($replacementValues_keyedByRawRows[$result->getRawValue()]);
        continue;
      }

      if (isset($replacementValues_keyedByValueRows[$result->getDisplayValue()])) {
        $result->setDisplayValue($replacementValues_keyedByValueRows[$result->getDisplayValue()]);
        continue;
      }
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    $config = $this->getConfiguration();

    $build['replacement_values'] = [
      '#title' => $this->t('Replacement values'),
      '#type' => 'textarea',
      '#default_value' => isset($config['replacement_values']) ? $config['replacement_values'] : '',
      '#description' => $this->t("Insert a replacement value on each row: <code>&lt;origin&gt;|&lt;value&gt;|&lt;new label&gt;</code>.<br />For example: <ul><li><code>r|article|Awesome news</code>: the <strong>r</strong> flag converts machine names (e.g.: content types IDs) or content IDs (node / term IDs, etc.).</li><li><code>d|Apple|New label of Apple term</code>: the <strong>d</strong> flag converts titles or names.</li></ul>"),
    ];

    return $build;
  }

}
