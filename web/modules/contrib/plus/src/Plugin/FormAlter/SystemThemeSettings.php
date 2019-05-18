<?php

namespace Drupal\plus\Plugin\FormAlter;

use Drupal\plus\Core\Form\FormAlterInterface;
use Drupal\plus\Core\Form\FormSubmitInterface;
use Drupal\plus\Core\Form\FormValidateInterface;
use Drupal\plus\Plugin\ThemePluginBase;
use Drupal\plus\Traits\PluginFormTrait;
use Drupal\plus\Utility\ArrayObject;
use Drupal\plus\Utility\Element;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_system_theme_settings_alter().
 *
 * @ingroup plugins_form_alter
 * @ingroup plugins_setting
 *
 * @FormAlter("system_theme_settings")
 */
class SystemThemeSettings extends ThemePluginBase implements FormAlterInterface, FormSubmitInterface, FormValidateInterface {

  use PluginFormTrait;

  /**
   * Sets up the vertical tab groupings.
   *
   * @param \Drupal\plus\Utility\Element $form
   *   The Element object that comprises the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function createGroups(Element $form, FormStateInterface $form_state) {
    // Vertical tabs for global settings provided by core or contrib modules.
    if (!isset($form['global'])) {
      $form['global'] = [
        '#type' => 'vertical_tabs',
        '#weight' => -9,
        '#prefix' => '<h2><small>' . t('Override Global Settings') . '</small></h2>',
      ];
    }

    // Iterate over existing children and move appropriate ones to global group.
    foreach ($form->children() as $child) {
      if ($child->isType(['details', 'fieldset']) && !$child->hasProperty('group')) {
        $child->setProperty('type', 'details');
        $child->setProperty('group', 'global');
      }
    }

    // Provide the necessary default groups.
    $form['bootstrap'] = [
      '#type' => 'vertical_tabs',
      '#attached' => ['library' => ['bootstrap/theme-settings']],
      '#prefix' => '<h2><small>' . t('Bootstrap Settings') . '</small></h2>',
      '#weight' => -10,
    ];
    $groups = [
      'general' => t('General'),
      'components' => t('Components'),
      'javascript' => t('JavaScript'),
      'advanced' => t('Advanced'),
    ];
    foreach ($groups as $group => $title) {
      $form[$group] = [
        '#type' => 'details',
        '#title' => $title,
        '#group' => 'bootstrap',
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function formAlter(Element $form, FormStateInterface $form_state, $form_id = NULL) {
    $theme = $this->getFormTheme($form_state);

    // Do not continue if the theme is not Plus based.
    if (!$theme || !$theme->isPlus()) {
      unset($form['#submit'][0]);
      unset($form['#formValidate'][0]);
      return;
    }

    // Creates the necessary groups (vertical tabs) for a Bootstrap based theme.
    $this->createGroups($form, $form_state);

    // Iterate over all setting plugins and add them to the form.
    foreach ($theme->getSettingPlugin() as $setting) {
      if ($setting instanceof FormAlterInterface) {
        $setting->formAlter($form, $form_state);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function formSubmit(Element $form, FormStateInterface $form_state) {
    $theme = $this->getFormTheme($form_state);
    if (!$theme) {
      return;
    }

    $cache_tags = new ArrayObject();
    $save = FALSE;
    $settings = $theme->settings();

    // Iterate over all setting plugins and manually save them since core's
    // process is severely limiting and somewhat broken.
    foreach ($theme->getSettingPlugin() as $name => $setting) {
      // Allow the setting to participate in the form submission process.
      if ($setting instanceof FormSubmitInterface) {
        $setting->formSubmit($form, $form_state);
      }

      // Retrieve the submitted value.
      $value = $form_state->getValue($name);

      // Determine if the setting has a new value that overrides the original.
      // Ignore "schemas" setting because it's handled by UpdatePluginManager.
      if ($name !== 'schemas' && $settings->overridesValue($name, $value)) {
        // Set the new value.
        $settings->set($name, $value);

        // Merge in any cache tags from the setting.
        $cache_tags->merge($setting->getCacheTags())->unique();

        // Flag save.
        $save = TRUE;
      }

      // Remove value from the form state object so core doesn't re-save it.
      $form_state->unsetValue($name);
    }

    // Save the settings, if needed.
    if ($save) {
      $settings->save();

      // Invalidate necessary cache tags.
      if ($cache_tags->count()) {
        \Drupal::service('cache_tags.invalidator')->invalidateTags($cache_tags->getArrayCopy());
      }

      // Clear our internal theme cache so it can be rebuilt properly.
      $theme->getCache('settings')->deleteAll();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function formValidate(Element $form, FormStateInterface $form_state) {
    $theme = $this->getFormTheme($form_state);
    if (!$theme) {
      return;
    }

    // Iterate over all setting plugins and allow them to participate.
    foreach ($theme->getSettingPlugin() as $setting) {
      // Allow settings to participate in the form validation process.
      if ($setting instanceof FormValidateInterface) {
        $setting->formValidate($form, $form_state);
      }
    }
  }

  /**
   * Retrieves the currently selected theme on the settings form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\plus\Plugin\Theme\ThemeInterface|false
   *   The currently selected theme object or FALSE if not a Bootstrap theme.
   */
  public function getFormTheme(FormStateInterface $form_state) {
    $build_info = $form_state->getBuildInfo();
    return isset($build_info['args'][0]) ? $this->plus->getTheme($build_info['args'][0]) : FALSE;
  }

}
