<?php

namespace Drupal\entity_pdf\Controller;

use Drupal\Core\Entity\Controller\EntityViewController;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Drupal\Core\Entity\EntityInterface;
use Mpdf\Output\Destination;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defines a controller to render a single entity.
 */
class PdfEntityController extends EntityViewController {

  /**
   * Public function view.
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    global $base_url;

    $build = [
      '#theme' => 'htmlpdf',
      '#title' => $entity->label(),
      '#content' => parent::view($entity, $view_mode, $langcode),
      '#base_url' => $base_url,
    ];

    $output = render($build);

    // If you want the test HTML output, uncomment this:
    // return new Response(render($build), 200, []);

    // Get the filename from config and replace tokens.
    $configFactory = \Drupal::service('config.factory');
    $config = $configFactory->get('entity_pdf.settings');
    $filename = \Drupal::token()->replace($config->get('filename'), [ $entity->getEntityTypeId() => $entity ], ['langcode' => $langcode]);

    // Get mpdf's default config and allow other modules to alter it.
    $mpdf_config = [];
    $mpdf_config['tempDir'] = DRUPAL_ROOT . '/sites/default/files/entity_pdf';
    $defaultConfig = (new ConfigVariables())->getDefaults();
    $mpdf_config['fontDir'] = $defaultConfig['fontDir'];
    $defaultFontConfig = (new FontVariables())->getDefaults();
    $mpdf_config['fontdata'] = $defaultFontConfig['fontdata'];
    \Drupal::moduleHandler()->alter('mpdf_config', $mpdf_config);

    // Build and return the pdf.
    $mpdf = new Mpdf($mpdf_config);
    $mpdf->SetBasePath(\Drupal::request()->getSchemeAndHttpHost());
    $mpdf->SetTitle($filename);
    $mpdf->WriteHTML($output);
    $content = $mpdf->Output($filename, Destination::STRING_RETURN);
    $headers = [
      'Content-Type' => 'application/pdf',
      'Content-disposition' => 'attachment; filename="' . $filename . '"',
    ];

    return new Response($content, 200, $headers);
  }

  /**
   * Public function title.
   *
   * @inheritdoc
   */
  public function title(EntityInterface $entity) {
    return $entity->label();
  }

}
