<?php

namespace Drupal\x_reference\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class XReferenceTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function __construct(QueryFactory $entity_query) {
    $this->entityQuery = $entity_query;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\x_reference\Entity\XReferenceType $x_reference_type */
    $x_reference_type = $this->entity;

    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#maxlength' => 255,
      '#default_value' => $x_reference_type->label(),
      '#description' => $this->t("Label for the Sheme type."),
      '#required' => TRUE,
    );
    $form['machine_name'] = array(
      '#type' => 'textfield',
      '#maxlength' => 255,
      '#title' => $this->t('Machine name'),
      '#default_value' => !empty($x_reference_type->machine_name)
        ? $x_reference_type->machine_name
        : '',
      '#machine_name' => array(
        'exists' => array($this, 'exist'),
      ),
      '#required' => TRUE,
      '#disabled' => !$x_reference_type->isNew(),
    );

    // @todo: custom select list
    $form['source_entity_source'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Source entity source'),
      '#maxlength' => 255,
      '#default_value' => !empty($x_reference_type->source_entity_source)
        ? $x_reference_type->source_entity_source
        : '',
      '#description' => $this->t('Source of the source entity'),
      '#required' => TRUE,
    );
    $form['source_entity_type'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Source entity type'),
      '#maxlength' => 255,
      '#default_value' => !empty($x_reference_type->source_entity_type)
        ? $x_reference_type->source_entity_type
        : '',
      '#description' => $this->t('Type of the source entity'),
      '#required' => TRUE,
    );
    $form['target_entity_source'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Target entity source'),
      '#maxlength' => 255,
      '#default_value' => !empty($x_reference_type->target_entity_source)
        ? $x_reference_type->target_entity_source
        : '',
      '#description' => $this->t('Source of the target entity'),
      '#required' => TRUE,
    );
    $form['target_entity_type'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Target entity type'),
      '#maxlength' => 255,
      '#default_value' => !empty($x_reference_type->target_entity_type)
        ? $x_reference_type->target_entity_type
        : '',
      '#description' => $this->t('Type of the target entity'),
      '#required' => TRUE,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = $entity->save();

    if ($status) {
      drupal_set_message($this->t('Saved the %label Sheme type.', array(
        '%label' => $entity->label(),
      )));
    }
    else {
      drupal_set_message($this->t('The %label scheme type was not saved.', array(
        '%label' => $entity->label(),
      )));
    }

    $form_state->setRedirect('entity.x_reference_type.collection');
  }

}