<?php

namespace Drupal\outlayer_ui\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Messenger\Messenger;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\outlayer\OutlayerManagerInterface;

/**
 * Provides a listing of Outlayer optionsets.
 */
class OutlayerListBuilder extends DraggableListBuilder {

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * The outlayer manager.
   *
   * @var \Drupal\outlayer\OutlayerManagerInterface
   */
  protected $manager;

  /**
   * Constructs a new OutlayerListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param Drupal\Core\Messenger\Messenger $messenger
   *   The messenger class.
   * @param \Drupal\outlayer\OutlayerManagerInterface $manager
   *   The outlayer manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, Messenger $messenger, OutlayerManagerInterface $manager) {
    parent::__construct($entity_type, $storage);
    $this->messenger = $messenger;
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('messenger'),
      $container->get('outlayer.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'outlayer_list_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'label'      => $this->t('Optionset'),
      'layoutMode' => $this->t('layoutMode'),
    ];

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = Html::escape($entity->label());
    $row['layoutMode']['#markup'] = $entity->getOption('layoutMode');

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

    $operations['duplicate'] = [
      'title'  => $this->t('Duplicate'),
      'weight' => 15,
      'url'    => $entity->toUrl('duplicate-form'),
    ];

    if ($entity->id() == 'default') {
      unset($operations['delete'], $operations['edit']);
    }

    return $operations;
  }

  /**
   * Adds some descriptive text to the outlayer optionsets list.
   *
   * @return array
   *   Renderable array.
   *
   * @see admin/config/development/configuration/single/export
   */
  public function render() {
    $build['description'] = [
      '#markup' => $this->t("<p>Manage the Outlayer optionsets. Optionsets are Config Entities.</p><p>By default, when this module is enabled, few optionsets are created from configuration. Use the Operations column to edit, clone and delete optionsets.<br /><strong>Important!</strong> Avoid overriding Default optionset as it is meant for Default -- checking and cleaning. Use Duplicate instead. Otherwise messes are yours.<br />Outlayer doesn't need Outlayer UI to run. It is always safe to uninstall Outlayer UI once done with optionsets.</p>"),
    ];

    $build[] = parent::render();
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->messenger->addMessage($this->t('The optionsets order has been updated.'));
  }

}
