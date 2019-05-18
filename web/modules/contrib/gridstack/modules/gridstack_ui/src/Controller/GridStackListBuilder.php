<?php

namespace Drupal\gridstack_ui\Controller;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Messenger\Messenger;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\gridstack\GridStackManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of GridStack optionsets.
 */
class GridStackListBuilder extends DraggableListBuilder {

  /**
   * The gridstack manager.
   *
   * @var \Drupal\gridstack\GridStackManagerInterface
   */
  protected $manager;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Constructs a new GridStackListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param Drupal\Core\Messenger\Messenger $messenger
   *   The messenger class.
   * @param \Drupal\gridstack\GridStackManagerInterface $manager
   *   The gridstack manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, Messenger $messenger, GridStackManagerInterface $manager) {
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
      $container->get('gridstack.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gridstack_list_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'label'     => $this->t('Optionset'),
      'icon'      => $this->t('Icon'),
      'framework' => $this->t('Grid framework'),
      'grids'     => $this->t('Grids : Nested'),
      'provider'  => $this->t('Provider'),
    ];

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $icon_uri         = $entity->getIconUri();
    $manager          = $this->manager;
    $framework        = $manager->configLoad('framework', 'gridstack.settings');
    $use_framework    = $framework && $entity->getOption('use_framework');
    $row['label']     = Html::escape($entity->label());
    $row['icon']      = [];
    $row['framework'] = [];
    $token_query      = [IMAGE_DERIVATIVE_TOKEN => time()];
    $image_url        = file_url_transform_relative(file_create_url($icon_uri));
    $image_url       .= (strpos($image_url, '?') !== FALSE ? '&' : '?') . UrlHelper::buildQuery($token_query);

    if (!empty($icon_uri)) {
      $row['icon'] = [
        '#theme' => 'blazy',
        '#settings' => [
          'uri' => $icon_uri,
          'lazy' => 'blazy',
          'image_url' => $image_url,
        ],
        '#item_attributes' => ['width' => 140],
      ];
    }

    $row['framework']['#markup'] = $use_framework ? ucwords($framework) : 'GridStack JS';

    $grids = $entity->getEndBreakpointGrids();
    $nested = $entity->getEndBreakpointGrids('nested');
    $nested = array_filter($nested);

    $counts = [];
    if (!empty($nested)) {
      foreach ($nested as $grid) {
        if (empty($grid)) {
          continue;
        }

        if (is_string($grid)) {
          $grid = Json::decode($grid);
        }

        $boxes = [];
        foreach ($grid as $item) {
          if (!isset($item['width'])) {
            continue;
          }

          $boxes[] = $item['width'];
        }

        $counts = NestedArray::mergeDeep($counts, $boxes);
      }
    }

    $nested_grids = empty($nested) ? '0' : count($counts);
    $row['grids']['#markup'] = $this->t('@grids : @nested', ['@grids' => count($grids), '@nested' => $nested_grids]);

    $dependencies = $entity->getDependencies();
    $row['provider']['#markup'] = isset($dependencies['module'][0]) ? $dependencies['module'][0] : 'gridstack';

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
    if ($entity->id() == 'frontend') {
      unset($operations['delete']);
    }

    return $operations;
  }

  /**
   * Adds some descriptive text to the gridstack optionsets list.
   *
   * @return array
   *   Renderable array.
   *
   * @see admin/config/development/configuration/single/export
   */
  public function render() {
    $build['description'] = [
      '#markup' => $this->t("<p>Manage the GridStack optionsets. Optionsets are Config Entities.</p><p>By default, when this module is enabled, four optionsets are created from configuration: Default Admin, Default Frontend, Default Bootstrap/ Foundation. Install GridStack example module to speed up by cloning them. Use the Operations column to edit, clone and delete optionsets. GridStack supports both magazine layout (GridStack JS), and static float layout (Bootstrap/ Foundation). The main difference is no fixed height, no JS, just CSS, for static float layout. Visit <a href=':ui'>GridStack UI</a> page to enable its support. To generate icons, edit and save optionsets.<br><strong>Important!</strong><br>Avoid overriding Default Admin (hidden) optionset as it is meant for Default -- checking and cleaning the frontend. Use Duplicate Default Frontend (GridStack JS), or Default Bootstrap/ Foundation, accordingly instead. Otherwise possible messes.<br>Use <a href=':url'>config_update</a> module to revert to stored optionsets at <em>/admin/config/development/configuration/report/module/gridstack</em>, if needed.</p>", [':ui' => Url::fromRoute('gridstack.settings')->toString(), ':url' => '//drupal.org/project/config_update']),
    ];

    $build[] = parent::render();

    $attachments = $this->manager->attach(['blazy' => TRUE]);
    $build['#attached'] = isset($build['#attached']) ? NestedArray::mergeDeep($build['#attached'], $attachments) : $attachments;
    $build['#attached']['library'][] = 'gridstack/admin';

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attributes']['class'][] = 'blazy';
    $form['#attributes']['data-blazy'] = '';
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->messenger->addMessage($this->t('The optionsets order has been updated.'));
  }

}
