<?php

namespace Drupal\entity_ui\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\LocalTaskManagerInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\entity_ui\Plugin\EntityTabContentManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the form to delete Entity tab entities.
 */
class EntityTabDeleteForm extends EntityConfirmFormBase {

  /**
   * The Entity Tab content plugin manager
   *
   * @var \Drupal\entity_ui\Plugin\EntityTabContentManager
   */
  protected $entityTabContentPluginManager;

  /**
   * The menu local task plugin manager.
   *
   * @var \Drupal\Core\Menu\LocalTaskManagerInterface
   */
  protected $menuLocalTaskPluginManager;

  /**
   * The router builder service.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routerBuilder;

  /**
   * Constructs a new EntityTabForm.
   *
   * @param \Drupal\entity_ui\Plugin\EntityTabContentManager
   *   The entity tab plugin manager.
   */
  public function __construct(
    EntityTabContentManager $entity_tab_content_manager,
    LocalTaskManagerInterface $plugin_manager_menu_local_task,
    RouteBuilderInterface $router_builder
    ) {
    $this->entityTabContentPluginManager = $entity_tab_content_manager;
    $this->menuLocalTaskPluginManager = $plugin_manager_menu_local_task;
    $this->routerBuilder = $router_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.entity_ui_tab_content'),
      $container->get('plugin.manager.menu.local_task'),
      $container->get('router.builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $target_entity_type_id = $this->entity->getTargetEntityTypeID();
    return Url::fromRoute("entity_ui.entity_tab.{$target_entity_type_id}.collection");
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();

    // Clear caches.
    $this->routerBuilder->setRebuildNeeded();
    $this->menuLocalTaskPluginManager->clearCachedDefinitions();

    \Drupal::messenger()->addMessage($this->t('Deleted @label.', [
      '@label' => $this->entity->label(),
    ]));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
