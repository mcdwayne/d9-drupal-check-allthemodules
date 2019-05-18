<?php

namespace Drupal\evergreen\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\evergreen\Entity\EvergreenConfig;

/**
 * Form handler for the evergreen config add and edit forms.
 */
class EvergreenContentForm extends EntityForm {

  /**
   * Constructs an EvergreenConfigForm object.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The entity query.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(QueryFactory $entity_query, EntityTypeManagerInterface $entity_type_manager) {
    $this->entityQuery = $entity_query;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $evergreen = $this->entity;
    $options = $this->getEntityTypeOptions();

    if ($evergreen && $evergreen->id()) {
      $form['id'] = [
        '#type' => 'hidden',
        '#default_value' => $evergreen->id(),
      ];
    }

    // $form[EvergreenConfig::ENTITY_TYPE] = [
    //   '#type' => 'select',
    //   '#title' => $this->t('Entity type'),
    //   '#options' => $options,
    //   '#ajax' => [
    //     'callback' => [$this, 'updateBundlesCallback'],
    //     'wrapper' => ['bundle-options-dropdown'],
    //   ],
    //   '#default_value' => $evergreen->getEvergreenEntityType(),
    //   '#required' => TRUE,
    // ];
    //
    // // get the bundle options
    // $bundle_options = $this->getBundleOptions($form, $form_state, $options);
    //
    // $form[EvergreenConfig::BUNDLE] = [
    //   '#type' => 'select',
    //   '#title' => $this->t('Bundle'),
    //   '#options' => $bundle_options,
    //   '#prefix' => '<div id="bundle-options-dropdown">',
    //   '#suffix' => '</div>',
    //   '#default_value' => $evergreen->getEvergreenBundle(),
    //   '#required' => TRUE,
    //   '#attached' => [
    //     'library' => ['evergreen/evergreen_config_form'],
    //   ],
    // ];
    //
    // $form[EvergreenConfig::STATUS] = [
    //   '#type' => 'select',
    //   '#title' => $this->t('Default status'),
    //   '#options' => [
    //     EVERGREEN_STATUS_EVERGREEN => 'Evergreen',
    //     0 => 'Content expires',
    //   ],
    //   '#default_value' => $evergreen->getEvergreenStatus(),
    // ];
    //
    // $form[EvergreenConfig::EXPIRY] = [
    //   '#type' => 'textfield',
    //   '#title' => $this->t('Default expiration time'),
    //   '#default_value' => evergreen_get_readable_expiry($evergreen->getEvergreenExpiry()),
    // ];

    return $form;
  }

  /**
   * Get bundle options.
   *
   * TODO block out bundles that have already been configured if this is a
   * new entity...
   */
  public function getBundleOptions(array $form, FormStateInterface $form_state, array $entity_options) {
    $entity_type = isset($form[EvergreenConfig::ENTITY_TYPE]['#default_value']) ? $form[EvergreenConfig::ENTITY_TYPE]['#default_value'] : '';
    if ($form_state->getValue(EvergreenConfig::ENTITY_TYPE)) {
      $entity_type = $form_state->getValue(EvergreenConfig::ENTITY_TYPE);
    }

    $bundle_options = [];
    $bundles = entity_get_bundles($entity_type);
    if ($bundles) {
      foreach ($bundles as $bundle => $bundle_details) {
        $bundle_options[$entity_type . '.' . $bundle] = $this->t('%bundle', ['%bundle' => $bundle_details['label']]);
      }
    }
    return $bundle_options;
  }

  /**
   * Get the options for selecting an entity type.
   *
   * TODO block out bundles that have already been configured if this is a
   * new entity...
   */
  public function getEntityTypeOptions() {
    $types = $this->entityTypeManager->getDefinitions();

    $options = [];
    $first = NULL;
    foreach ($types as $entity => $details) {
      if (!$details instanceof ContentEntityType) {
        continue;
      }

      $bundles = entity_get_bundles($entity);
      if (!$bundles) {
        continue;
      }


      if (empty($options)) {
        $first = $entity;
      }
      $options[$entity] = $this->t('%label', ['%label' => $details->getLabel()]);
    }
    asort($options);

    return $options;
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
    if ($status) {
      drupal_set_message($this->t('Saved the evergreen configuration for %entity.%bundle.', $vars));
    }
    else {
      drupal_set_message($this->t('The %label Example was not saved.', $vars));
    }

    $form_state->setRedirect('entity.evergreen_config.collection');
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
