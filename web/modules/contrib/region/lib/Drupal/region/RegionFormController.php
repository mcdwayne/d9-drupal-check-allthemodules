<?php

/**
 * @file
 * Definition of Drupal\region\RegionFormController.
 */

namespace Drupal\region;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityFormController;

/**
 * Form controller for the region edit/add forms.
 */
class RegionFormController extends EntityFormController {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state, EntityInterface $region) {
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#maxlength' => 255,
      '#default_value' => $region->label(),
      '#description' => t("Example: 'Banner' or 'Highlight'."),
      '#required' => TRUE,
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $region->id(),
      '#machine_name' => array(
        'exists' => 'region_load',
        'source' => array('label'),
      ),
      '#disabled' => !$region->isNew(),
    );
    return parent::form($form, $form_state, $region);
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::actions().
   */
  protected function actions(array $form, array &$form_state) {
    // Only includes a Save action for the entity, no direct Delete button.
    return array(
      'submit' => array(
        '#value' => t('Save'),
        '#validate' => array(
          array($this, 'validate'),
        ),
        '#submit' => array(
          array($this, 'submit'),
          array($this, 'save'),
        ),
      ),
    );
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, array &$form_state) {
    $region = $this->getEntity($form_state);
    $region->save();

    watchdog('region', 'Region @label saved.', array('@label' => $region->label()), WATCHDOG_NOTICE);
    drupal_set_message(t('Region %label saved.', array('%label' => $region->label())));

    $form_state['redirect'] = 'admin/structure/regions';
  }

}

