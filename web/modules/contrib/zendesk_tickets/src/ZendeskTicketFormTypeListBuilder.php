<?php

namespace Drupal\zendesk_tickets;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Datetime\DateFormatter;

/**
 * Defines a class to build a listing of Zendesk ticket form type entities.
 *
 * @see \Drupal\zendesk_tickets\Entity\ZendeskTicketFormType
 */
class ZendeskTicketFormTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected $limit;

  /**
   * The date formatter service.
   *
   * @var DateFormatter
   */
  protected $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('date.formatter')
    );
  }

  /**
   * Constructs a new EntityListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param DateFormatter $dateFormatter
   *   The date formatter service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, DateFormatter $dateFormatter) {
    parent::__construct($entity_type, $storage);
    $this->dateFormatter = $dateFormatter;
    $this->limit = FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\views_ui\ViewListBuilder
   */
  public function load() {
    $entities = array(
      'enabled' => array(),
      'disabled' => array(),
    );
    foreach (parent::load() as $entity) {
      if ($entity->status()) {
        $entities['enabled'][] = $entity;
      }
      else {
        $entities['disabled'][] = $entity;
      }
    }
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = t('Id');
    $header['label'] = t('Label');
    $header['name'] = t('Name');
    $header['imported'] = t('Imported');
    $header['third_party_settings'] = t('Other settings');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['id']['data'] = ['#markup' => $entity->id()];

    $row['label'] = [
      'data' => $entity->label(),
      'class' => array('menu-label'),
    ];

    $row['name']['data'] = [
      '#plain_text' => $entity->getMachineName(),
    ];

    $row['imported']['data'] = [
      '#plain_text' => $entity->getImportedTime() ? $this->dateFormatter->format($entity->getImportedTime(), 'short') : '---',
    ];

    if ($entity->get('third_party_settings')) {
      $provider_items = [];
      foreach ($entity->get('third_party_settings') as $provider => $provider_settings) {
        foreach ($provider_settings as $provider_setting_key => $provider_setting_value) {
          $provider_items[] = "{$provider}.{$provider_setting_key} = {$provider_setting_value}";
        }
      }

      $row['third_party_settings']['data'] = [
        '#theme' => 'item_list',
        '#items' => $provider_items,
      ];
    }
    else {
      $row['third_party_settings']['data'] = '---';
    }

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    if ($entity->access('submit')) {
      $operations['view'] = [
        'title' => $this->t('View form'),
        'weight' => -100,
        'url' => $entity->urlInfo(),
      ];
    }

    // Add AJAX functionality to enable/disable operations.
    foreach (array('enable', 'disable') as $op) {
      if (isset($operations[$op])) {
        if ($entity->access($op)) {
          $operations[$op]['url'] = $entity->urlInfo($op);
          // Enable and disable operations should use AJAX.
          $operations[$op]['attributes']['class'][] = 'use-ajax';
        }
        else {
          unset($operations[$op]);
        }
      }
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\views_ui\ViewListBuilder
   */
  public function render() {
    $entities = $this->load();
    $list['#type'] = 'container';
    $list['#attributes']['id'] = 'zendesk-ticket-form-types-entity-list';
    $list['#attached']['library'][] = 'core/drupal.ajax';

    $list['enabled']['heading'] = [
      '#markup' => '<h2>' . $this->t('Enabled', array(), array('context' => 'Plural')) . '</h2>',
    ];
    $list['disabled']['heading'] = [
      '#markup' => '<h2>' . $this->t('Disabled', array(), array('context' => 'Plural')) . '</h2>',
    ];

    foreach (array('enabled', 'disabled') as $status) {
      $list[$status]['#type'] = 'container';
      $list[$status]['#attributes'] = array('class' => array('zendesk-ticket-form-type-list-section', $status));
      $list[$status]['table'] = array(
        '#type' => 'table',
        '#attributes' => array(
          'class' => array('zendesk-ticket-form-type-listing-table'),
        ),
        '#header' => $this->buildHeader(),
        '#rows' => array(),
      );
      foreach ($entities[$status] as $entity) {
        $list[$status]['table']['#rows'][$entity->id()] = $this->buildRow($entity);
      }
    }

    $list['enabled']['table']['#empty'] = $this->t('There are no enabled forms.');
    $list['disabled']['table']['#empty'] = $this->t('There are no disabled forms.');

    return $list;
  }

}
