<?php

namespace Drupal\warmer\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\ConfigFormBaseTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\warmer\Plugin\WarmerPluginBase;
use Drupal\warmer\Plugin\WarmerPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings form for the warmer module.
 *
 * This form aggregates the configuration of all the warmer plugins.
 */
final class SettingsForm extends ConfigFormBase {

  /**
   * The plugin manager for the warmers.
   *
   * @var \Drupal\warmer\Plugin\WarmerPluginManager
   */
  private $warmerManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, WarmerPluginManager $warmer_manager) {
    $this->setConfigFactory($config_factory);
    $this->warmerManager = $warmer_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.warmer')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['warmer.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'warmer_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['help'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Configure the cache warming behaviors. Each cache warmer is a plugin that may contain specific settings. They are all configured here.'),
    ];
    $warmers = $this->warmerManager->getWarmers();
    $form['warmers'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Warmers'),
    ];
    $subform_state = SubformState::createForSubform(
      $form,
      $form,
      $form_state
    );
    $form += array_reduce(
      $warmers,
      function ($carry, WarmerPluginBase $warmer) use ($subform_state) {
        return $warmer->buildConfigurationForm($carry, $subform_state) + $carry;
      },
      $form
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $warmers = $this->warmerManager->getWarmers();
    array_map(function (WarmerPluginBase $warmer) use (&$form, $form_state) {
      $id = $warmer->getPluginId();
      $subform_state = SubformState::createForSubform($form[$id], $form, $form_state);
      $warmer->submitConfigurationForm($form[$id], $subform_state);
    }, $warmers);
    $name = $this->getEditableConfigNames();
    $config_name = reset($name);
    $config = $this->configFactory()->getEditable($config_name);
    $warmer_configs = array_reduce($warmers, function ($carry, WarmerPluginBase $warmer) {
      $carry[$warmer->getPluginId()] = $warmer->getConfiguration();
      return $carry;
    }, []);
    $config->set('warmers', $warmer_configs);
    $config->save();
    $message = $this->t('Settings saved for plugin(s): %names', [
      '%names' => implode(', ', array_map(function (WarmerPluginBase $warmer) {
        return $warmer->getPluginDefinition()['label'];
      }, $warmers))
    ]);
    $this->messenger()->addStatus($message);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    array_map(function (WarmerPluginBase $warmer) use (&$form, $form_state) {
      $id = $warmer->getPluginId();
      $subform_state = SubformState::createForSubform($form[$id], $form, $form_state);
      $warmer->validateConfigurationForm($form[$id], $subform_state);
    }, $this->warmerManager->getWarmers());
  }

}
