<?php

namespace Drupal\paragraphs_entity_embed\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\embed\DomHelperTrait;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\entity_embed\EntityEmbedBuilderInterface;
use Drupal\entity_embed\Exception\EntityNotFoundException;
use Drupal\entity_embed\Exception\RecursiveRenderingException;

/**
 * Provides a filter to display embedded URLs based on data attributes.
 *
 * @Filter(
 *   id = "paragraphs_entity_embed",
 *   title = @Translation("Display embedded paragraphs"),
 *   description = @Translation("Embeds paragraphs using data attribute: data-paragraph-type."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class ParagraphEmbedFilter extends FilterBase implements ContainerFactoryPluginInterface {
  use DomHelperTrait;

  /**
   * The renderer service.
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
   * The entity embed builder service.
   *
   * @var \Drupal\entity_embed\EntityEmbedBuilderInterface
   */
  protected $builder;

  /**
   * Constructs a UrlEmbedFilter object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Drupal renderer.
   * @param \Drupal\entity_embed\EntityEmbedBuilderInterface $builder
   *   Embed builder interface.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer, EntityEmbedBuilderInterface $builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
    $this->builder = $builder;
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
      $container->get('renderer'),
      $container->get('entity_embed.builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    if (strpos($text, 'data-paragraph-id') !== FALSE) {
      $dom = Html::load($text);
      $xpath = new \DOMXPath($dom);
      $entity_type = 'embedded_paragraphs';

      foreach ($xpath->query('//drupal-paragraph[@data-paragraph-id]') as $node) {
        /** @var \DOMElement $node */
        $entity = NULL;
        $entity_output = '';

        /** @var \DOMElement $node */
        try {
          // Load the entity either by UUID (preferred) or ID.
          $id = NULL;
          $entity = NULL;
          $id = $node->getAttribute('data-paragraph-id');
          $embed_entity = $this->entityTypeManager->getStorage($entity_type)
            ->loadByProperties(['uuid' => $id]);
          $entity = current($embed_entity);
          if ($entity) {
            // Protect ourselves from recursive rendering.
            static $depth = 0;
            $depth++;
            if ($depth > 20) {
              throw new RecursiveRenderingException(sprintf('Recursive rendering detected when rendering embedded %s entity %s.', $entity_type, $entity->id()));
            }
            $context = $this->getNodeAttributesAsArray($node);
            $context += ['data-langcode' => $langcode];
            $context['data-view-mode'] = 'embed';
            $build = $this->builder->buildEntityEmbed($entity, $context);

            // We need to render the embedded entity:
            // - without replacing placeholders, so that the placeholders are
            //   only replaced at the last possible moment. Hence we cannot use
            //   either renderPlain() or renderRoot(), so we must use render().
            // - without bubbling beyond this filter, because filters must
            //   ensure that the bubbleable metadata for the changes they make
            //   when filtering text makes it onto the FilterProcessResult
            //   object that they return ($result). To prevent that bubbling, we
            //   must wrap the call to render() in a render context.
            $entity_output = $this->renderer->executeInRenderContext(new RenderContext(), function () use (&$build) {
              return $this->renderer->render($build);
            });
            $result = $result->merge(BubbleableMetadata::createFromRenderArray($build));

            $depth--;
          }
          else {
            throw new EntityNotFoundException(sprintf('Unable to load embedded %s entity %s.', $entity_type, $id));
          }
        }
        catch (\Exception $e) {
          watchdog_exception('entity_embed', $e);
        }

        $this->replaceNodeContent($node, $entity_output);
      }

      $result->setProcessedText(Html::serialize($dom));
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    if ($long) {
      return $this->t('
        <p>You can embed Drupal paragraph entities. Examples:</p>
        <ul>
          <li><code><drupal-paragraph data-paragraph-id="423d2d23d23-432423-432"> </drupal-paragraph> </code></li>
        </ul>');
    }
    else {
      return $this->t('You can embed Drupal Paragraphs.');
    }
  }

}
