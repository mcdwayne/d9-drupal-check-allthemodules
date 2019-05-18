<?php

namespace Drupal\field_tools\Form;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field_tools\DisplayCloner;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to clone displays from an entity bundle.
 */
class EntityDisplayBulkCloneForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The entity display repository service.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The query factory to create entity queries.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * The field cloner.
   *
   * @var \Drupal\field_tools\DisplayCloner
   */
  protected $displayCloner;

  /**
   * Creates a Clone instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository service.
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The entity query factory.
   * @param \Drupal\field_tools\DisplayCloner $display_cloner
   *   The display cloner.
   */
  public function __construct(
      EntityTypeManagerInterface $entity_type_manager,
      EntityTypeBundleInfoInterface $entity_type_bundle_info,
      EntityDisplayRepositoryInterface $entity_display_repository,
      QueryFactory $query_factory,
      DisplayCloner $display_cloner) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->queryFactory = $query_factory;
    $this->displayCloner = $display_cloner;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_display.repository'),
      $container->get('entity.query'),
      $container->get('field_tools.display_cloner')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'field_tools_displays_clone_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL, $bundle = NULL) {
    $form['displays']['entity_form_display'] = array(
      '#title' => t('Form displays to clone'),
      '#type' => 'checkboxes',
      '#options' => $this->getDisplayOptions('entity_form_display', $entity_type_id, $bundle),
      '#description' => t("Select form displays to clone onto one or more bundles."),
    );

    $form['displays']['entity_view_display'] = array(
      '#title' => t('View displays to clone'),
      '#type' => 'checkboxes',
      '#options' => $this->getDisplayOptions('entity_view_display', $entity_type_id, $bundle),
      '#description' => t("Select view displays to clone onto one or more bundles."),
    );

    $entity_type_bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
    $destination_bundle_options = [];
    foreach ($entity_type_bundles as $bundle_id => $bundle_info) {
      if ($bundle_id == $bundle) {
        continue;
      }

      $destination_bundle_options[$bundle_id] = $bundle_info['label'];
    }
    natcasesort($destination_bundle_options);

    $form['destination_bundles'] = [
      '#type' => 'checkboxes',
      '#title' => t("Bundle to clone displays to"),
      '#options' => $destination_bundle_options,
    ];

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Clone displays'),
    );

    return $form;
  }

  /**
   * Get the form options for a display type.
   *
   * @param $type
   *  The entity type ID of the display type.
   * @param $entity_type_id
   *  The target entity type ID of the displays.
   * @param $bundle
   *  The target bundle.
   *
   * @return
   *  An array of form options.
   */
  protected function getDisplayOptions($type, $entity_type_id, $bundle) {
    $display_ids = $this->queryFactory->get($type)
      ->condition('targetEntityType', $entity_type_id)
      ->condition('bundle', $bundle)
      ->execute();
    $form_displays = $this->entityTypeManager->getStorage($type)->loadMultiple($display_ids);

    // Unfortunately, getDisplayModesByEntityType() is protected :(
    if ($type == 'entity_form_display') {
      $mode_options = $this->entityDisplayRepository->getFormModeOptions($entity_type_id);
    }
    else {
      $mode_options = $this->entityDisplayRepository->getViewModeOptions($entity_type_id);
    }

    $form_display_options = [];
    foreach ($form_displays as $id => $form_display) {
      // The label() method of displays returns NULL always, so we get the label
      // from the related mode.
      $form_display_options[$id] = $mode_options[$form_display->getMode()];
    }
    asort($form_display_options);

    return $form_display_options;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $destination_bundles = array_filter($form_state->getValue('destination_bundles'));

    $form_display_ids = array_filter($form_state->getValue('entity_form_display'));
    $form_displays_to_clone = $this->entityTypeManager->getStorage('entity_form_display')->loadMultiple($form_display_ids);
    foreach ($form_displays_to_clone as $form_display) {
      foreach ($destination_bundles as $destination_bundle) {
        $this->displayCloner->cloneDisplay($form_display, $destination_bundle);
      }
    }

    $view_display_ids = array_filter($form_state->getValue('entity_view_display'));
    $view_displays_to_clone = $this->entityTypeManager->getStorage('entity_view_display')->loadMultiple($view_display_ids);
    foreach ($view_displays_to_clone as $view_display) {
      foreach ($destination_bundles as $destination_bundle) {
        $this->displayCloner->cloneDisplay($view_display, $destination_bundle);
      }
    }

    drupal_set_message(t("The displays have been cloned."));
  }

}
