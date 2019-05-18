<?php

namespace Drupal\rename_admin_paths\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\rename_admin_paths\EventSubscriber\RenameAdminPathEventSubscriber;

class RenameAdminPathsCallbacks {

  use StringTranslationTrait;

  /**
   * Form element validation handler for 'name' in form_test_validate_form().
   *
   * @param $element
   *   The field element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function validatePath(&$element, FormStateInterface $form_state) {
    if (empty($element['#value'])) {
      $form_state->setError($element, $this->t('Path replacement value must contain a value.'));
    }
    elseif (!$this->isValidPath($element['#value'])) {
      $form_state->setError($element, $this->t('Path replacement value must contain only letters, numbers, hyphens and underscores.'));
    }
    elseif ($this->isDefaultPath($element['#value'])) {
      $form_state->setError($element, sprintf($this->t('Renaming to a default name (%s) is not allowed.'),
        implode(', ', RenameAdminPathEventSubscriber::DEFAULT_ADMIN_PATHS)));
    }
  }

  /**
   * Force path replacement values to contain only lowercase letters, numbers, and underscores.
   *
   * @param string $value
   *
   * @return boolean
   */
  private function isValidPath($value) {
    return (bool) preg_match('~^[a-zA-Z0-9_-]+$~', $value);
  }

  /**
   * Verifiy users not overwriting with the default path names, could lead to broken routes
   *
   * @param string $value
   *
   * @return bool
   */
  private function isDefaultPath($value) {
    return in_array(strtolower($value), RenameAdminPathEventSubscriber::DEFAULT_ADMIN_PATHS);
  }
}
