<?php

namespace Drupal\jquery_colorpicker\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Base class for Color API Color Field Formatters.
 *
 * @deprecated as of Jquery Colorpicker update 8200. Will be removed in Jquery
 *   Colorpicker 8.x-3.x, and/or 9.x-1.x. Running
 *   jquery_colorpicker_update_8200() requires the existence formatters that,
 *   extend this class, however the field type is obsolete after that update has
 *   been run, so this will be removed.
 */
abstract class JqueryColorpickerDisplayFormatterBase extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary['overview'] = $this->t('Error: Please run update.php to solve this error.');

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $element[0]['color'] = [
      '#prefix' => '<p class="error">',
      '#suffix' => '</p>',
      '#markup' => $this->t('Error: Please run update.php to fix this error.'),
    ];

    return $element;
  }

}
