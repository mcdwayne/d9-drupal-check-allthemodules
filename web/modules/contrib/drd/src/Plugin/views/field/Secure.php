<?php

namespace Drupal\drd\Plugin\views\field;

use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\Boolean;
use Drupal\views\ViewExecutable;

/**
 * A handler to provide a field formatter for security status.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("drd_domain_secure")
 */
class Secure extends Boolean {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['type']['default'] = 'drd-secure';
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    $this->definition['output formats']['drd-secure'] = [t('<div class="drd-ssl yes">on</div>'), t('<div class="drd-ssl no">off</div>')];
    parent::init($view, $display, $options);
  }

}
