<?php /**
 * @file
 * Contains \Drupal\download\Controller\DefaultController.
 */

namespace Drupal\download\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\field\Entity\FieldConfig;
use Drupal\file\Entity\File;
use PclZip;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * Default controller for the download module.
 */
class DefaultController extends ControllerBase {

  public function download_download($bundle, $entity_type, $fieldname, $entity_id, $delta) {

    $field_name = 'download';
    $files = [];
    $entity_storage = \Drupal::entityTypeManager()->getStorage($entity_type, [$entity_id]);
    $entity = $entity_storage->load($entity_id);

    if (!class_exists('PclZip')) {
      throw new ServiceUnavailableHttpException();
    }

    $instances = $entity->getFieldDefinitions();
    $filename = $entity_type . '-' . $entity_id . '-' . $delta;

    foreach ($instances as $instance) {
      if ($instance instanceof FieldConfig && $instance->getType() == 'download_link' && $instance->getName() == $fieldname) {
        $field_name = $instance->getName();

        if (!empty($instance->getSettings()['download_filename'])) {
          $filename = $this->_download_get_filename($instance->getSettings()['download_filename'], $entity, $entity_type);
        }
      }
    }

    $fields = $entity->get($field_name);
    foreach($fields as $field) {
      $fieldnames = $fields = unserialize($field->download_fields);
    }

    foreach ($fieldnames as $fieldname) {
      if ($fieldname) {
        foreach ($entity->get($fieldname) as $field_obj) {
          if ($field_obj instanceof EntityReferenceItem) {
            // TODO need to get the actual file entity somehow !!!
            $file_entity_info = $field_obj->getValue();
            $file_entity = File::load($file_entity_info['target_id']);
            $files[] = \Drupal::service("file_system")->realpath($file_entity->getFileUri());
          }
        }
      }
    }

    $filename = $filename . '.zip';
    $tmp_file = file_save_data('', 'temporary://' . $filename);
    $tmp_file->status = 0;
    $tmp_file->save();
    $archive = new PclZip(\Drupal::service("file_system")->realpath($tmp_file->getFileUri()));
    $archive->add($files, PCLZIP_OPT_REMOVE_ALL_PATH);

    \Drupal::moduleHandler()->invokeAll('download_download', [$files, $entity]);

    header("Content-Type: application/force-download");
    header('Content-Description: File Transfer');
    header('Content-Disposition: inline; filename=' . $filename);
    readfile(\Drupal::service("file_system")->realpath($tmp_file->getFileUri()));
    exit();
  }

  private function _download_get_filename($filename, $entity, $entity_type) {

    $fn = \Drupal::token()->replace(\Drupal\Component\Utility\Html::escape($filename), array($entity_type => $entity));
    $fn = preg_replace("/[^a-zA-Z0-9\.\-_]/", "", $fn);

    return $fn;
  }

}
