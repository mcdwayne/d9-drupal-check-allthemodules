<?php

namespace Drupal\clever_reach\Component\Repository;

use DOMDocument;
use Drupal;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

/**
 * Article (Node) Repository.
 */
class ArticleRepository {

  /**
   * Gets articles by provided filters.
   *
   * If filters are not set, all nodes (articles) will be returned.
   *
   * @param array|null $filterBy
   *   Example: ['field' => 'test', 'condition' => '=', 'value' => 'something'].
   * @param string|null $language
   *   Language filter.
   *
   * @return \Drupal\node\Entity\Node[]
   *   List of matching nodes.
   */
  public function get($filterBy = NULL, $language = NULL) {
    if (version_compare(Drupal::VERSION, '8.4.0') >= 0) {
      $query = Drupal::entityQuery('node')->latestRevision();
    }
    else {
      $query = Drupal::entityQuery('node');
    }

    if (is_array($filterBy) && !empty($filterBy)) {
      foreach ($filterBy as $filter) {
        $field = $filter['field'];
        $value = $filter['value'];

        if ($field === 'uid') {
          /** @var \Drupal\user\Entity\User $user */
          $user = user_load_by_name($filter['value']);
          $value = $user ? $user->id() : $value;
        }

        $condition = isset($filter['condition']) ? $filter['condition'] : '=';
        $query->condition($field, $value, $condition, $language);
      }
    }

    if (!$ids = $query->execute()) {
      return [];
    }

    return Node::loadMultiple($ids);
  }

  /**
   * Gets all fields of provided content type code.
   *
   * @param string $typeId
   *   Unique type identifier.
   * @param string $contentType
   *   Drupal content type.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition[]
   *   Array of field definitions.
   */
  public function getFieldsByContentType($typeId, $contentType) {
    return Drupal::service('entity_field.manager')
      ->getFieldDefinitions($typeId, $contentType);
  }

  /**
   * Gets all content types available in system.
   *
   * @return array
   *   Hash-map of content type ID as key and content label as value.
   */
  public function getContentTypes() {
    $result = [];
    $contentTypes = \Drupal::service('entity.manager')
      ->getStorage('node_type')->loadMultiple();

    /** @var \Drupal\Core\Entity\ContentEntityBase $contentType */
    foreach ($contentTypes as $contentType) {
      $result[$contentType->id()] = $contentType->label();
    }

    return $result;
  }

  /**
   * Gets URL by node.
   *
   * @param \Drupal\node\Entity\Node $node
   *   Node object.
   * @param string|null $language
   *   (optional) Language code. If null default language is used.
   *
   * @return \Drupal\Core\GeneratedUrl|string
   *   Article URL.
   *
   * @throws Drupal\Core\Entity\EntityMalformedException
   */
  public function getUrlById(Node $node, $language = NULL) {
    $lang = NULL;

    if ($language) {
      $lang = Drupal::languageManager()->getLanguage($language);
    }

    return $node->toUrl('canonical', [
      'absolute' => TRUE,
      'language' => $lang,
    ])->toString();
  }

  /**
   * Gets html content of element.
   *
   * @param \Drupal\node\Entity\Node $node
   *   Node object.
   * @param string|null $language
   *   (optional) Language code. If null default language is used.
   *
   * @return string
   *   HTML of article.
   */
  public function getContentByArticle(Node $node, $language = NULL) {
    $node->in_preview = TRUE;
    $view = node_view($node, 'full', $language);

    if (!$html = Drupal::service('renderer')->render($view, FALSE)) {
      return '';
    }

    $doc = new DOMDocument();
    libxml_use_internal_errors(TRUE);
    $doc->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    $this->convertToAbsoluteUrls($doc, 'img', 'src');
    $this->convertToAbsoluteUrls($doc, 'a', 'href');

    return $doc->saveHTML($doc);
  }

  /**
   * Converts relative path to absolute path in DomDocument.
   *
   * @param \DOMDocument $doc
   *   HTML Document.
   * @param string $tag
   *   Tag name.
   * @param string $attribute
   *   Attribute name.
   */
  private function convertToAbsoluteUrls(DOMDocument $doc, $tag, $attribute) {
    $images = $doc->getElementsByTagName($tag);
    /** @var \DOMElement $image */
    foreach ($images as $image) {
      // Convert relative link/image paths to absolute.
      if (strpos($image->getAttribute($attribute), '/') === 0) {
        $url = Url::fromUserInput($image->getAttribute($attribute))
          ->setOption('absolute', TRUE)->toString();
        $image->setAttribute($attribute, $url);
      }
    }
  }

}
