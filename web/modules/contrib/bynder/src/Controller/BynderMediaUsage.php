<?php

namespace Drupal\bynder\Controller;

use Drupal\bynder\BynderApiInterface;
use Drupal\bynder\Plugin\media\Source\Bynder;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller contains methods for displaying Bynder media usage info.
 */
class BynderMediaUsage extends ControllerBase {

  /**
   * The Bynder API service.
   *
   * @var \Drupal\bynder\BynderApiInterface
   */
  protected $bynder;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Renderer object.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a BynderMediaUsage class instance.
   *
   * @param \Drupal\bynder\BynderApiInterface $bynder
   *   The Bynder API service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Drupal\Core\Render\RendererInterface $renderer_object
   *   Renderer object.
   */
  public function __construct(BynderApiInterface $bynder, ConfigFactoryInterface $config_factory, RendererInterface $renderer_object) {
    $this->bynder = $bynder;
    $this->configFactory = $config_factory;
    $this->renderer = $renderer_object;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('bynder_api'),
      $container->get('config.factory'),
      $container->get('renderer')
    );
  }

  /**
   * The Bynder media usage info.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Represents an HTTP request.
   *
   * @return mixed
   *   Bynder media usage list.
   */
  public function bynderMediaInfo(Request $request) {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->entityTypeManager()->getStorage('node')->load($request->attributes->get('node'));
    $media_types = $this->entityTypeManager()->getStorage('media_type')->loadMultiple();
    $bynder_types = array_filter($media_types, function ($type) {
      /** @var \Drupal\media\MediaTypeInterface $type */
      return $type->getSource() instanceof Bynder;
    });
    $entity_reference_fields = array_filter($node->getFields(), function ($field) {
      return $field instanceof EntityReferenceFieldItemList;
    });
    $header = [
      ['data' => $this->t('Name')],
      ['data' => $this->t('Type')],
      ['data' => $this->t('Usage Restriction')],
      ['data' => $this->t('Action')],
    ];
    $rows = [];
    $entities = [];

    /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $field */
    foreach ($entity_reference_fields as $field) {
      /** @var \Drupal\media\Entity\Media $entity */
      foreach ($field->referencedEntities() as $entity) {
        if (in_array($entity->bundle(), array_keys($bynder_types))) {
          $entities[] = $entity;
          $account_domain = $this->configFactory->get('bynder.settings')->get('account_domain');
          $name = $entity->getSource()->getMetadata($entity, 'name');
          $type = $entity->getSource()->getMetadata($entity, 'type');
          $bynder_id = $entity->getSource()->getMetadata($entity, 'uuid');
          $row['name'] = $name ?: $bynder_id;
          $row['type'] = $type ?: $this->t('N/A');
          $row['restriction'] = get_media_restriction($entity->getSource()->getMetadata($entity, 'propertyOptions'));
          $links['edit'] = [
            'title' => $this->t('Edit'),
            'url' => $entity->toUrl('edit-form'),
          ];
          $links['edit_on_bynder'] = [
            'title' => $this->t('Edit on Bynder'),
            'url' => Url::fromUri($account_domain . '/media', ['query' => ['mediaId' => $bynder_id]]),
          ];
          $row['actions']['data'] = ['#type' => 'operations', '#links' => $links];
          $rows[] = ['data' => $row];
        }
      }
    }

    $table = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t("There are no Bynder media found on the page."),
    ];

    $this->renderer->addCacheableDependency($table, $node);
    $this->renderer->addCacheableDependency($table, $this->configFactory->get('bynder.settings'));
    foreach ($entities as $entity) {
      $this->renderer->addCacheableDependency($table, $entity);
    }

    return $table;
  }

}
