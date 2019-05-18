<?php

namespace Drupal\pdf_download\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\pdf_download\Controller\PdfDownloadController;

/**
 * PDF Download.
 *
 * @Action(
 *   id = "pdf_download",
 *   label = @Translation("PDF Download"),
 *   type = "node"
 * )
 */
class PdfDownload extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    /** @var \Drupal\taxonomy\TermInterface $entity */
    $entity_type_id = 'node';
    $bundle = $entity->bundle();
    $entity_arr['Title'] = $entity->get('title')->getString();
    foreach (\Drupal::entityManager()->getFieldDefinitions($entity_type_id, $bundle) as $field_name => $field_definition) {
      if($field_definition->getType() == "text_with_summary") {
        $entity_arr[''.$field_definition->getLabel()] = $entity->get($field_name)->getvalue()[0]['value'];
      }
      elseif ($field_definition->getType() == "entity_reference" && !empty($entity->get($field_name)->getString())) {
        if (!empty($field_definition->getTargetBundle())) {
          $t_value = \Drupal\taxonomy\Entity\Term::load($entity->get($field_name)->getString());
          $entity_arr[''.$field_definition->getLabel()] = $t_value->getName();
        }
      }
      else {
        $entity_arr[''.$field_definition->getLabel()] = $entity->get($field_name)->getString();
      }
    }
    $statistics = new PdfDownloadController;
    $result = $statistics->downloadPdf($entity_arr);
    print_r($result);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\taxonomy\TermInterface $object */
    return TRUE;
  }

}
