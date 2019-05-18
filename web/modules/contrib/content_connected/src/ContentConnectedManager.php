<?php


namespace Drupal\content_connected;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Link;

/**
 * Class ContentConnectedManager.
 *
 * @package Drupal\content_connected
 */
class ContentConnectedManager implements ContentConnectedManagerInterface {

  use StringTranslationTrait;

  use DependencySerializationTrait {
    __wakeup as defaultWakeup;
    __sleep as defaultSleep;
  }

  /**
   * Entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Forum settings config object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity query factory service.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Drupal\Core\Extension\ModuleHandler definition.
   *
   * @var ModuleHandler
   */
  protected $moduleHandler;

  /**
   * Constructs the content connected manager service.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The entity query factory service.
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The render service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager service.
   */
  public function __construct(EntityManagerInterface $entity_manager, ConfigFactoryInterface $config_factory, QueryFactory $query_factory, ModuleHandler $module_handler, RendererInterface $renderer, TranslationInterface $string_translation) {
    $this->entityManager = $entity_manager;
    $this->configFactory = $config_factory;
    $this->entityQuery = $query_factory;
    $this->moduleHandler = $module_handler;
    $this->renderer = $renderer;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityRefrenceFields() {

    $fields = array();
    $ref_fields = $this->entityManager->getStorage('field_storage_config')->loadByProperties(
      array(
        'settings' => array(
          'target_type' => 'node',
        ),
        'entity_type' => 'node',
        'type' => 'entity_reference',
        'deleted' => FALSE,
        'status' => 1,
      )
    );
    foreach ($ref_fields as $field) {
      $fields[$field->getName()] = $field->getLabel();
    }
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getlinkFields() {
    $fields = array();
    $link_fields = $this->entityManager->getStorage('field_storage_config')->loadByProperties(
      array(
        'entity_type' => 'node',
        'type' => 'link',
        'deleted' => FALSE,
        'status' => 1,
      )
    );
    foreach ($link_fields as $field) {
      $fields[$field->getName()] = $field->getLabel();
    }
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getLongTextFields() {
    $fields = array();
    $text_with_summary = $this->entityManager->getStorage('field_storage_config')->loadByProperties(
      array(
        'entity_type' => 'node',
        'type' => 'text_with_summary',
        'deleted' => FALSE,
        'status' => 1,
      )
    );
    $text_long = $this->entityManager->getStorage('field_storage_config')->loadByProperties(
      array(
        'entity_type' => 'node',
        'type' => 'text_long',
        'deleted' => FALSE,
        'status' => 1,
      )
    );

    $long_fields = array_merge($text_with_summary, $text_long);
    foreach ($long_fields as $field) {
      $fields[$field->getName()] = $field->getLabel();
    }
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  protected function matchesEntityRefrenceField($nid) {

    $matches = array();
    $fields = $this->getEntityRefrenceFields();

    $config = $this->configFactory->get('content_connected.adminsettings');
    $exclude_fields = $config->get('content_connected_exclude_entityreffields');

    $fields = $this->excludeFields($fields, $exclude_fields);

    foreach ($fields as $fieldname => $field_value) {
      $matches[$fieldname] = $this->entityQuery->get('node')
          ->condition('status', 1)
          ->condition($fieldname, $nid)->execute();
    }

    return array('entity_reference' => $matches);
  }

  /**
   * {@inheritdoc}
   */
  protected function matchesLinkField($nid) {
    $matches = array();

    $fields = $this->getlinkFields();
    $node = $this->entityManager->getStorage('node')->load($nid);
    $config = $this->configFactory->get('content_connected.adminsettings');
    $exclude_fields = $config->get('content_connected_exclude_linkfields');

    $fields = $this->excludeFields($fields, $exclude_fields);

    foreach ($fields as $fieldname => $field_value) {

      $matches[$fieldname] = $this->entityQuery->get('node')
          ->condition('status', 1)
          ->condition($fieldname, 'node/' . $node->id(), 'CONTAINS')->execute();
    }
    return array('Link_field' => $matches);
  }

  /**
   * {@inheritdoc}
   */
  protected function matchesLongTextField($nid) {
    $matches = array();
    $fields = $this->getLongTextFields();

    $node = $this->entityManager->getStorage('node')->load($nid);
    $config = $this->configFactory->get('content_connected.adminsettings');
    $exclude_fields = $config->get('content_connected_exclude_longtextfields');

    $fields = $this->excludeFields($fields, $exclude_fields);

    foreach ($fields as $fieldname => $field_value) {
      $matches[$fieldname] = $this->entityQuery->get('node')
          ->condition('status', 1)
          ->condition($fieldname, 'node/' . $node->id(), 'CONTAINS')->execute();
    }
    return array('long_text' => $matches);
  }

  /**
   * Render function to generate content connected table.
   */
  public function renderMatches($nid) {

    $matches = array_merge($this->matchesEntityRefrenceField($nid), $this->matchesLinkField($nid), $this->matchesLongTextField($nid));

    // Allow other modules to alter the reference array.
    $this->moduleHandler->alter('content_connected', $nid, $matches);
    $headers = array(
      $this->t('Title'),
      $this->t('Content type'),
      $this->t('Status'),
      $this->t('Type of field'),
    );
    $rows = array();
    foreach ($matches as $match_type => $match) {
      foreach ($match as $field_name => $nids) {
        $nodes = $this->entityManager->getStorage('node')->loadMultiple($nids);

        foreach ($nodes as $node) {
          $row = array();
          $link = Link::fromTextAndUrl($node->label(), $node->toUrl());
          $row[] = $link;
          $row[] = node_get_type_label($node);
          $row[] = $node->isPublished() == NODE_PUBLISHED ? $this->t('Published') : $this->t('Not published');
          $row[] = $match_type . '(' . $field_name . ')';
          $rows[] = $row;
        }
      }
    }

    $build = array(
      '#theme' => 'table',
      '#caption' => $this->t('Content connected'),
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => $this->t('No content connected available.'),
    );

    return $this->renderer->render($build);
  }

  /**
   * Helper function to exclude fields.
   */
  protected function excludeFields($fields, $exclude_fields) {

    if (!empty($exclude_fields)) {
      $fields = array_diff_key($fields, array_filter($exclude_fields));
    }
    return $fields;
  }

}
