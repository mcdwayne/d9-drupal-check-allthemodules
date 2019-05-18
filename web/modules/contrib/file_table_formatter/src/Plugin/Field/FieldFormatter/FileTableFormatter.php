<?php 

namespace Drupal\file_table_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Field\Annotation\FieldFormatter;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;
use Drupal\file_table_formatter\FileTableData;
use Drupal\file_table_formatter\FileTableFormatterManager;

/**
 * @FieldFormatter(
 *  id = "file_table_formatter",
 *  label = @Translation("Display file contents as table"),
 *  field_types = {"file"}
 * )
 */
class FileTableFormatter extends FileFormatterBase {
  
  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'header' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements['header'] = [
      '#type'           => 'checkbox',
      '#title'          => t('Files include a header row'),
      '#description'    => t('If checked first row will be displayed formatted as a table header'),
      '#default_value'  => $this->getSetting('header'),
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $includes_header = $this->getSetting('header');
    $summary[] = t('Files include a header row: @header', [
      '@header' => empty($includes_header) ? t('No') : t('Yes'),
    ]);
    return $summary;
  }
  
  
  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    
    if ($files = $this->getEntitiesToView($items, $langcode)) {
      $includes_header = $this->getSetting('header');
      $processor = \Drupal::service('file_table_formatter.processor');
      foreach ($files as $delta => $file) {
        $table_data = $processor->getTableDataFromFile($file, $includes_header);
        $elements[$delta] = [
          '#theme' => 'file_table_formatter_table',
          '#data' => $table_data,
          '#datatables' => FALSE,
        ];
      }
    }
    return $elements;
  }
  
}
