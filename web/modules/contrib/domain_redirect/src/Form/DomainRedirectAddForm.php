<?php

/**
 * @file
 * Contains Drupal\domain_redirect\Form\DomainRedirectAddForm.
 */

namespace Drupal\domain_redirect\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Class DomainRedirectAddForm.
 *
 * Provides the add form for our domain redirect entity.
 *
 * @package Drupal\domain_redirect\Form
 *
 * @ingroup domain_redirect
 */
class DomainRedirectAddForm extends DomainRedirectFormBase {

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Create redirect');
    return $actions;
  }
}
