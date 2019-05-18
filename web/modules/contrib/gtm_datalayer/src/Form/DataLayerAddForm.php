<?php

namespace Drupal\gtm_datalayer\Form;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\gtm_datalayer\Plugin\DataLayerProcessorPluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides add form for dataLayer instance forms.
 */
class DataLayerAddForm extends EntityForm {

  /**
   * The GTM dataLayer Processor plugin manager.
   *
   * @var \Drupal\gtm_datalayer\Plugin\DataLayerProcessorPluginManagerInterface
   */
  protected $datalayerProcessorManager;

  /**
   * Constructs a DataLayerAddForm object.
   *
   * @param \Drupal\gtm_datalayer\Plugin\DataLayerProcessorPluginManagerInterface $datalayer_processor_manager
   *   The GTM dataLayer Processor plugin manager.
   */
  public function __construct(DataLayerProcessorPluginManagerInterface $datalayer_processor_manager) {
    $this->datalayerProcessorManager = $datalayer_processor_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.gtm_datalayer.processor')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\gtm_datalayer\Entity\DataLayerInterface $datalayer */
    $datalayer = $this->entity;

    $form = parent::form($form, $form_state);

    $form['#title'] = $this->t('Add dataLayer');
    $form['label'] = [
      '#title' => $this->t('Name'),
      '#type' => 'textfield',
      '#default_value' => $datalayer->label(),
      '#description' => $this->t('The human-readable name of this dataLayer. This name must be unique.'),
      '#required' => TRUE,
      '#size' => 30,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $datalayer->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name' => [
        'exists' => ['Drupal\gtm_datalayer\Entity\DataLayer', 'load'],
        'source' => ['label'],
      ],
      '#description' => $this->t('A unique machine-readable name for this dataLayer. It must only contain lowercase letters, numbers, and underscores.'),
    ];
    $form['description'] = [
      '#title' => $this->t('Description'),
      '#type' => 'textarea',
      '#default_value' => $datalayer->getDescription(),
    ];
    $form['plugin'] = [
      '#type' => 'select',
      '#options' => $this->getProcessorPlugins(),
      '#title' => t('dataLayer processor plugin'),
      '#default_value' => $datalayer->getPlugin(),
      '#required' => TRUE,
    ];
    // Hidden weight setting.
    $weight = $datalayer->isNew() ? 0 : $datalayer->getWeight();
    $form['weight'] = [
      '#type' => 'hidden',
      '#default_value' => $weight,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $status = $this->save($form, $form_state);

    if ($status == SAVED_NEW) {
      drupal_set_message($this->t('The dataLayer configuration has been saved.'));
    }
    $form_state->setRedirect('entity.gtm_datalayer.edit_form', ['gtm_datalayer' => $this->entity->id()]);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    $actions['submit']['#value'] = $this->t('Add conditions');

    return $actions;
  }

  /**
   * Get the processor plugins list options.
   *
   * @param string $group
   *   The group name to filter.
   *
   * @return array
   *   The processor array.
   */
  protected function getProcessorPlugins($group = 'global') {
    $datalayer_processors = [];
    foreach ($this->datalayerProcessorManager->getDefinitions() as $procesor_name => $processor) {
      if (Unicode::strcasecmp($processor['group']->getUntranslatedString(), $group) === 0) {
        $datalayer_processors[$procesor_name] = $processor['label']->__toString();
      }
    }

    return $datalayer_processors;
  }

}
