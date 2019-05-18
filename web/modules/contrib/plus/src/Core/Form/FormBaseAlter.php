<?php

namespace Drupal\plus\Core\Form;

use Drupal\plus\Plugin\ThemePluginBase;
use Drupal\plus\Utility\Element;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base form alter class.
 *
 * @ingroup plugins_form
 */
class FormBaseAlter extends ThemePluginBase implements FormAlterInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(Element $form, FormStateInterface $form_state, $form_id = NULL) {
  }

}
