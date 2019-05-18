<?php

namespace Drupal\content_synchronizer\Controller;

use Drupal\content_synchronizer\Processors\BatchExportProcessor;
use Drupal\content_synchronizer\Processors\ExportEntityWriter;
use Drupal\content_synchronizer\Service\ArchiveDownloader;
use Drupal\content_synchronizer\Service\EntityExportFormBuilder;
use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class QuickExportController.
 *
 * @package Drupal\content_synchronizer\Controller
 */
class QuickExportController {

  protected $url = '/admin/content';

  /**
   * Launch quick export batch.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   THe response.
   */
  public function quickExport(Request $request) {

    // Get the destination url.
    $this->url = $request->query->has('destination') ? $request->query->get('destination') : Url::fromRoute('system.admin_content')
      ->toString();

    if ($request->query->has('entityTypeId') && $request->query->has('entityId')) {
      /** @var Drupal\Core\Entity\EntityInterface $entity */
      $entity = \Drupal::entityTypeManager()
        ->getStorage($request->query->get('entityTypeId'))
        ->load($request->query->get('entityId'));

      $writer = new ExportEntityWriter();
      $writer->initFromId($entity->getEntityTypeId() . '.' . $entity->id());

      $batchExportProcessor = new BatchExportProcessor($writer);

      if ($entity instanceof ConfigEntityBundleBase) {
        $batchExportProcessor->exportEntities(EntityExportFormBuilder::getEntitiesFromBundle($entity), [
          $this,
          'onBatchEnd'
        ]);
      }
      else {
        $batchExportProcessor->exportEntities([$entity], [$this, 'onBatchEnd']);
      }

      return batch_process('');
    }

    return new RedirectResponse($this->url);
  }

  /**
   * On batch end redirect to the form url.
   *
   * @param string $archiveUri
   *   THe archive to download.
   */
  public function onBatchEnd($archiveUri) {
    \Drupal::service(ArchiveDownloader::SERVICE_NAME)
      ->redirectWithArchivePath($this->url, $archiveUri);
  }

}
