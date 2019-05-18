<?php

namespace Drupal\mason_ui\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Config\Entity\DraggableListBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\mason\MasonManagerInterface;

/**
 * Provides a listing of Mason optionsets.
 */
class MasonListBuilder extends DraggableListBuilder {

  /**
   * The mason manager.
   *
   * @var \Drupal\mason\MasonManagerInterface
   */
  protected $manager;

  /**
   * Constructs a new MasonListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\mason\MasonManagerInterface $manager
   *   The mason manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, MasonManagerInterface $manager) {
    parent::__construct($entity_type, $storage);
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('mason.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mason_list_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = array(
      'label'    => $this->t('Optionset'),
      'gutter'   => $this->t('Gutter'),
      'layout'   => $this->t('Layout'),
      'promoted' => $this->t('Promoted items'),
      'ratio'    => $this->t('Ratio'),
    );

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = Html::escape($entity->label());
    foreach (['gutter', 'layout', 'promoted', 'ratio'] as $key) {
      $row[$key]['#markup'] = $key == 'promoted' ? count($entity->getOption($key)) : $entity->getOption($key);
    }
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    if (isset($operations['edit'])) {
      $operations['edit']['title'] = $this->t('Configure');
    }

    $operations['duplicate'] = array(
      'title'  => $this->t('Duplicate'),
      'weight' => 15,
      'url'    => $entity->toUrl('duplicate-form'),
    );

    if ($entity->id() == 'default') {
      unset($operations['delete'], $operations['edit']);
    }

    return $operations;
  }

  /**
   * Adds some descriptive text to the mason optionsets list.
   *
   * @return array
   *   Renderable array.
   *
   * @see admin/config/development/configuration/single/export
   */
  public function render() {
    $build['description'] = array(
      '#markup' => $this->t("<p>Manage the Mason optionsets. Optionsets are Config Entities.</p><p>By default, when this module is enabled, a single optionset is created from configuration. Install Mason example module to speed up by cloning them. Use the Operations column to edit, clone and delete optionsets.</p>"),
    );

    $build[] = parent::render();
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    drupal_set_message($this->t('The optionsets order has been updated.'));
  }

}
