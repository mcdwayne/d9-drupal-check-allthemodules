<?php

/**
 * @file
 * Contains Drupal\themekey\Form\ThemeKeyRuleForm.
 */

namespace Drupal\themekey\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\themekey\PropertyManagerTrait;
use Drupal\themekey\PropertyAdminManagerTrait;
use Drupal\themekey\OperatorManagerTrait;
use Drupal\themekey\ThemeKeyRuleInterface;

class ThemeKeyRuleForm extends EntityForm
{

  use PropertyManagerTrait;
  use PropertyAdminManagerTrait;
  use OperatorManagerTrait;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $themekey_rule = $this->entity;

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $themekey_rule->label(),
      '#description' => $this->t("Label for the ThemeKeyRule."),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $themekey_rule->id(),
      '#machine_name' => array(
        'exists' => 'themekey_rule_load',
      ),
      '#disabled' => !$themekey_rule->isNew(),
    );

    $properties = $this->getPropertyManager()->getDefinitions();

    $property_options = array();
    foreach ($properties as $property) {
      $property_options[$property['id']] = $property['id'];
    }

    $form['property'] = array(
      '#type' => 'select',
      '#title' => $this->t('Property'),
      '#options' => $property_options,
      '#default_value' => $themekey_rule->property(),
      '#description' => $this->t("Property for the ThemeKeyRule."),
      '#required' => TRUE,
    );

    $form['key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Key'),
      '#maxlength' => 255,
      '#default_value' => $themekey_rule->key(),
      '#description' => $this->t("Optional key for the ThemeKeyRule."),
      '#required' => FALSE,
    );

    $operators = $this->getOperatorManager()->getDefinitions();

    $operator_options = array();
    foreach ($operators as $operator) {
      $operator_options[$operator['id']] = $operator['id'];
    }

    $form['operator'] = array(
      '#type' => 'select',
      '#title' => $this->t('Operator'),
      '#options' => $operator_options,
      '#default_value' => $themekey_rule->operator(),
      '#description' => $this->t("Operator for the ThemeKeyRule."),
      '#required' => TRUE,
    );

    $form['value'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Value'),
      '#maxlength' => 255,
      '#default_value' => $themekey_rule->value(),
      '#description' => $this->t("Value for the ThemeKeyRule."),
      '#required' => TRUE,
    );

    $form['theme'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Theme'),
      '#maxlength' => 255,
      '#default_value' => $themekey_rule->theme(),
      '#description' => $this->t("Theme for the ThemeKeyRule."),
      '#required' => TRUE,
    );

    $form['comment'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Comment'),
      '#default_value' => $themekey_rule->comment(),
      '#description' => $this->t("Optional comment for the ThemeKeyRule."),
      '#required' => FALSE,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $propertyAdmin = $this->getPropertyAdminManager()->createInstance(
      $form_state->getValue('property')
    );

    $value = $form_state->getValue('value');

    if ($propertyAdmin) {
      $operator = $this->getOperatorManager()->createInstance(
        $form_state->getValue('operator')
      );

      $operator->validate($propertyAdmin, $value, $form_state);
    }
  }

  /**
  * {@inheritdoc}
  */
  public function save(array $form, FormStateInterface $form_state) {
    $themekey_rule = $this->entity;
    $status = $themekey_rule->save();

    if ($status) {
      drupal_set_message($this->t('Saved the %label ThemeKeyRule.', array(
        '%label' => $themekey_rule->label(),
      )));
    }
    else {
      drupal_set_message($this->t('The %label ThemeKeyRule was not saved.', array(
        '%label' => $themekey_rule->label(),
      )));
    }
    $form_state->setRedirect('themekey_rule.list');
  }
}
