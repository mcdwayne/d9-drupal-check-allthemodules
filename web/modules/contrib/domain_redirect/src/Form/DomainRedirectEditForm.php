<?php

/**
 * @file
 * Contains Drupal\domain_redirect\Form\DomainRedirectEditForm.
 */

namespace Drupal\domain_redirect\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Class DomainRedirectEditForm
 *
 * Provides the edit form for the domain redirect entity.
 *
 * @package Drupal\domain_redirect\Form
 *
 * @ingroup domain_redirect
 */
class DomainRedirectEditForm extends DomainRedirectFormBase {

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Update redirect');
    return $actions;
  }
}
