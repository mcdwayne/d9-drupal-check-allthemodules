<?php

namespace Drupal\ckeditor5_sections\Plugin\Filter;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\FilterProcessResult;
use Drupal\Component\Utility\Html;
use Drupal\Core\Render\RendererInterface;

/**
 * Provides a filter to render media elements.
 *
 * @Filter(
 *   id = "sections_media_caption",
 *   title = @Translation("Sections media"),
 *   description = @Translation("Uses a media tags to render media entities."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class CKEditor5SectionsMediaFilter extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * Renderer service object.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository, RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity.repository'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    if (strpos($text, 'data-media-uuid=') !== FALSE) {
      $document = Html::load($text);
      $xpath = new \DOMXPath($document);

      foreach ($xpath->query('//div[@data-media-uuid]') as $node) {
        $media_uuid = $node->getAttribute('data-media-uuid');
        $display = $node->getAttribute('data-media-display');
        // Clear attributes and normalize.
        $node->removeAttribute('data-media-uuid');
        $node->removeAttribute('data-media-display');

        if (empty($display)) {
          $display = 'default';
        }

        $media = $this->entityRepository->loadEntityByUuid('media', $media_uuid);
        if (!$media) {
          continue;
        }

        $build = $this->entityTypeManager->getViewBuilder('media')->view($media, $display);
        $rendered = $this->renderer->render($build);
        $updated_nodes = Html::load($rendered)->getElementsByTagName('body')
          ->item(0)
          ->childNodes;

        // Insert rendered media into the element.
        foreach ($updated_nodes as $updated_node) {
          $updated_node = $document->importNode($updated_node, TRUE);
          $node->appendChild($updated_node);
        }
      }
      $result->setProcessedText(Html::serialize($document));
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return $this->t('You can render media entities created by CKEditor5 module.');
  }

}
