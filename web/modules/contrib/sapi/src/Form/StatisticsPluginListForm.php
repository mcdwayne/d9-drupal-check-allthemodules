<?php

namespace Drupal\sapi\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class StatisticsPluginListForm.
 *
 * @package Drupal\sapi\Form
 */
class StatisticsPluginListForm extends ConfigFormBase {

  /**
   * The statistics action type plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface $sapi_action_type_manager
   */
  protected $sapi_action_type_manager;

  /**
   * The statistics action handler plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface $sapi_action_handler_manager
   */
  protected $sapi_action_handler_manager;

  /**
   * Constructs a new StatisticsPluginListForm form.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Component\Plugin\PluginManagerInterface $sapi_action_handler_manager
   * @param \Drupal\Component\Plugin\PluginManagerInterface $sapi_action_type_manager
   */
  public function __construct(ConfigFactoryInterface $config_factory,  PluginManagerInterface $sapi_action_type_manager, PluginManagerInterface $sapi_action_handler_manager) {
    parent::__construct($config_factory);
    $this->sapi_action_type_manager = $sapi_action_type_manager;
    $this->sapi_action_handler_manager = $sapi_action_handler_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.sapi_action_type'),
      $container->get('plugin.manager.sapi_action_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'sapi.entity_events',
      'sapi.action_types',
      'sapi.action_handlers',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'statistics_handler_list_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['plugins'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Statistics plugins'),
      '#description' => $this->t('Select the statistics plugins that you want to have enabled.'),
    ];

    $form['plugins']['action_types'] = [
      '#type' => 'table',
      '#header' => [
        'id' => $this->t('ID'),
        'label' => $this->t('Label')
      ],
      '#empty' => $this->t('There are no plugins yet.'),
      '#tableselect' => TRUE,
      '#default_value' => $this->config('sapi.action_types')->get('enabled'),
    ];
    // Loop through the statistics plugins.
    foreach ($this->sapi_action_type_manager->getDefinitions() as $pluginDefinition) {
      $id = $pluginDefinition['id'];
      $label = $pluginDefinition['label'];

      $form['plugins']['action_types'][$id] = [
        'id' => ['#plain_text' => $id],
        'label' => ['#plain_text' => $label]
      ];
    }

    $form['plugins']['action_handlers'] = [
      '#type' => 'table',
      '#header' => [
        'id' => $this->t('ID'),
        'label' => $this->t('Label')
      ],
      '#empty' => $this->t('There are no plugins yet.'),
      '#tableselect' => TRUE,
      '#default_value' => $this->config('sapi.action_handlers')->get('enabled'),
    ];
    // Loop through the statistics plugins.
    foreach ($this->sapi_action_handler_manager->getDefinitions() as $pluginDefinition) {
      $id = $pluginDefinition['id'];
      $label = $pluginDefinition['label'];

      $form['plugins']['action_handlers'][$id] = [
        'id' => ['#plain_text' => $id],
        'label' => ['#plain_text' => $label]
      ];
    }


    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('sapi.action_types')
      ->set('enabled', array_filter($form_state->getValue('action_types')))
      ->save();

    $this->config('sapi.action_handlers')
      ->set('enabled', array_filter($form_state->getValue('action_handlers')))
      ->save();
  }

}
