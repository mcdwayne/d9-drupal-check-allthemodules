<?php

namespace Drupal\evergreen\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\evergreen\Entity\EvergreenConfig;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Form handler for the evergreen config add and edit forms.
 */
class EvergreenConfigForm extends EntityForm {

  /**
   * Constructs an EvergreenConfigForm object.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The entity query.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(QueryFactory $entity_query, EntityTypeManagerInterface $entity_type_manager, PluginManagerInterface $plugin_manager, PluginManagerInterface $evergreen_manager, ConfigFactoryInterface $config_factory) {
    $this->entityQuery = $entity_query;
    $this->entityTypeManager = $entity_type_manager;
    $this->pluginManager = $plugin_manager;
    $this->evergreenManager = $evergreen_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.evergreen_expiry_provider'),
      $container->get('plugin.manager.evergreen'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $evergreen = $this->entity;
    $options = $this->getEntityTypeOptions();

    $form['intro'] = [
      '#markup' => $this->t('<p>Configure the evergreen settings for a content entity type. These settings will be the default settings for content but can be overridden for each piece of content.</p>'),
    ];

    if ($evergreen && $evergreen->id()) {
      $form['id'] = [
        '#type' => 'hidden',
        '#default_value' => $evergreen->id(),
      ];
    }

    $form[EvergreenConfig::ENTITY_TYPE] = [
      '#type' => 'select',
      '#title' => $this->t('Entity type'),
      '#options' => $options,
      '#ajax' => [
        'callback' => [$this, 'updateBundlesCallback'],
        'wrapper' => ['bundle-options-dropdown'],
      ],
      '#default_value' => $evergreen->getEvergreenEntityType(),
      '#required' => TRUE,
    ];

    // get the bundle options
    $bundle_options = $this->getBundleOptions($form, $form_state);

    $form[EvergreenConfig::BUNDLE] = [
      '#type' => 'select',
      '#title' => $this->t('Bundle'),
      '#options' => $bundle_options,
      '#prefix' => '<div id="bundle-options-dropdown">',
      '#suffix' => '</div>',
      '#default_value' => $evergreen->getEvergreenBundle(),
      '#required' => TRUE,
      '#attached' => [
        'library' => ['evergreen/evergreen_config_form'],
      ],
    ];

    $form[EvergreenConfig::STATUS] = [
      '#type' => 'select',
      '#title' => $this->t('Default status'),
      '#description' => 'Set the default status for new content. Evergreen content does not expire.',
      '#options' => [
        EVERGREEN_STATUS_EVERGREEN => 'Evergreen',
        0 => 'Content expires',
      ],
      '#default_value' => $evergreen->getEvergreenStatus(),
    ];

    // get the expiry option provider
    $provider = $evergreen->get(EvergreenConfig::EXPIRY_PROVIDER);
    if (!$provider) {
      $settings = $this->configFactory->get('evergreen.settings');
      $provider = $settings->get('expiry.default_expiry_provider');
    }
    // $provider = 'month_options_expiry';

    $form[EvergreenConfig::EXPIRY_PROVIDER] = [
      '#type' => 'hidden',
      '#default_value' => $provider,
    ];

    $instance = $this->pluginManager->createInstance($provider);

    $form[EvergreenConfig::EXPIRY] = $instance->getFormElement(
      $evergreen->getEvergreenExpiry(),
      ['label' => 'Default expiration time']
    );

    $form['sync'] = [
      '#type' => 'checkbox',
      '#title' => 'Synchronize this configuration with existing entities',
      '#description' => "Add the default configuration to all existing entities that do not yet have their own configuration. Content without it's own configuration will use the defaults.",
      '#default_value' => 0,
    ];

    return $form;
  }

  /**
   * Get bundle options.
   *
   * TODO block out bundles that have already been configured if this is a
   * new entity...
   */
  public function getBundleOptions(array $form, FormStateInterface $form_state) {
    $entity_type = isset($form[EvergreenConfig::ENTITY_TYPE]['#default_value']) ? $form[EvergreenConfig::ENTITY_TYPE]['#default_value'] : '';
    if ($form_state->getValue(EvergreenConfig::ENTITY_TYPE)) {
      $entity_type = $form_state->getValue(EvergreenConfig::ENTITY_TYPE);
    }

    if (!$entity_type) {
      return [];
    }

    $plugin = $this->evergreenManager->createInstance($entity_type);
    return $plugin->getBundleOptions();
  }

  /**
   * Get the options for selecting an entity type.
   *
   * TODO block out bundles that have already been configured if this is a
   * new entity...
   */
  public function getEntityTypeOptions() {
    $plugins = $this->evergreenManager->getDefinitions();

    $options = [];
    foreach ($plugins as $key => $plugin) {
      $options[$key] = $plugin['label'];
    }
    asort($options);
    return $options;

    // $types = $this->entityTypeManager->getDefinitions();
    //
    // $options = [];
    // $first = NULL;
    // foreach ($types as $entity => $details) {
    //   if (!$details instanceof ContentEntityType) {
    //     continue;
    //   }
    //
    //   $bundles = entity_get_bundles($entity);
    //   if (!$bundles) {
    //     continue;
    //   }
    //
    //
    //   if (empty($options)) {
    //     $first = $entity;
    //   }
    //   $options[$entity] = $this->t('%label', ['%label' => $details->getLabel()]);
    // }
    // asort($options);
    //
    // return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $evergreen = $this->entity;
    $evergreen
      ->generateID()
      ->checkBundle()
      ->checkExpiry();
    $status = $evergreen->save();

    $vars = [
      '%entity' => $evergreen->getEvergreenEntityType(),
      '%bundle' => $evergreen->getEvergreenBundle(),
    ];

    $form_state->setRedirect('entity.evergreen_config.collection');
    if ($status) {
      drupal_set_message($this->t('Saved the evergreen configuration for %entity.%bundle.', $vars));
      if ($form_state->getValue('sync', FALSE)) {
        $entities = $this->entityQuery->get($evergreen->getEvergreenEntityType())
          ->condition('type', $evergreen->getEvergreenBundle())
          ->execute();
        $batch = [
          'title' => $this->t('Synchronizing evergreen configuration'),
          'operations' => array_map(function ($entity) use ($evergreen) {
            return [
              'evergreen_sync',
              [$entity, $evergreen],
            ];
          }, $entities),
          'finished' => 'evergreen_sync_finished',
        ];
        batch_set($batch);
      }
    }
    else {
      drupal_set_message($this->t('The %label Example was not saved.', $vars));
    }
  }

  /**
   * Update the bundles list if the entity type changes.
   */
  public function updateBundlesCallback(array $form) {
    return $form[EvergreenConfig::BUNDLE];
  }

  /**
   * Helper function to check whether an Example configuration entity exists.
   */
  public function exist($id) {
    $entity = $this->entityQuery->get('evergreen_config')
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

}
