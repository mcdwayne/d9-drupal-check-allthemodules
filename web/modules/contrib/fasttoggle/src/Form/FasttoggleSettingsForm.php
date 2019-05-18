<?php
/**
 * @file
 * Contains \Drupal\fasttoggle\Form\FasttoggleSettingsForm.
 */

namespace Drupal\fasttoggle\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Database\Database;
use Drupal\Core\Plugin\DefaultPluginManager;

require_once __DIR__ . '/../../fasttoggle.inc';

/**
 * Configure fasttoggle settings for this site.
 */
class FasttoggleSettingsForm extends ConfigFormBase {

  /**
   * @var \Drupal\fasttoggle\Controller\FasttoggleController
   *   The Fasttoggle controller.
   */
  private $controller;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'fasttoggle.settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->controller = new \Drupal\fasttoggle\Controller\FasttoggleController();
    $this->objectManager = \Drupal::service('plugin.manager.fasttoggle.setting_object');
    $this->groupManager = \Drupal::service('plugin.manager.fasttoggle.setting_group');
    $this->settingManager = \Drupal::service('plugin.manager.fasttoggle.setting');
    $this->contextManager = \Drupal::service('plugin.manager.fasttoggle.context');

    return self::buildFormContent($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    static $result = NULL;

    if (is_null($result)) {
      $settings = $this->settingManager->getDefinitions();
      $result = [];

      foreach ($settings as $object_type => $groups) {
        foreach ($groups as $group_name => $settings) {
          $result = array_merge($result, array_keys($settings));
        }
      }
    }

    return $result;
  }

  /**
   * Get the sitewide settings form content.
   *
   * @param array $form
   *   The Drupal form array.
   * @param array $form_state
   *   The Drupal form_state array.
   */
  public function buildFormContent(array $form, FormStateInterface $form_state) {
    $config = $this->config('fasttoggle.settings');

    $form['label_style'] = [
      '#type' => 'radios',
      '#title' => t('Label style'),
      '#description' => t('Select what kind of labels you want for fasttoggle links. See the README.txt for information about providing your own labels.'),
      '#options' => [
        FASTTOGGLE_LABEL_STATUS => t('Status (reflects the current state, e.g. "published", "active")'),
        FASTTOGGLE_LABEL_ACTION => t('Action (shows what happens upon a click, e.g. "unpublish", "block")'),
      ],
      '#default_value' => $config->get('label_style'),
    ];

    if (0) {  // @TODO Custom labels.
      $custom_labels = $config->get('custom_labels');
      if (!empty($custom_labels)) {
        $form['fasttoggle_label_style']['#options'][FASTTOGGLE_LABEL_CUSTOM] = t('Custom (configure in your settings.php)');
      }
    }

    // Get all settings, grouped by entity and setting group.
    $objects = $this->objectManager->getDefinitions();
    $groups = $this->groupManager->getDefinitions();
    $settings = $this->settingManager->getDefinitions();

    foreach ($objects as $object => $objectDef) {
      $object_plugin = $this->objectManager->createInstance($object);
      $elements_for_type = $object_plugin->getSitewideSettingFormElements($config);

      foreach ($groups[$object] as $group => $groupDef) {
        $groupPlugin = $this->groupManager->createInstance($group);

        foreach ($settings[$object][$group] as $setting => $settingDef) {
          if (!isset($elements_for_type[$group])) {
            if ($groupDef['title']) {
              $elements_for_type[$group] = [
                '#type' => 'fieldset',
                '#title' => $groupDef['title'],
                '#description' => $groupDef['description'],
                '#weight' => $groupDef['weight'],
              ];
              $fieldset = &$elements_for_type[$group];
            }
            else {
              $fieldset = &$elements_for_type;
            }
          }

          $setting_plugin = $this->settingManager->createInstance($setting);
          $configKeys = $setting_plugin->configSettingKeys();
          foreach ($configKeys as $key) {
            $fieldset[$key] = $setting_plugin->settingForm($config, $key);
          }
        }
      }

      if (!empty($elements_for_type)) {
        $form[$object] = [
          '#type' => 'fieldset',
          '#title' => $objectDef['title'],
          '#description' => $objectDef['description'],
          '#weight' => $objectDef['weight'],
        ];

        $form[$object] += $elements_for_type;
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('fasttoggle.settings');
    $configKeys = [ ];

    $settings_obj = $this->configFactory()->getEditable('fasttoggle.settings');
    $settings_obj->set('label_style', $form_state->getValue('label_style'));

    $objects = $this->objectManager->getDefinitions();
    $group = $this->groupManager->getDefinitions();
    $object_settings = $this->settingManager->getDefinitions();

    foreach ($object_settings as $type => $groups) {
      $plugin = $this->objectManager->createInstance($type);
      $configKeys += array_keys($plugin->getSitewideSettingFormElements($config));

      foreach ($groups as $group => $settings) {
        $plugin = $this->groupManager->createInstance($group);
        $configKeys += array_keys($plugin->getSitewideSettingFormElements($config));

        foreach ($settings as $setting => $settingDef) {
          $plugin = $this->settingManager->createInstance($setting);
          $configKeys += array_merge($configKeys, $plugin->getSitewideSettingFormElements($config));
          $configKeys += array_merge($configKeys, $plugin->configSettingKeys());
        }
      }
    }

    foreach ($configKeys as $key) {
      $new_value = $form_state->getValue($key);
      $settings_obj->set($key, $new_value)
        ->save();
    }

    parent::submitForm($form, $form_state);
  }

  /**
   *
   */
  public function systemAdminMenuFasttogglePage() {

  }

}
