<?php

namespace Drupal\mrr_embed_filter\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\RenderContext;
use Drupal\filter\FilterProcessResult;
use Drupal\entity_embed\Exception\EntityNotFoundException;
use Drupal\entity_embed\Exception\RecursiveRenderingException;
use Drupal\entity_embed\Plugin\Filter\EntityEmbedFilter;
use Drupal\media_reference_revisions\Entity\MediaReferenceRevision;
  
/**
 * Provides a filter to display embedded entities based on data attributes.
 *
 * @Filter(
 *   id = "entity_embed_revision",
 *   title = @Translation("Display embedded entities (revision-locking)"),
 *   description = @Translation("Embeds entities using data attributes: data-entity-type, data-entity-uuid, and data-view-mode. This version keeps track of the revision of referenced content and only loads that revision. Must not be used at the same time as the regular ""Display embedded entities"" filter."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class EntityEmbedRevisionFilter extends EntityEmbedFilter {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    if (strpos($text, 'data-entity-type') !== FALSE && (strpos($text, 'data-entity-embed-display') !== FALSE || strpos($text, 'data-view-mode') !== FALSE)) {
      // Work out what entity this page is on.
      $parent = $this->getParentEntity();

      // No parent entity could be worked out, skip all this custom logic and
      // just use the normal entity embed filter.
      if (empty($parent)) {
        return parent::process($text, $langcode);
      }

      // Turn the text into an DOMXPath object.
      $dom = Html::load($text);
      $xpath = new \DOMXPath($dom);

      foreach ($xpath->query('//drupal-entity[@data-entity-type and (@data-entity-uuid or @data-entity-id) and (@data-entity-embed-display or @data-view-mode)]') as $node) {
        /** @var \DOMElement $node */
        $entity_type = $node->getAttribute('data-entity-type');
        // Only work with Media objects.
        if ($entity_type != 'media') {
          continue;
        }

        $entity_output = '';

        // data-entity-embed-settings is deprecated, make sure we convert it to
        // data-entity-embed-display-settings.
        if (($settings = $node->getAttribute('data-entity-embed-settings')) && !$node->hasAttribute('data-entity-embed-display-settings')) {
          $node->setAttribute('data-entity-embed-display-settings', $settings);
          $node->removeAttribute('data-entity-embed-settings');
        }

        try {
          // Load the entity either by UUID (preferred) or ID.
          $id = NULL;
          $entity = NULL;
          if ($id = $node->getAttribute('data-entity-uuid')) {
            $entity = $this->entityTypeManager->getStorage($entity_type)
              ->loadByProperties(['uuid' => $id]);
            $entity = current($entity);
          }
          else {
            $id = $node->getAttribute('data-entity-id');
            $entity = $this->entityTypeManager->getStorage($entity_type)->load($id);
          }

          // If the entity wasn't loaded, skip to the next one.
          if (empty($entity)) {
            \Drupal::logger('mrr_embed_filter')
              ->error($this->t('Unable to load embedded %type entity %id.', ['%type' => $entity_type, '%id' => $id]));
            continue;
          }

          // Get the appropriate revision.
          $replacement = MediaReferenceRevision::loadMediaEntity($parent, $entity->id());
          if (!empty($replacement)) {
            $entity = $replacement;
          }

          // If the entity wasn't found, leave it to use the original. This
          // matches the logic used in media_reference_revisions_entity_view()
          // where the original is used if a replacement can't be found.
          else {
            \Drupal::logger('mrr_embed_filter')
              ->error($this->t('Unable to load embedded %type entity %id, failing over to use the original entity instead.', ['%type' => $entity_type, '%id' => $id]));
          }

          // Presuming there's something still to process, render the entity.
          if (!empty($entity)) {
            // Protect ourselves from recursive rendering.
            static $depth = 0;
            $depth++;
            if ($depth > 20) {
              throw new RecursiveRenderingException(sprintf('Recursive rendering detected when rendering embedded %s entity %s.', $entity_type, $entity->id()));
            }

            // If a UUID was not used, but is available, add it to the HTML.
            if (!$node->getAttribute('data-entity-uuid') && $uuid = $entity->uuid()) {
              $node->setAttribute('data-entity-uuid', $uuid);
            }
  
            $context = $this->getNodeAttributesAsArray($node);
            $context += array('data-langcode' => $langcode);
            $build = $this->builder->buildEntityEmbed($entity, $context);

            // Additional customizations for MRR.
            // Replace the rendered title with that of the other revision.
            if (isset($build['entity']['#title'])) {
              $build['entity']['#title'] = $entity->label();
            }
            // This handles entities which are displayed as an entity view.
            if (isset($build['entity']['#media'])) {
              $build['entity']['#media'] = $entity;
            }
            // Add the revision ID to the end of the cache keys, to make this
            // instance get reloaded when the ID is different.
            $build['entity']['#cache']['keys'][] = $entity->getRevisionId();

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
            \Drupal::logger('mrr_embed_filter')
              ->error($this->t('Unable to load embedded %type entity %id.', ['%type' => $entity_type, '%id' => $id]));
          }
        }
        catch (\Exception $e) {
          watchdog_exception('mrr_embed_filter', $e);
        }

        $this->replaceNodeContent($node, $entity_output);
      }

      $result->setProcessedText(Html::serialize($dom));
    }

    return $result;
  }

  /**
   * Get the entity for the current node.
   */
  protected function getParentEntity() {
    $route_match = \Drupal::routeMatch();
    $route_name = $route_match->getRouteName();
    $matches = [];
    preg_match('/entity\.(.*)\.(latest[_-]version|canonical)/', $route_name, $matches);
    if (!empty($matches[1])) {
      $entity_type = $matches[1];
      return $route_match->getParameter($entity_type);
    }
  }

}
