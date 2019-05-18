<?php

/**
 * @file
 * Contains \Drupal\packery\Form\PackeryGroupDisplayForm.
 */

namespace Drupal\packery\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements a display form; because vertical tabs won't render
 * correctly without it.
 */
class PackeryGroupDisplayForm extends FormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'packery_group_display_form';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $table = array(
      '#type' => 'table',
      '#header' => array('Name', 'Description', 'Value'),
    );

    $form['settings'] = array(
      '#type' => 'vertical_tabs',
      '#title' => t('Settings groups'),
      '#parents' => ['settings']
    );

    // Retrieve config definitions.
    $definitions = \Drupal::service('config.typed')->getDefinition('packery.group.default');
    $settings = $definitions['mapping']['settings']['mapping'];

    // Retrieve config entity values.
    $groups = packery_load_multiple();
    foreach ($groups as $group) {
      $form[$group->id()] = array(
        '#type' => 'details',
        '#title' => t('@title', array('@title' => $group->label())),
        '#group' => 'settings'
      );

      $form[$group->id()]['table'] = $table;
      foreach ($group->getSettings() as $name => $value) {        
        $form[$group->id()]['table']['#rows'][] = array(
          'name' => t('@label', array('@label' => $settings[$name]['label'])),
          'description' => t('@text', array('@text' => $settings[$name]['text'])),
          'value' => $value
        );
      }

      $form[$group->id()]['actions'] = array(
        '#type' => 'container'
      );
      $form[$group->id()]['actions']['edit'] = array(
        '#type' => 'submit',
        '#name' => $group->id(),
        '#value' => 'Edit'
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $group = $form_state->getTriggeringElement();
    $form_state->setRedirect('entity.packery_group.edit_form', array('packery_group' => $group['#name']));
  }

}
