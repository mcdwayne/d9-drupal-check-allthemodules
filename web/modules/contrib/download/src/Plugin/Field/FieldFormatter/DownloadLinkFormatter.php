<?php /**
 * @file
 * Contains \Drupal\download\Plugin\Field\FieldFormatter\DownloadLinkFormatter.
 */

namespace Drupal\download\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Url;

/**
 * @FieldFormatter(
 *  id = "download_link_formatter",
 *  label = @Translation("Download link formatter"),
 *  field_types = {"download_link"}
 * )
 */
class DownloadLinkFormatter extends FormatterBase {

  public function viewElements(FieldItemListInterface $items, $langcode) {
    $output = array();
    $entity = $items->getEntity();

    foreach ($items as $delta => $item) {
      $element = array();
      $element['container'] = array(
        '#type' => 'container',
        '#attributes' => array(
          'class' => array('download_link')
        )
      );
      $valid_file_found = FALSE;
      $fname = NULL;
      if ($item->download_fields) {
        $fields = unserialize($item->download_fields);

        foreach ($fields as $fieldname) {
          $files = $entity->{$fieldname};
          if ($files instanceof FieldItemListInterface && !$files->isEmpty()) {
            foreach($files as $file) {
              $fileEntity = $file->entity;
              $uri = $fileEntity->getFileUri();
              if (file_valid_uri($uri)) {
                $valid_file_found = TRUE;
                $fname = $items->getName();
              }
            }
          }
        }
      }
      if ($valid_file_found) {
        $element['container']['value'] = array(
          '#type'   => 'link',
          '#title'    => $item->get('download_label')->getValue(),
          '#url' => Url::fromRoute('download.download', array(
            'bundle' => $entity->bundle(),
            'entity_type' => $entity->getEntityTypeId(),
            'fieldname' => $fname,
            'entity_id' => $entity->id(),
            'delta' => $delta,
          )),
        );

        $output[$delta] = $element;
      }
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $settings = $this->getSettings();

    $summary[] = t('Displays a link to download all files in selected fields.');

    return $summary;
  }

}
