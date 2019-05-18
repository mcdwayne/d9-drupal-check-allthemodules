<?php

namespace Drupal\drd\Update;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;

/**
 * Manages discovery and instantiation of DRD Update Storage plugins.
 */
class ManagerStorage extends Manager implements ManagerStorageInterface {

  /**
   * List of plugins.
   *
   * @var PluginInterface[]
   */
  private $plugins = [];

  /**
   * Get meta data for update plugin types.
   */
  private function meta() {
    return [
      'storage' => [
        'label' => t('Storage'),
        'manager' => $this,
      ],
      'build' => [
        'label' => t('Build'),
        'manager' => \Drupal::service('plugin.manager.drd_update.build'),
      ],
      'process' => [
        'label' => t('Process'),
        'manager' => \Drupal::service('plugin.manager.drd_update.process'),
      ],
      'test' => [
        'label' => t('Test'),
        'manager' => \Drupal::service('plugin.manager.drd_update.test'),
      ],
      'deploy' => [
        'label' => t('Deployment'),
        'manager' => \Drupal::service('plugin.manager.drd_update.deploy'),
      ],
      'finish' => [
        'label' => t('Finishing'),
        'manager' => \Drupal::service('plugin.manager.drd_update.finish'),
      ],
    ];
  }

  /**
   * Generate and return an update plugin instance.
   *
   * @param string $type
   *   The update plugin type.
   * @param array $settings
   *   The plugin settings.
   *
   * @return object|PluginBuildInterface|PluginDeployInterface|PluginFinishInterface|PluginProcessInterface|PluginStorageInterface|PluginTestInterface
   *   The update plugin.
   */
  private function instance($type, array $settings) {
    $id = $settings['current'][$type];
    $meta = $this->meta();
    /** @var ManagerInterface $manager */
    $manager = $meta[$type]['manager'];
    return $manager->createInstance($id, $settings['details'][$type][$id]);
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return 'storage';
  }

  /**
   * {@inheritdoc}
   */
  public function getSubDir() {
    return 'Plugin/Update/Storage';
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginInterface() {
    return 'Drupal\drd\Update\PluginStorageInterface';
  }

  /**
   * {@inheritdoc}
   */
  public function getSelect() {
    return ['' => t('None')] + parent::getSelect();
  }

  /**
   * {@inheritdoc}
   */
  public function executableInstance(array $settings) {
    if (empty($settings['current']['storage'])) {
      throw new \Exception('Storage plugin is required');
    }

    /** @var PluginStorageInterface $pluginStorage */
    $pluginStorage = $this->instance('storage', $settings);

    $pluginStorage->stepPlugins(
      $this->instance('build', $settings),
      $this->instance('process', $settings),
      $this->instance('test', $settings),
      $this->instance('deploy', $settings),
      $this->instance('finish', $settings)
    );
    return $pluginStorage;
  }

  /**
   * {@inheritdoc}
   */
  public function buildGlobalForm(array &$form, FormStateInterface $form_state, array $settings) {
    $settings = NestedArray::mergeDeep([
      'current' => [
        'storage' => '',
        'build' => 'direct',
        'process' => 'noprocess',
        'test' => 'notest',
        'deploy' => 'nodeploy',
        'finish' => 'nofinish',
      ],
      'details' => [
        'storage' => [],
        'build' => [],
        'process' => [],
        'test' => [],
        'deploy' => [],
        'finish' => [],
      ],
    ], $settings);
    $meta = $this->meta();

    $form['drd_update_method'] = [
      '#type' => 'fieldset',
      '#title' => t('Update method'),
    ];
    foreach ($meta as $type => $details) {
      /** @var ManagerInterface $manager */
      $manager = $details['manager'];
      $form['drd_update_method'][$type] = [
        '#type' => 'fieldset',
        '#title' => $details['label'],
        '#tree' => TRUE,
      ];
      $form['drd_update_method'][$type]['drd_upd_type_' . $type] = [
        '#type' => 'select',
        '#options' => $manager->getSelect(),
        '#default_value' => $settings['current'][$type],
      ];
      if ($type != 'storage') {
        $form['drd_update_method'][$type]['#states'] = [
          'invisible' => ['select#edit-storage-drd-upd-type-storage' => ['value' => '']],
        ];
      }
      foreach ($manager->getSelect() as $id => $label) {
        if (empty($id)) {
          continue;
        }
        /** @var PluginInterface $upd */
        $upd = $manager->createInstance($id);
        $upd->setConfiguration(isset($settings['details'][$type][$id]) ?
          $settings['details'][$type][$id] :
          []
        );

        $condition = ['select#edit-' . $type . '-drd-upd-type-' . $type => ['value' => $id]];
        $upd->setConfigFormContext('drd_update_method', $condition);
        $form['drd_update_method'][$type][$id] = [
          '#type' => 'container',
          '#states' => [
            'visible' => $condition,
          ],
        ];
        $form['drd_update_method'][$type][$id] += $upd->buildConfigurationForm($form, $form_state);
        $this->plugins[$type][$id] = $upd;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateGlobalForm(array &$form, FormStateInterface $form_state) {
    $current = [
      'storage' => $form_state->getValue(['storage', 'drd_upd_type_storage']),
      'build' => $form_state->getValue(['build', 'drd_upd_type_build']),
      'process' => $form_state->getValue(['process', 'drd_upd_type_process']),
      'test' => $form_state->getValue(['test', 'drd_upd_type_test']),
      'deploy' => $form_state->getValue(['deploy', 'drd_upd_type_deploy']),
      'finish' => $form_state->getValue(['finish', 'drd_upd_type_finish']),
    ];
    if (!empty($current['storage'])) {
      foreach ($this->plugins as $type => $plugins) {
        foreach ($plugins as $id => $plugin) {
          /** @var PluginInterface $plugin */
          if ($id == $current[$type]) {
            $plugin->validateConfigurationForm($form, $form_state);
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function globalFormValues(array $form, FormStateInterface $form_state) {
    $settings = [
      'current' => [
        'storage' => $form_state->getValue(['storage', 'drd_upd_type_storage']),
        'build' => $form_state->getValue(['build', 'drd_upd_type_build']),
        'process' => $form_state->getValue(['process', 'drd_upd_type_process']),
        'test' => $form_state->getValue(['test', 'drd_upd_type_test']),
        'deploy' => $form_state->getValue(['deploy', 'drd_upd_type_deploy']),
        'finish' => $form_state->getValue(['finish', 'drd_upd_type_finish']),
      ],
      'details' => [],
    ];
    foreach ($this->plugins as $type => $plugins) {
      foreach ($plugins as $id => $plugin) {
        /** @var PluginInterface $plugin */
        $plugin->submitConfigurationForm($form, $form_state);
        $settings['details'][$type][$id] = $plugin->getConfiguration();
      }
    }
    return $settings;
  }

}
