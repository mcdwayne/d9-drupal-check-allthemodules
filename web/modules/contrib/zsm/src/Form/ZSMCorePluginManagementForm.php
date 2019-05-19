<?php
/**
 * @file
 * Contains \Drupal\zsm\Form\ZSMCoreSettingsForm.
 */

namespace Drupal\zsm\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\zsm\ZSMPluginManager;

/**
 * Class ContentEntityExampleSettingsForm.
 *
 * @package Drupal\zsm\Form
 *
 * @ingroup zsm
 */
class ZSMCorePluginManagementForm extends FormBase {
  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'zsm_core_manage_plugins';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Empty implementation of the abstract submit class.
    $vals = $form_state->getValues();
    $core = \Drupal::entityTypeManager()->getstorage('zsm_core')->load($vals['zsm_core_id']);
    $enabled_plugins = explode(',', $vals['zsm_enabled_plugins']);
    // Go through the list of enabled plugins, find checked items, and add them to the values to be set.
    $set = array();
    foreach($enabled_plugins as $ep) {
      if (isset($vals[$ep])) {
        foreach($vals[$ep] as $item) {
          if ($item) {
            $set[] = array('target_type' => $ep, 'target_id' => $item);
          }
        }
      }
    }

    $core->set('field_zsm_enabled_plugins', $set);
    $core->save();

    $response = new TrustedRedirectResponse($_SERVER['HTTP_REFERER']);
    $form_state->setResponse($response);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $plugin_manager = \Drupal::service('plugin.manager.zsm');
    $plugin_definitions = $plugin_manager->getDefinitions();
    $core_id = \Drupal::request()->get('zsm_core');
    $core = \Drupal::entityTypeManager()->getstorage('zsm_core')->load($core_id);
    $enabled_plugins = array();
    foreach ($core->get('field_zsm_enabled_plugins')->getValue() as $item) {
      $enabled_plugins[$item['target_type']][] = $item['target_id'];
    }
    $form['zsm_core_id'] = array(
      '#type' => 'hidden',
      '#value' => $core_id,
    );
    $form['zsm_enabled_plugins'] = array(
      '#type' => 'hidden',
      '#value' => '',
    );
    foreach ($plugin_definitions as $pd) {
      // Get the core entity's pre-selected items, and the available options
      $query = \Drupal::entityQuery($pd['id']);
      $query->condition('user_id', \Drupal::currentUser()->id());
      $ids = $query->execute();
      $ents = \Drupal::entityTypeManager()->getstorage($pd['id'])->loadMultiple(array_values($ids));
      $options = array();
      foreach ($ents as $ent) {
        $options[$ent->id()] = $ent->label();
      }
      if(!empty($options)) {
        $form['zsm_enabled_plugins']['#value'] .= empty($form['zsm_enabled_plugins']['#value']) ? $pd['id'] : ',' . $pd['id'];
        $form[$pd['id'] . '_list'] = array(
          '#type' => 'container',
        );
        $form[$pd['id'] . '_list']['header'] = array(
          '#type' => 'markup',
          '#prefix' => '<h2>',
          '#markup' => $pd['label'],
          '#suffix' => '</h2>',
        );
        $form[$pd['id'] . '_list'][$pd['id']] = array(
          '#type' => 'checkboxes',
          '#options' => $options,
        );
        if (isset($enabled_plugins[$pd['id']]) && !empty($enabled_plugins[$pd['id']])) {
          $form[$pd['id'] . '_list'][$pd['id']]['#default_value'] = $enabled_plugins[$pd['id']];
        }

      }
    }
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Submit',
    );
    return $form;
  }
}
