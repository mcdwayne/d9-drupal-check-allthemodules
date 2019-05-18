<?php

namespace Drupal\ldadmin\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class JsonLDMappingForm.
 *
 * @package Drupal\ldadmin\Form
 */
class JsonLDMappingForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $jsonld_mapping = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#maxlength' => 255,
      '#default_value' => $jsonld_mapping->label(),
      '#description' => $this->t("Description for the JSON-LD Mapping."),
      '#required' => TRUE,
    ];

    $form['nid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Node NID'),
      '#maxlength' => 255,
      '#default_value' => $jsonld_mapping->getNid(),
      '#description' => $this->t("Node ID to which this JSON will be linked."),
      '#required' => TRUE,
    ];

    $form['json'] = [
      '#type' => 'textarea',
      '#title' => $this->t('JSON LD Data'),
      '#default_value' => $jsonld_mapping->getJson(),
      '#description' => $this->t("The JSON LD data string."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $jsonld_mapping->id(),
      '#machine_name' => [
        'exists' => '\Drupal\ldadmin\Entity\JsonLDMapping::load',
      ],
      '#disabled' => !$jsonld_mapping->isNew(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $jsonld_mapping = $this->entity;
    $jsonld_mapping->setNid($form_state->getValue('nid'));
    $jsonld_mapping->setJson($form_state->getValue('json'));
    $status = $jsonld_mapping->save();
    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label JSON-LD Mapping.', [
          '%label' => $jsonld_mapping->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label JSON-LD Mapping.', [
          '%label' => $jsonld_mapping->label(),
        ]));
    }
    $form_state->setRedirectUrl($jsonld_mapping->toUrl('collection'));
  }

}
