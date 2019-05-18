<?php

namespace Drupal\drd\Plugin\field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\BooleanFormatter;

/**
 * A handler to provide a field formatter for security status.
 *
 * @FieldFormatter(
 *   id = "drd_domain_secure",
 *   label = @Translation("SSL yes-no"),
 *   field_types = {
 *     "boolean",
 *   }
 * )
 */
class Secure extends BooleanFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings['format'] = 'ssl-yes-no';
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  protected function getOutputFormats() {
    $formats['ssl-yes-no'] = [t('<div class="drd-ssl yes">on</div>'), t('<div class="drd-ssl no">off</div>')];
    return $formats;
  }

}
