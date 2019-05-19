<?php

namespace Drupal\simple_amp\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityDisplayRepository;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure AMP Entity settings.
 */
class EntitySettingsForm extends ConfigFormBase {

  protected $entityTypeManager;
  protected $entityFieldManager;
  protected $entityDisplayRepository;

  /**
   * {@inheritDoc}
   */
  public function __construct(
      ConfigFactoryInterface $config_factory,
      EntityTypeManagerInterface $entity_type_manager,
      EntityFieldManagerInterface $entity_field_manager,
      EntityDisplayRepository $entity_display_repository
    )
  {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_amp_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['simple_amp.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('simple_amp.settings');

    $node_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();

    $form['node_types'] = [
      '#type'   => 'table',
      '#header' => [
        $this->t('Entity Type'),
        $this->t('Display Mode'),
      ],
      '#tableselect' => FALSE,
      '#tabledrag'   => FALSE,
    ];

    foreach ($node_types as $type => $info) {
      $view_modes = $this->entityDisplayRepository->getViewModeOptionsByBundle('node', $type);
      unset($view_modes['default']);
      $view_modes[''] = $this->t('Default');
      $form['node_types'][$type]['enable'] = [
        '#type'          => 'checkbox',
        '#title'         => $this->t('Enable AMP for @bundle', ['@bundle' => $info->label()]),
        '#default_value' => !empty($config->get($type . '_enable')) ? $config->get($type . '_enable') : '',
      ];
      $form['node_types'][$type]['view_mode'] = [
        '#type'          => 'select',
        '#options'       => $view_modes,
        '#default_value' => !empty($config->get($type . '_view_mode')) ? $config->get($type . '_view_mode') : '',
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('simple_amp.settings');
    $form_state->cleanValues();
    foreach ($form_state->getValues() as $key => $value) {
      if ($key == 'node_types') {
        foreach ($value as $type => $data) {
          $config->set($type . '_enable', $data['enable']);
          $config->set($type . '_view_mode', $data['view_mode']);
        }
      }
      else {
        $config->set($key, $value);
      }
    }
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
