<?php

namespace Drupal\ics_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Url;
use Drupal\file\Entity\File;

/**
 * Plugin implementation of the 'calendar_download_default_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "calendar_download_default_formatter",
 *   label = @Translation("Calendar download default formatter"),
 *   field_types = {
 *     "calendar_download_type"
 *   }
 * )
 */
class CalendarDownloadDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   * @throws \InvalidArgumentException
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = $this->viewValue($item);
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return mixed[]|null
   *
   * @throws \InvalidArgumentException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   *   A render array for a link element.
   */
  protected function viewValue(FieldItemInterface $item) {
    $fileRef = $item->get('fileref')->getValue();
    $file = File::load($fileRef);//TODO - once formatter classes get container access replace with DI
    if ($file) {
      $fileUrlObj = Url::fromUri(file_create_url($file->getFileUri()));
      $build = [
        '#type'  => 'link',
        '#title' => $this->t('iCal Download'),
        '#url'   => $fileUrlObj,
      ];
      return $build;
    }
    return NULL;
  }

}
