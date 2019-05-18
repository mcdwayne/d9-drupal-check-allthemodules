<?php

namespace Drupal\nice_filemime\Plugin\facets\processor;

use Drupal\facets\FacetInterface;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;

/**
 * Provides human friendly MIME type description.
 *
 * @FacetsProcessor(
 *   id = "nice_filemime_processor",
 *   label = @Translation("Nice File MIME"),
 *   description = @Translation("Provides human friendly MIME type description."),
 *   stages = {
 *     "build" = 50
 *   }
 * )
 */
class NiceFileMimeProcessor extends ProcessorPluginBase implements BuildProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results) {
    // Only works on filemime field.
    if ($facet->getFieldIdentifier() == 'filemime') {
      // Step over the results.
      foreach ($results as $result) {
        // Original mime.
        $filemime = $result->getDisplayValue();
        // NiceFileMime.
        $niceFileMime = \Drupal::service('nice_filemime.filemime')->getNiceFileMime($filemime);
        // Replace display value.
        $result->setDisplayValue($niceFileMime);
      }
      // Done.
      return $results;
    }
  }

}