<?php

namespace Drupal\uc_country\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for country forms.
 */
class CountryForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $country = $this->entity;

    $form['#title'] = $this->t('Edit %country', ['%country' => $country->label()]);

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => $country->getName(),
    ];

    $form['address_format'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Address format'),
      '#default_value' => implode("\r\n", $country->getAddressFormat()),
      '#rows' => 7,
    ];

    $form['help'] = [
      '#type' => 'details',
      '#title' => $this->t('Address format variables'),
      '#collapsed' => TRUE,
    ];
    $form['help']['text'] = [
      '#theme' => 'table',
      '#header' => [$this->t('Variable'), $this->t('Description')],
      '#rows' => [
        ['!first_name', $this->t("Customer's first name")],
        ['!last_name', $this->t("Customer's last name")],
        ['!company', $this->t('Company name')],
        ['!street1', $this->t('First street address field')],
        ['!street2', $this->t('Second street address field')],
        ['!city', $this->t('City name')],
        ['!zone_name', $this->t('Full name of the zone')],
        ['!zone_code', $this->t('Abbreviation of the zone')],
        ['!postal_code', $this->t('Postal code')],
        ['!country_name', $this->t('Name of the country')],
        ['!country_code2', $this->t('2 digit country abbreviation')],
        ['!country_code3', $this->t('3 digit country abbreviation')],
      ],
      '#prefix' => '<p>' . $this->t('The following variables should be used in configuring addresses for the countries you ship to:') . '</p>',
      '#suffix' => '<p>' . $this->t('Adding _if to any country variable will make it display only for addresses whose country is different than the default store country.') . '</p>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['delete']['#access'] = FALSE;
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Save address_format as an array.
    $address_format = $form_state->getValue('address_format');
    $this->entity->setAddressFormat(explode("\r\n", $address_format));

    $this->entity->save();
    $this->messenger()->addMessage($this->t('Country settings saved.'));
    $form_state->setRedirect('entity.uc_country.collection');
  }

}
