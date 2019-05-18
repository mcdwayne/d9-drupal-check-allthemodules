<?php

namespace Drupal\entity_content_export;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Url;
use Drupal\entity_content_export\Form\EntityContentExport;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Define batch entity export.
 */
class BatchEntityExport {

  /**
   * Serialize entity structured data.
   *
   * @param $format
   * @param $entity_type
   * @param array $entity_ids
   * @param \Drupal\entity_content_export\Form\EntityContentExport $content_export
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   * @param array $context
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function serializeEntityStructuredData(
    $format,
    $entity_type,
    array $entity_ids,
    EntityContentExport $content_export,
    EntityViewDisplayInterface $display,
    array &$context
  ) {
    $serializer = $content_export->getSerializer();
    $structure = $content_export->buildEntityExportDataStructure(
      $entity_type, $entity_ids, $display
    );

    // Alter the structure of the array based on the serializer format.
    switch ($format) {
      case 'csv' :
        $structure = array_values($structure);
        break;
    }
    $dir = file_directory_temp();

    $content = $serializer->serialize($structure, $format);
    $filename = "{$dir}/entity-content-export-{$entity_type}.{$format}";

    $context['results']['file'] = file_unmanaged_save_data(
      $content, $filename, FILE_EXISTS_REPLACE
    );
  }

  /**
   * Batch entity export finish callback.
   *
   * @param $success
   * @param $results
   * @param $operations
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public static function finishedCallback($success, $results, $operations) {
    $redirect_url = Url::fromRoute('entity_content_export.download.results', [
      'results' => $results
    ])->toString();

    return new RedirectResponse($redirect_url);
  }
}
