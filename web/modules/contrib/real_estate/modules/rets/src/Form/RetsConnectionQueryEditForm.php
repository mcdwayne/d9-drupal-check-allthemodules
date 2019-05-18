<?php

namespace Drupal\real_estate_rets\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class RetsConnectionQueryEditForm.
 */
class RetsConnectionQueryEditForm extends EntityForm {

  /**
   * The ID of the query that is being edited.
   *
   * @var string
   */
  protected $queryId;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'connect_query_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $connection_query = NULL) {
    $this->queryId = $connection_query;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /* @var \Drupal\real_estate_rets\RetsConnectionInterface $connect */
    $connect = $this->getEntity();
    $query = $connect->getQuery($this->queryId);
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $query->label(),
      '#description' => $this->t('Label for the query.'),
      '#required' => TRUE,
    ];
    $form['resource'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Resource'),
      '#maxlength' => 255,
      '#default_value' => $query->resource(),
      '#description' => $this->t('Resource for the query.'),
      '#required' => TRUE,
    ];
    $form['class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Class'),
      '#maxlength' => 255,
      '#default_value' => $query->class(),
      '#description' => $this->t('Class for the query.'),
      '#required' => TRUE,
    ];
    $form['dmql'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Dmql'),
      '#maxlength' => 255,
      '#default_value' => $query->dmql(),
      '#description' => $this->t('Default value: DMQL2 .'),
      '#required' => TRUE,
    ];
    $form['query'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Query'),
      '#maxlength' => 255,
      '#default_value' => $query->query(),
      '#description' => $this->t('Ex.: "(DATE_MODIFIED=2018-07-01-2020-08-01),(PHOTO_COUNT=1-5)" (without quotes)'),
      '#required' => TRUE,
    ];
    $form['format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Format'),
      '#maxlength' => 255,
      '#default_value' => $query->format(),
      '#description' => $this->t('Default value: COMPACT . Possible values: COMPACT, COMPACT-DECODED, ..etc.'),
      '#required' => TRUE,
    ];
    $form['limit'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Limit'),
      '#maxlength' => 255,
      '#default_value' => $query->limit(),
      '#description' => $this->t('Limit.'),
      '#required' => TRUE,
    ];
    $form['standardnames'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Standard Names'),
      '#maxlength' => 25,
      '#default_value' => $query->standardnames(),
      '#description' => $this->t('Default value: 0 . Possible values: 0, 1 etc.'),
      '#required' => TRUE,
    ];
    $form['mapping'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Mapping'),
    ];
    $form['mapping']['entity'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Entity'),
      '#maxlength' => 255,
      '#default_value' => $query->entity(),
      '#description' => $this->t('Entity.'),
      '#required' => TRUE,
    ];
    $form['mapping']['key_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Key Field'),
      '#maxlength' => 255,
      '#default_value' => $query->keyField(),
      '#description' => $this->t('Key Field.'),
      '#required' => TRUE,
    ];
    $form['mapping']['select'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Select'),
      '#maxlength' => 255,
      '#default_value' => $query->select(),
      '#description' => $this->t('Mapped fields list for downloading. A record 
        must look like: "SomeRetsField:field_drupal,SomeRetsField_1:field_drupal_1" 
        (without quotes). Every pair can be placed in a new line. For ex.:
        BuildingProjectName:field_building_name,
        StreetNume:field_street,
        ListPrice:field_price'),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'value',
      '#value' => $this->queryId,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\real_estate_rets\RetsConnectionInterface $connect */
    // $connect = $this->getEntity();
    // $values = $form_state->getValues();
    // todo: add validation.
  }

  /**
   * Copies top-level form values to entity properties.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the current form should operate upon.
   * @param array $form
   *   A nested array of form elements comprising the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    if (!$form_state->isValidationComplete()) {
      // Only do something once form validation is complete.
      return;
    }
    /** @var \Drupal\real_estate_rets\RetsConnectionInterface $entity */
    $values = $form_state->getValues();
    $form_state->set('created_query', FALSE);
    $entity->setQueryLabel($values['id'], $values['label']);
    $entity->setQueryResource($values['id'], $values['resource']);
    $entity->setQueryClass($values['id'], $values['class']);
    $entity->setQueryQuery($values['id'], $values['query']);
    $entity->setQueryDmql($values['id'], $values['dmql']);
    $entity->setQueryFormat($values['id'], $values['format']);
    $entity->setQueryLimit($values['id'], $values['limit']);
    $entity->setQueryStandardNames($values['id'], $values['standardnames']);
    $entity->setQueryKeyField($values['id'], $values['key_field']);
    $entity->setQueryEntity($values['id'], $values['entity']);
    $entity->setQuerySelect($values['id'], $values['select']);
    if (isset($values['type_settings'])) {
      $configuration = $entity->getTypePlugin()->getConfiguration();
      $configuration['queries'][$values['id']] = $values['type_settings'][$entity->getTypePlugin()->getPluginId()];
      $entity->set('type_settings', $configuration);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\real_estate_rets\RetsConnectionInterface $connect */
    $connect = $this->entity;
    $connect->save();
    drupal_set_message($this->t('Saved %label query.', [
      '%label' => $connect->getQuery($this->queryId)->label(),
    ]));
    $form_state->setRedirectUrl($connect->toUrl('queries-list'));
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#submit' => ['::submitForm', '::save'],
    ];

    $actions['delete'] = [
      '#type' => 'link',
      '#title' => $this->t('Delete'),
      // Deleting a query is editing a connect.
      '#access' => $this->entity->access('edit'),
      '#attributes' => [
        'class' => ['button', 'button--danger'],
      ],
      '#url' => Url::fromRoute('entity.real_estate_rets_connection.delete_query_form', [
        'real_estate_rets_connection' => $this->entity->id(),
        'connection_query' => $this->queryId,
      ]),
    ];

    return $actions;
  }

}
