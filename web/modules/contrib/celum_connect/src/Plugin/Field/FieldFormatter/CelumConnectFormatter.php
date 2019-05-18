<?php

namespace Drupal\celum_connect\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'celum_connect' formatter.
 *
 * @FieldFormatter(
 *   id = "celum_connect_formatter",
 *   label = @Translation("Celum:connect default"),
 *   field_types = {
 *     "celum_connect_field"
 *   }
 * )
 */
class CelumConnectFormatter extends FormatterBase
{
    /**
     * {@inheritdoc}
     */
    public function viewElements(FieldItemListInterface $items, $langcode) {
        $elements = array();
        foreach ($items as $delta => $item) {
            $element = array(
                '#theme' => 'celum_connect',
                '#fileCategory' => $item->fileCategory,
                '#title' => $item->title,
                '#version' => $item->version,
                '#id' => $item->id,
                '#downloadFormat' => $item->downloadFormat,
                '#fileExtension' => $item->fileExtension,
                '#uri' => file_create_url($item->uri),
                '#thumb' => file_create_url($item->thumb),

            );
            $element['#attached']['library'][] = 'celum_connect/celum-connect-renderer';
            $elements[$delta] = $element;
        }

        return $elements;
    }

}
