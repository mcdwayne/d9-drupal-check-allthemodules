<?php

namespace Drupal\google_scholar;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Adds Google Scholar meta tags to the page attachments based on node fields.
 */
class TagRenderer implements TagRendererInterface {

  /**
   * The Google Scholar meta tag names.
   */
  const METATAG_NAMES = [
    'citation_title',
    'citation_author',
    'citation_publication_date',
    'citation_journal_title',
    'citation_volume',
    'citation_issue',
    'citation_first_page',
    'citation_last_page',
    'citation_pdf_url',
  ];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new TagRenderer.
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
  public function buildTags(EntityInterface $entity) {
    $tags = [];

    $node_type = \Drupal\node\Entity\NodeType::load($entity->bundle());
    $view_builder = $this->entityTypeManager->getHandler('node', 'view_builder');

    foreach (self::METATAG_NAMES as $key) {
      $source_field = $node_type->getThirdPartySetting('google_scholar', $key);

      if (empty($source_field)) {
        // Skip if there's no setting for a particular meta tag.
        continue;
      }

      $field_item_list = $entity->get($source_field);
      $field_value = $field_item_list->getValue();

      // Skip an empty field.
      if (empty($field_value)) {
        continue;
      }

      $tag_content = [];

      // Special handling for certain tags.
      switch ($key) {
        case 'citation_publication_date':
          $tags += $this->buildFieldTagsDate($key, $field_item_list, $view_builder);
          break;
        case 'citation_author':
          $tags += $this->buildFieldTagsAuthor($key, $field_item_list, $view_builder);
          break;
        case 'citation_pdf_url':
          $tags += $this->buildFieldTagsURL($key, $field_item_list, $view_builder);
          break;
        default:
          $tags += $this->buildFieldTags($key, $field_item_list, $view_builder);
      }
    }

    return $tags;
  }

  /**
   * Build the tags for a single field.
   *
   * @param $key
   *  The tag name.
   * @param $field_item_list
   *  The field items.
   * @param $view_builder
   *  The entity's view builder handler.
   *
   * @return
   *  An array in the same format as the return from buildTags().
   */
  protected function buildFieldTags($key, $field_item_list, $view_builder) {
    $field_view = $view_builder->viewField($field_item_list, 'google_scholar');
    $field_render = render($field_view);

    return ['google_scholar_' . $key => ['name' => $key, 'content' => \Drupal\Component\Utility\Xss::filter($field_render, [])]];
  }

  /**
   * Build the tags for the citation date field.
   *
   * @param $key
   *  The tag name.
   * @param $field_item_list
   *  The field items.
   * @param $view_builder
   *  The entity's view builder handler.
   *
   * @return
   *  An array in the same format as the return from buildTags().
   */
  protected function buildFieldTagsDate($key, $field_item_list, $view_builder) {
    if (is_numeric($field_item_list->value)) {
      $tag_content = date("Y/m/d", $field_item_list->value);
    }
    else {
      $tag_content = $field_item_list->value;
    }

    return ['google_scholar_' . $key => ['name' => $key, 'content' => $tag_content]];
  }

  /**
   * Build the tags for the PDF URL field.
   *
   * @param $key
   *  The tag name.
   * @param $field_item_list
   *  The field items.
   * @param $view_builder
   *  The entity's view builder handler.
   *
   * @return
   *  An array in the same format as the return from buildTags().
   */
  protected function buildFieldTagsURL($key, $field_item_list, $view_builder) {
    // Need an absolute URL, so can't rely on the file field formatter as that
    // outputs relative.
    $file_entity = $field_item_list[0]->entity;
    $url = file_create_url($file_entity->getFileUri());
    return ['google_scholar_' . $key => ['name' => $key, 'content' => $url]];
  }

  /**
   * Build the tags for the author field.
   *
   * Handles multiple values, outputting a meta tag for each value.
   *
   * @param $key
   *  The tag name.
   * @param $field_item_list
   *  The field items.
   * @param $view_builder
   *  The entity's view builder handler.
   *
   * @return
   *  An array in the same format as the return from buildTags().
   */
  protected function buildFieldTagsAuthor($key, $field_item_list, $view_builder) {

    $tags = [];
    // Multiple authors result in multiple author meta tags.
    foreach ($field_item_list as $delta => $field_item) {
      $field_view_item = $view_builder->viewFieldItem($field_item, 'google_scholar');
      $field_render = render($field_view_item);
      $tags['google_scholar_' . $key . '_' . $delta] = [
        'name' => $key,
        'content' => \Drupal\Component\Utility\Xss::filter($field_render, []),
      ];
    }
    return $tags;
  }

}
