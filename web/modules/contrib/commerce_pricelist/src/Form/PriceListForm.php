<?php

namespace Drupal\commerce_pricelist\Form;

use Drupal\Core\Link;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

class PriceListForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\commerce_pricelist\Entity\PriceList */
    $store_query = $this->entityManager->getStorage('commerce_store')->getQuery();
    if ($store_query->count()->execute() == 0) {
      $link = Link::createFromRoute('Add a new store.', 'entity.commerce_store.add_page');
      $form['warning'] = [
        '#markup' => t("Price lists can't be created until a store has been added. @link", ['@link' => $link->toString()]),
      ];
      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_pricelist\Entity\PriceListInterface $price_list */
    $price_list = $this->entity;

    $form = parent::form($form, $form_state);
    $form['#tree'] = TRUE;
    $form['#theme'] = ['commerce_pricelist_form'];
    $form['#attached']['library'][] = 'commerce_pricelist/form';

    $form['advanced'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['entity-meta']],
      '#weight' => 99,
    ];
    $form['option_details'] = [
      '#type' => 'container',
      '#title' => $this->t('Options'),
      '#group' => 'advanced',
      '#attributes' => ['class' => ['entity-meta__header']],
      '#weight' => -100,
    ];
    $form['date_details'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Dates'),
      '#group' => 'advanced',
    ];

    $field_details_mapping = [
      'status' => 'option_details',
      'weight' => 'option_details',
      'start_date' => 'date_details',
      'end_date' => 'date_details',
    ];
    foreach ($field_details_mapping as $field => $group) {
      if (isset($form[$field])) {
        $form[$field]['#group'] = $group;
      }
    }

    // Hide the customer/customer_roles fields behind a set of radios, to
    // emphasize that they are mutually exclusive.
    $default_value = 'everyone';
    if ($price_list->getCustomerId()) {
      $default_value = 'customer';
    }
    elseif ($price_list->getCustomerRoles()) {
      $default_value = 'customer_roles';
    }
    $form['customer_eligibility'] = [
      '#type' => 'radios',
      '#title' => $this->t('Customer eligibility'),
      '#options' => [
        'everyone' => $this->t('Everyone'),
        'customer' => $this->t('Specific customer'),
        'customer_roles' => $this->t('Customer roles'),
      ],
      '#default_value' => $default_value,
      '#weight' => 10,
    ];
    $form['customer']['widget'][0]['target_id']['#states']['visible'] = [
      'input[name="customer_eligibility"]' => ['value' => 'customer'],
    ];
    $form['customer_roles']['widget']['#states']['visible'] = [
      'input[name="customer_eligibility"]' => ['value' => 'customer_roles'],
    ];
    // Remove the '- None -' option from the customer roles dropdown.
    if ($form['customer_roles']['widget']['#type'] == 'select') {
      unset($form['customer_roles']['widget']['#options']['_none']);
    }
    // EntityFormDisplay::processForm() overwrites any widget #weight set
    // here, so the new weights must be assigned in a #process of our own.
    $form['#process'][] = [get_class($this), 'modifyCustomerFieldWeights'];

    return $form;
  }

  /**
   * Process callback: assigns new weights to customer fields.
   */
  public static function modifyCustomerFieldWeights($element, FormStateInterface $form_state, $form) {
    $element['customer']['#weight'] = 11;
    $element['customer_roles']['#weight'] = 11;

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    if ($this->entity->isNew()) {
      $actions['submit_continue'] = [
        '#type' => 'submit',
        '#value' => $this->t('Save and add prices'),
        '#continue' => TRUE,
        '#submit' => ['::submitForm', '::save'],
      ];
    }

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_pricelist\Entity\PriceListInterface $price_list */
    $price_list = $this->entity;
    // Don't persist customer values that are not going to be used.
    $customer_eligibility = $form_state->getValue('customer_eligibility');
    if ($customer_eligibility == 'everyone') {
      $price_list->setCustomerId(NULL);
      $price_list->setCustomerRoles([]);
    }
    elseif ($customer_eligibility == 'customer') {
      $price_list->setCustomerRoles([]);
    }
    elseif ($customer_eligibility == 'customer_roles') {
      $price_list->setCustomerId(NULL);
    }
    $price_list->save();
    $this->messenger()->addMessage($this->t('Saved the %label price list.', ['%label' => $price_list->label()]));

    if (!empty($form_state->getTriggeringElement()['#continue'])) {
      $form_state->setRedirect('entity.commerce_pricelist_item.collection', ['commerce_pricelist' => $price_list->id()]);
    }
    else {
      $form_state->setRedirect('entity.commerce_pricelist.collection');
    }
  }

}
