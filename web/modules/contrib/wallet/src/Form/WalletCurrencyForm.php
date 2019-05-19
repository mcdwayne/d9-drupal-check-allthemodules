<?php

namespace Drupal\wallet\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\Language;

/**
 * Form controller for the wallet_currency entity edit forms.
 *
 * @ingroup wallet_currency
 */
class WalletCurrencyForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;
    $form['langcode'] = array('#title' => $this->t('Language'), '#type' => 'language_select', '#default_value' => $entity->getUntranslated()->language()->getId(), '#languages' => Language::STATE_ALL,);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $form_state->setRedirect('view.list_currency.page_1');
    $entity = $this->getEntity();
    $entity->save();

  }

}
