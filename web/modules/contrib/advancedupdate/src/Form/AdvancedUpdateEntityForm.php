<?php

namespace Drupal\advanced_update\Form;

use Drupal\advanced_update\Entity\AdvancedUpdateEntity;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AdvancedUpdateEntityForm.
 *
 * @package Drupal\advanced_update\Form
 */
class AdvancedUpdateEntityForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $advanced_update_entity = $this->entity;
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Functionality description'),
      '#maxlength' => 255,
      '#default_value' => $advanced_update_entity->label(),
      '#description' => $this->t("Description of this Advanced update."),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $advanced_update_entity->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\advanced_update\Entity\AdvancedUpdateEntity::load',
      ),
      '#disabled' => !$advanced_update_entity->isNew(),
    );

    $form['date'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Creation date (timestamp)'),
      '#maxlength' => 255,
      '#default_value' => $advanced_update_entity->isNew() ? time() : $advanced_update_entity->date(),
      '#description' => $this->t("Creation date of this Advanced update."),
      '#required' => TRUE,
    );

    if ($advanced_update_entity->isNew()) {
      $modules = array_keys(system_get_info('module'));
      $modules = array_combine($modules, $modules);
      $form['module_name'] = [
        '#type' => 'select',
        '#title' => $this->t('Module name'),
        '#description' => $this->t("Module name where is performed this Advanced update."),
        '#required' => TRUE,
        '#options' => $modules,
      ];
    }
    else {
      $form['module_name'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Module name'),
        '#maxlength' => 255,
        '#default_value' => $advanced_update_entity->moduleName(),
        '#description' => $this->t("Module name where is performed this Advanced update."),
        '#required' => TRUE,
        '#disabled' => TRUE,
      );
    }

    $form['class_name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Class name'),
      '#maxlength' => 255,
      '#default_value' => $advanced_update_entity->isNew() ? AdvancedUpdateEntity::generateClassName() : $advanced_update_entity->className(),
      '#description' => $this->t("Class name of this Advanced update."),
      '#required' => TRUE,
      '#disabled' => TRUE,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $advanced_update_entity = $this->entity;
    $status = $advanced_update_entity->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Advanced update.', [
          '%label' => $advanced_update_entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Advanced update.', [
          '%label' => $advanced_update_entity->label(),
        ]));
    }
    $form_state->setRedirectUrl($advanced_update_entity->urlInfo('collection'));
  }

}
