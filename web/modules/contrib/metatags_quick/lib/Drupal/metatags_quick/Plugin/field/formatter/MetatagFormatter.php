<?php

/**
 * @file
 * Definition of Drupal\metatags_quick\Plugin\field\formatter\MetatagFormatter.
 */

namespace Drupal\metatags_quick\Plugin\field\formatter;

use Drupal\field\Annotation\FieldFormatter;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Type\Formatter\FormatterBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Field\FieldInterface;

/**
 * Plugin implementation of the 'email_mailto' formatter.
 *
 * @FieldFormatter(
 *   id = "metatag_formatter_default",
 *   label = @Translation("Meta tag"),
 *   field_types = {
 *     "metatags_quick"
 *   }
 * )
 */
class MetatagFormatter extends FormatterBase {
  /**
   * Implements Drupal\field\Plugin\Type\Formatter\FormatterInterface::viewElements().
   */
  public function viewElements(FieldInterface $items) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $value = $item->getValue();
      _metatags_quick_add_head(array(
        'type' => 'meta',
        'name' => $this->fieldDefinition->settings['meta_name'],
        'content' => $value['value'],
        ));
    }

    return;
  }
}
