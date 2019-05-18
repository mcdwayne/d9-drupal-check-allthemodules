<?php

/**
 * @file
 * Definition of Drupal\gridbuilder\GridFormController.
 */

namespace Drupal\gridbuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityFormController;

/**
 * Form controller for the grid edit/add forms.
 */
class GridFormController extends EntityFormController {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::prepareEntity().
   *
   * Prepares the grid object filling in a few default values.
   */
  protected function prepareEntity(EntityInterface $grid) {
    if (empty($grid->width)) {
      // Set some defaults for the user if this is a new grid.
      $grid->type = GRIDBUILDER_FLUID;
      $grid->width = 100;
      $grid->columns = 12;
      $grid->padding_width = 1.5;
      $grid->gutter_width = 2;
    }
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state, EntityInterface $grid) {
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#maxlength' => 255,
      '#default_value' => $grid->label(),
      '#required' => TRUE,
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $grid->id(),
      '#machine_name' => array(
        'exists' => 'gridbuilder_load',
        'source' => array('label'),
      ),
      '#disabled' => !$grid->isNew(),
    );

    // Master grid configuration.
    $form['type'] = array(
      '#type' => 'radios',
      '#title' => t('Type of grid'),
      '#options' => array(GRIDBUILDER_FLUID => t('Fluid'), GRIDBUILDER_FIXED => t('Fixed to specific width')),
      '#default_value' => $grid->type,
    );
    $form['width'] = array(
      '#type' => 'textfield',
      '#title' => t('Grid width'),
      '#description' => t('Only meaningful if using a fixed grid. Enter a pixel size (eg. 960).'),
      '#default_value' => $grid->width,
      '#states' => array(
        'visible' => array('input[name="type"]' => array('value' => GRIDBUILDER_FIXED)),
      ),
      '#field_suffix' => $grid->type == GRIDBUILDER_FIXED ? 'px' : '%',
    );

    // Grid detail configuration.
    $form['columns'] = array(
      '#type' => 'textfield',
      '#title' => t('Number of grid columns'),
      '#default_value' => $grid->columns,
    );
    $form['padding_width'] = array(
      '#type' => 'textfield',
      '#title' => t('Column padding'),
      '#description' => t('Column padding in pixels (for fixed grids, eg. 10) or percentages (for fluid grids, eg. 1.5). Enter 0 for no padding.'),
      '#default_value' => $grid->padding_width,
      '#field_suffix' => $grid->type == GRIDBUILDER_FIXED ? 'px' : '%',
    );

    $form['gutter_width'] = array(
      '#type' => 'textfield',
      '#title' => t('Gutter width'),
      '#description' => t('Gutter width in pixels (for fixed grids, eg. 20) or percentages (for fluid grids, eg. 2). Enter 0 for no gutter.'),
      '#default_value' => $grid->gutter_width,
      '#field_suffix' => $grid->type == GRIDBUILDER_FIXED ? 'px' : '%',
    );

    $grid_breakpoints = entity_load('breakpoint_group', 'module.gridbuilder.gridbuilder');
    $breakpoint_options = array();
    foreach ($grid_breakpoints->breakpoints as $key => $breakpoint) {
      $breakpoint_options[$key] = $breakpoint->label();
    }
    $form['breakpoints'] = array(
      '#title' => t('Breakpoints where this grid will apply'),
      '#type' => 'checkboxes',
      '#options' => $breakpoint_options,
      '#default_value' => (array) $grid->breakpoints,
    );

    $form['#attached']['css'][] = drupal_get_path('module', 'gridbuilder') . '/gridbuilder-admin.css';
    $form['#attached']['js'][] = drupal_get_path('module', 'gridbuilder') . '/gridbuilder-admin.js';

    return parent::form($form, $form_state, $grid);
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::validate().
   */
  public function validate(array $form, array &$form_state) {
    // Force width to 100 if fluid width. That is in percentages.
    if ($form_state['values']['type'] == GRIDBUILDER_FLUID) {
      $form_state['values']['width'] = 100;
    }

    if ((intval($form_state['values']['width']) != $form_state['values']['width']) || $form_state['values']['width'] == 0) {
      // Width should be a positive integer.
      form_set_error('columns', t('The width should be a positive number.'));
    }
    if ((intval($form_state['values']['columns']) != $form_state['values']['columns']) || $form_state['values']['columns'] == 0) {
      // Columns should be a positive integer.
      form_set_error('columns', t('The number of columns should be a positive number.'));
    }
    if (!is_numeric($form_state['values']['padding_width'])) {
      // Padding can be float as well (eg. 1.5 for 1.5% for fluid grids).
      form_set_error('padding_width', t('The column padding should be a number. Enter 0 (zero) for no padding.' . $form_state['values']['padding_width']));
    }
    if (!is_numeric($form_state['values']['gutter_width'])) {
      // Gutter can be float too (eg. 1.5 for 1.5% for fluid grids).
      form_set_error('gutter_width', t('The gutter width should be a number. Enter 0 (zero) for no gutter.'));
    }
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
    $grid = $this->getEntity($form_state);
    $grid->save();

    watchdog('gridbuilder', 'Grid @label saved.', array('@label' => $grid->label()), WATCHDOG_NOTICE);
    drupal_set_message(t('Grid %label saved.', array('%label' => $grid->label())));

    $form_state['redirect'] = 'admin/structure/grids';
  }

}

