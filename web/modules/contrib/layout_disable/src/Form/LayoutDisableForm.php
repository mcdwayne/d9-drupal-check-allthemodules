<?php

/**
 * @file
 * Contains \Drupal\layout_disable\Form\LayoutDisableForm.
 */

namespace Drupal\layout_disable\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\Cache;


/**
 * Administration settings form.
 */
class LayoutDisableForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'layout_disable';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('layout_disable.settings');
    $settings = $config->get('layout_disable.settings');

    $layouts = \Drupal::service('plugin.manager.core.layout')
      ->getSortedDefinitions();

    $layout_names = [];
    if (!empty($layouts)) {
      foreach ($layouts as $layoutName => $layoutDefinition) {
        if ($layoutName == 'layout_onecol') {
          // layout_oncecol is required by core and can not be disabled!
          continue;
        }
        $layout_names[Html::escape($layoutName)] = Html::escape($layoutDefinition->getLabel()) . ' (' . Html::escape($layoutDefinition->id()) . ')';
      }
    }

    // Already disabled layouts have already been removed here because the form is built after layout_disable_layout_alter.
    // See #2983016 (https://www.drupal.org/project/layout_disable/issues/2983016)
    // So we have to make them available manually:
    if (!empty($settings['disabled_layouts'])) {
      \Drupal::messenger()
        ->addWarning('If already removed from code (disabled) layouts appear here, uncheck them to remove them finally. They are still listed due to a hook_layout_alter logical incompatibility.');
      // TODO - This may lead to listing already uninstalled layouts here. Find a way to only show existing layouts despite alteration.
      $layout_names = array_merge($settings['disabled_layouts'], $layout_names);
    }

    $form['disabled_layouts'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Disable layouts'),
      '#description' => $this->t('Select the layouts you wish to disable. "layout_oncecol" is required by core and can not be disabled!'),
      '#default_value' => !empty($settings['disabled_layouts']) ? $settings['disabled_layouts'] : [],
      '#options' => $layout_names,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('layout_disable.settings');
    $form_values = $form_state->getValues();

    if(!empty($form_values)){
      foreach($form_values['disabled_layouts'] as $disabledLayout => $layoutStatus){
        if(!$layoutStatus){
          // Only save disabled layouts (status =1) to keep the lsit as small as possible.
          unset($form_values['disabled_layouts'][$disabledLayout]);
        }
      }
    }

    $config->set('layout_disable.settings', ['disabled_layouts' => $form_values['disabled_layouts']])
      ->save();
    parent::submitForm($form, $form_state);

    // Clear layout caches:
    \Drupal::service('plugin.manager.core.layout')->clearCachedDefinitions();
  }
}
