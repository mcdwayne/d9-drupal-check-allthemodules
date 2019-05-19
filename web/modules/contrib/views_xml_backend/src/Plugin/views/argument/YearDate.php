<?php

/**
 * @file
 * Contains \Drupal\views_xml_backend\Plugin\views\argument\YearDate.
 */

namespace Drupal\views_xml_backend\Plugin\views\argument;

use Drupal\views_xml_backend\Xpath;

/**
 * Argument handler for a year (CCYY).
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("views_xml_backend_date_year")
 */
class YearDate extends Date {

  /**
   * {@inheritdoc}
   */
  protected $argFormat = 'Y';

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    $xpath = $this->options['xpath_selector'];
    $value = (int) $this->getValue();
    $format = Xpath::escapeXpathString($this->argFormat);

    return "php:functionString('views_xml_backend_format_value', $xpath, $format) = $value";
  }

}
