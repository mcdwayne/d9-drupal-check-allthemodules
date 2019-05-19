<?php

/**
 * @file
 * Provides Drupal\themekey\OperatorBase.
 */

namespace Drupal\themekey;

use Drupal\themekey\Plugin\SingletonPluginBase;
use Drupal\themekey\PropertyAdminInterface;
use Drupal\Core\Form\FormStateInterface;

abstract class OperatorBase extends SingletonPluginBase implements OperatorInterface {

  public function getName() {
    return $this->pluginDefinition['name'];
  }

  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function validate(PropertyAdminInterface $propertyAdmin, $value, FormStateInterface $form_state) {
    if ($possibleValues = $propertyAdmin->getPossibleValues()) {
      if (!in_array($value, $possibleValues)) {
        $form_state->setErrorByName('value',
          $this->t('Value needs to be one of %values',
            implode(', ', $possibleValues)
          )
        );
      }
    }
  }

}

