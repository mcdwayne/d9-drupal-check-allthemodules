<?php

namespace Drupal\update_runner\Form;

use Drupal\update_runner\Plugin\UpdateRunnerProcessorPluginManager;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class UpdateRunnerProcessorForm.
 */
class UpdateRunnerProcessorForm extends EntityForm {

  protected $processorPluginManager;

  /**
   * Class constructor.
   *
   * @param \Drupal\update_runner\Plugin\UpdateRunnerProcessorPluginManager $processorPluginManager
   *   Plugin manager.
   */
  public function __construct(UpdateRunnerProcessorPluginManager $processorPluginManager) {
    $this->processorPluginManager = $processorPluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      // Load the service required to construct this class.
      $container->get('plugin.manager.update_runner_processor_plugin')
    );

  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $availableProcessors = $this->processorPluginManager->getDefinitions();
    $pluginOptions = [];
    $pluginOptions[''] = '';

    if (!empty($availableProcessors)) {
      foreach ($availableProcessors as $id => $availableProcessor) {
        $pluginOptions[$availableProcessor['id']] = $availableProcessor['label'];
      }
    }

    // The entity being created.
    $update_runner_processor = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $update_runner_processor->label(),
      '#description' => $this->t("Label for the Automatic updates processor."),
      '#required' => TRUE,
    ];

    if (!empty($this->entity->get('plugin'))) {
      $pluginType = $this->entity->get('plugin');
    }
    elseif ($form_state->getValue('plugin')) {
      $pluginType = $form_state->getValue('plugin');
    }

    if (!empty($pluginType)) {
      $form['plugin'] = [
        '#type' => 'select',
        '#title' => t('Plugin to use'),
        '#options' => $pluginOptions,
        '#default_value' => $pluginType,
        '#disabled' => 'disabled',
        '#required' => TRUE,
      ];

      $form['container'] = [
        '#prefix' => '<div id="plugin-container-options">',
        '#suffix' => '</div>',
      ];

      $plugin = $this->processorPluginManager->createInstance($pluginType);
      $form['container'] = array_merge($form['container'], $plugin->formOptions($this->entity));
    }
    else {
      $form['plugin'] = [
        '#type' => 'select',
        '#title' => t('Plugin to use'),
        '#options' => $pluginOptions,
        '#required' => TRUE,
        '#ajax'   => [
          'callback' => '::pluginOptionsAjaxCallback',
          'wrapper'  => 'plugin-container-options',
        ],
      ];

      $form['container'] = [
        '#prefix' => '<div id="plugin-container-options">',
        '#suffix' => '</div>',
      ];
    }

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $update_runner_processor->id(),
      '#machine_name' => [
        'exists' => '\Drupal\update_runner\Entity\UpdateRunnerProcessor::load',
      ],
      '#disabled' => !$update_runner_processor->isNew(),
    ];

    return $form;
  }

  /**
   * Callback function for options rendering.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return mixed
   *   Rendered form.
   */
  public function pluginOptionsAjaxCallback(array $form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
    return $form['container'];
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $pluginType = $this->entity->get('plugin');
    $plugin = $this->processorPluginManager->createInstance($pluginType);

    $plugin->validate($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $update_runner_processor = $this->entity;

    $pluginType = $this->entity->get('plugin');
    $plugin = $this->processorPluginManager->createInstance($pluginType);

    $optionsKeys = $plugin->optionsKeys();
    if (!empty($optionsKeys)) {
      foreach ($optionsKeys as $key) {
        $data[$key] = $form_state->getValue($key);
      }
    }

    $this->entity->set('data', serialize($data));
    $status = $update_runner_processor->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Automatic updates processor.', [
          '%label' => $update_runner_processor->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Automatic updates processor.', [
          '%label' => $update_runner_processor->label(),
        ]));
    }

    $form_state->setRedirectUrl($update_runner_processor->toUrl('collection'));
  }

}
