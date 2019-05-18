<?php

namespace Drupal\download_file\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;


/**
 * Plugin implementation of the 'download_file' Formatter
 * @FieldFormatter(
 *   id = "direct_download" ,
 *   label = @Translation("Direct Download"),
 *   field_types = {
 *    "file"
 *   }
 * )
 */
class DirectDownloadFormatter extends FileFormatterBase {
    /**   extends FileFormatterBase
     * Builds a renderable array for a field value.
     *
     * @param \Drupal\Core\Field\FieldItemListInterface $items
     *   The field values to be rendered.
     * @param string $langcode
     *   The language that should be used to render the field.
     *
     * @return array
     *   A renderable array for $items, as an array of child elements keyed by
     *   consecutive numeric indexes starting from 0.
     */
    public function viewElements(FieldItemListInterface $items, $langcode)
    {
        $elements = array();

        foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
            $item = $file->_referringItem;
            $id = $file->id();
            $link_text = !empty($item->description) ? $item->description : $file->getFilename();
            $elements[$delta] = [
                '#theme' => 'direct_download_file_link',
                '#link_text' => $link_text,
                '#file_id' => $id,
                '#cache' => [
                    'tags' => $file->getCacheTags(),
                ],
            ];

            if (isset($item->_attributes)) {
                $elements[$delta] += ['#attributes' => []];
                $elements[$delta]['#attributes'] += $item->_attributes;
                unset($item->_attributes);
            }
        }

        return $elements;

    }

}