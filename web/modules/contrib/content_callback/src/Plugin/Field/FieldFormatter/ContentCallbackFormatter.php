<?php

/**
 * @file
 * Contains \Drupal\content_callback\Plugin\field\formatter\ContentCallbackFormatter.
 */

namespace Drupal\content_callback\Plugin\Field\FieldFormatter;

use Drupal\content_callback\Plugin\ContentCallbackInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'content_callback_default' formatter.
 *
 * @FieldFormatter(
 *   id = "content_callback_default",
 *   module = "content_callback",
 *   label = @Translation("Content callback"),
 *   field_types = {
 *     "content_callback"
 *   }
 * )
 */
class ContentCallbackFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $manager = \Drupal::service('plugin.manager.content_callback');

    $element = array('#cache' => array());
    foreach ($items as $delta => $item) {
      if ($item->value !== '_none') {
        $definition = $manager->getDefinition($item->value);

        if (!empty($definition) && class_exists($definition['class'])) {
          $content_callback = $manager->createInstance($item->value);
          $options =  $item->options;
          /** @var $content_callback ContentCallbackInterface */
          $element[$delta] = $content_callback->render($options);

          // When available include cache settings.
          if (isset($element[$delta]['#cache'])) {
            $element['#cache'] = array_merge($element['#cache'], $element[$delta]['#cache']);
            unset($element[$delta]['#cache']);
          }
        }
      }
    }

    return $element;
  }

}
