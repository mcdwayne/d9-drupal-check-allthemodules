<?php

/**
 * @file
 * Definition of Drupal\rlayout\RLayoutFormController.
 */

namespace Drupal\rlayout;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityFormController;
use Drupal\region\Region;

/**
 * Form controller for the layout edit/add forms.
 */
class RLayoutFormController extends EntityFormController {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::prepareEntity().
   *
   * Prepares the layout object filling in a few default values.
   */
  protected function prepareEntity(EntityInterface $layout) {
    if (empty($layout->regions)) {
      if ($default = rlayout_load('default')) {
        // Attempt to clone the default layout if available.
        $layout->regions = $default->regions;
        $layout->overrides = $default->overrides;
      }
      else {
        // If the default cannot be cloned, set some defaults.
        $layout->regions = array();
        $default_regions = region_load_all();
        foreach ($default_regions as $region) {
          $layout->regions[] = $region->id();
        }
        $layout->overrides = array();
      }
    }
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state, EntityInterface $layout) {
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#maxlength' => 255,
      '#default_value' => $layout->label(),
      '#description' => t("Example: 'Front page', 'Section page'."),
      '#required' => TRUE,
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $layout->id(),
      '#machine_name' => array(
        'exists' => 'rlayout_load',
        'source' => array('label'),
      ),
      '#disabled' => !$layout->isNew(),
    );

    $layoutdata = array();
    $default_regions = region_load_all();
    foreach ($layout->regions as $id) {
      $layoutdata['regions'][] = array(
        'id' => $id,
        'label' => $default_regions[$id]->label(),
      );
    }
    $layoutdata['overrides'] = (array) $layout->overrides;

    $form['layout_regions'] = array(
      '#type' => 'textarea',
      '#title' => t('Region and bunnypoint configuration'),
      '#default_value' => drupal_json_encode($layoutdata),
      '#suffix' => '<div id="responsive-layout-designer"></div>',
    );

    $form['#attached'] = array(
      'library' => array(
        array('system', 'jquery.ui.dialog'),
        array('system', 'jquery.ui.sortable'),
        array('rlayout', 'rlayout-designer'),
        array('rlayout', 'rlayout-admin'),
      ),
      'js' => array(
        array(
          'data' => array(
            'responsiveLayout' => array(
              'layout' => $layout,
              'defaultRegions' => region_load_all(),
              'defaultBreakpoints' => rlayout_breakpoints_load_all(),
              'defaultGrids' => entity_load_multiple('grid'),
            ),
          ),
          'type' => 'setting',
        ),
      ),
      'css' => array(
        array(
          // Embed the grid css inline for now. Yeah, I know this is evil.
          // It is just a prototype for now, ok? I know it is evil. Yes.
          'data' => rlayout_breakpoint_get_css(FALSE),
          'type' => 'inline',
        ),
      ),
    );

  // JSON2 is required for stringifying JavaScript data structures in older browsers.
  /*$name = 'json2';
  if (!libraries_detect($name)) {
    watchdog('responsive', 'The JSON-js library is recommended for this module to function properly. Some older browsers do not provide the JSON function natively. Please visit !url to obtain this library.',
      array(
        '!url' => l('JSON-js (Github)', 'https://github.com/douglascrockford/JSON-js',
          array(
            'absolute' => TRUE,
            'external' => TRUE
          )
        )
      ),
      WATCHDOG_NOTICE
    );
  }
  else {
    libraries_load($name);
  }*/

    return parent::form($form, $form_state, $layout);
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
    $layout = $this->getEntity($form_state);

    $default_regions = region_load_all();
    $new_layout_settings = drupal_json_decode($form_state['values']['layout_regions']);

    if (!empty($new_layout_settings)) {
      $layout->regions = array();
      foreach ($new_layout_settings['regions'] as $region) {
        $layout->regions[] = $region['id'];

        /*/ Save region in common regions list in case it is new.
        if (!isset($default_regions[$region['id']])) {
          $region = (object) array(
            'id' => $region['id'],
            'label' => $region['label'],
          );
          region_save($region);
        }*/
      }
      $layout->overrides = $new_layout_settings['overrides'];
    }
    $layout->save();

    watchdog('layout', 'Layout @label saved.', array('@label' => $layout->label()), WATCHDOG_NOTICE);
    drupal_set_message(t('Layout %label saved.', array('%label' => $layout->label())));

    $form_state['redirect'] = 'admin/structure/layouts';
  }

}
