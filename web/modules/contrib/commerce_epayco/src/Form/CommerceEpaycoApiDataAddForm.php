<?php

namespace Drupal\commerce_epayco\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the add form for our CommerceEpaycoApiData entity.
 *
 * @ingroup commerce_epayco
 */
class CommerceEpaycoApiDataAddForm extends CommerceEpaycoApiDataFormBase {

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save new API data');

    return $actions;
  }

}
