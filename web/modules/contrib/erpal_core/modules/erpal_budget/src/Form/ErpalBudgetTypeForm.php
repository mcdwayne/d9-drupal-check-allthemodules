<?php

/**
 * @file
 * Contains \Drupal\erpal_budget\Form\ErpalBudgetTypeForm.
 */

namespace Drupal\erpal_budget\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Component\Utility\String;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
/**
 * Class ErpalBudgetTypeForm
 *
 * Form class for adding/editing Erpal Budget type entities.
 */
class ErpalBudgetTypeForm extends EntityForm {

   /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

   //$form = parent::form($form, $form_state);

    $type = $this->entity;

    // Change page title for the edit operation
    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('Edit Erpal Budget type: @name', array('@name' => $type->name));
    }

    // The Erpal Budget type name.
    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#maxlength' => 255,
      '#default_value' => $type->name,
      '#description' => $this->t("Erpal Budget type name."),
      '#required' => TRUE,
    );

    // The unique machine name of the Erpal Budget type.
    $form['id'] = array(
      '#type' => 'machine_name',
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#default_value' => $type->id,
      '#disabled' => !$type->isNew(),
      '#machine_name' => array(
        'source' => array('name'),
        'exists' => 'erpal_budget_type_load'
      ),
    );
    $form['description'] = array(
      '#title' => t('Description'),
      '#type' => 'textarea',
      '#default_value' => $type->description,
      '#description' => t('Describe this content type. The text will be displayed on the <em>Add content</em> page.'),
    );
    // The Erpal Budget type unit type.
    $form['unit_type'] = array(
      '#type' => 'select',
      '#options' => array(
        'time' => 'Time',
        'money' => 'Money'
      ),
      '#title' => $this->t('Unit type'),
      '#maxlength' => 255,
      '#default_value' => $type->unit_type,
      '#description' => $this->t("Select unit type for output."),
      '#required' => TRUE,
    );

    return parent::form($form, $form_state, $type);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $type = $this->entity;
    $status = $type->save();

    if ($status) {
      // Setting the success message.
      drupal_set_message($this->t('Saved the Erpal Budget type: @name.', array(
        '@name' => $type->name,
      )));
    }
    else {
      drupal_set_message($this->t('The @name Erpal Budget type was not saved.', array(
        '@name' => $type->name,
      )));
    }

    $form_state->setRedirect('entity.erpal_budget_type.list');
  }
}
