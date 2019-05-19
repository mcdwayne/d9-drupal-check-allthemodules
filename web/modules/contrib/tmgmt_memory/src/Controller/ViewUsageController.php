<?php

namespace Drupal\tmgmt_memory\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\Markup;
use Drupal\tmgmt_memory\UsageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller to view a Segment Usage.
 */
class ViewUsageController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates an ContentTranslationPreviewController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager */
    $entity_manager = $container->get('entity_type.manager');
    return new static($entity_manager);
  }

  /**
   * Preview job item entity data.
   *
   * @param \Drupal\tmgmt_memory\UsageInterface $tmgmt_memory_usage
   *   The usage.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function view(UsageInterface $tmgmt_memory_usage) {
    $langcode = $tmgmt_memory_usage->getLangcode();
    $job_item = $tmgmt_memory_usage->getJobItem();
    // Load entity.
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->entityTypeManager
      ->getStorage($job_item->getItemType())
      ->load($job_item->getItemId());

    // We cannot show the preview for non-existing entities.
    if (!$entity) {
      throw new NotFoundHttpException();
    }

    $data = $job_item->getData();
    $target_langcode = $job_item->getJob()->getTargetLangcode();
    // Populate preview with target translation data.
    $preview = $entity->getTranslation($langcode);
    // Build view for entity.
    $page = $this->entityTypeManager
      ->getViewBuilder($entity->getEntityTypeId())
      ->view($preview, 'full', $preview->language()->getId());

    /** @var \Drupal\tmgmt\Data $data_service */
    $data_service = \Drupal::service('tmgmt.data');
    /** @var \Drupal\tmgmt\SegmenterInterface $segmenter */
    $segmenter = \Drupal::service('tmgmt.segmenter');

    $flat_data = $data_service->flatten($data);
    $data_item_key = str_replace('|', '][', $tmgmt_memory_usage->getDataItemKey());
    if ($langcode == $target_langcode) {
      $unflattened_field = $data_service->unflatten([$data_item_key => $flat_data[$data_item_key]]);
      $segmented_data = $data_service->flatten($segmenter->getSegmentedData($unflattened_field));
      $segmented_text = $segmented_data[$data_item_key]['#translation']['#segmented_text'];
    }
    else {
      $segmented_text = $flat_data[$data_item_key]['#segmented_text'];
    }
    $segments = $segmenter->getSegmentsOfData($segmented_text);

    $page['#segment'] = $segments[$tmgmt_memory_usage->getSegmentDelta()]['data'];
    $page['#attached']['library'][] = 'tmgmt_memory/admin';
    $page['#post_render'][] = [$this, 'postRender'];

    // The preview is not cacheable.
    $page['#cache']['max-age'] = 0;
    \Drupal::service('page_cache_kill_switch')->trigger();

    return $page;
  }

  public static function postRender($html, array $elements) {
    $segment = $elements['#segment'];
    unset($elements['#segment']);
    $pos = strpos($html, $segment);
    $html = substr_replace($html, '</span>', $pos + strlen($segment), 0);
    $html = substr_replace($html, '<span class="tmgmt_memory_highlight">', $pos, 0);
    return Markup::create($html);
  }

}
