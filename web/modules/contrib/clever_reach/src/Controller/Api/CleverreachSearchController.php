<?php

namespace Drupal\clever_reach\Controller\Api;

use Drupal;
use Drupal\clever_reach\Component\Repository\ArticleRepository;
use Drupal\clever_reach\Exception\MethodNotAllowedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Article search endpoint.
 */
class CleverreachSearchController {
  /**
   * Article repository class.
   *
   * @var \Drupal\clever_reach\Component\Repository\ArticleRepository
   */
  private $articleRepository;

  /**
   * Allows search of articles in CleverReach system.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Json object.
   *
   * @throws MethodNotAllowedException
   */
  public function search(Request $request) {
    if (!$request->isMethod('POST')) {
      throw new MethodNotAllowedException("Method ({$request->getMethod()}) is not allowed.");
    }

    $result = [];
    switch ($request->get('get')) {
      case 'filter':
        $result = $this->getFilterSelect();
        break;

      case 'search':
        $result = $this->getSearchResult($request);
        break;
    }

    return new JsonResponse($result);
  }

  /**
   * Searches for articles with specific search term in title and/or id.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request search parameters, currently supported: id, title and language.
   *
   * @return array
   *   List of search results. Example:
   *   [
   *   'title' => "Some title",
   *   'description' => "Node 1",
   *   'image' => "http://www.example.com/node/image.jpg",
   *   'content' => <p>Node 1</p>,
   *   'url' => "http://www.example.com/node/1",
   *   ]
   */
  private function getSearchResult(Request $request) {
    $result = [
      'settings' => [
        'type' => 'content',
        'link_editable' => TRUE,
        'link_text_editable' => TRUE,
        'image_size_editable' => TRUE,
      ],
      'items' => [],
    ];

    $filter = [];
    if ($value = $request->get('nid')) {
      $filter[] = ['field' => 'nid', 'value' => $value];
    }

    if ($value = $request->get('title')) {
      $filter[] = [
        'field' => 'title',
        'value' => $value,
        'condition' => 'CONTAINS',
      ];
    }

    if (empty($filter)) {
      return $result;
    }

    $language = $request->get('lang');
    $articles = $this->getArticleRepository()->get($filter, $language);

    /** @var \Drupal\node\Entity\Node $article */
    foreach ($articles as $article) {
      $url = $this->getArticleRepository()->getUrlById($article, $language);
      $content = $this->getArticleRepository()->getContentByArticle($article, $language);

      $result['items'][] = [
        'title' => $article->getTitle(),
        'description' => strip_tags($content),
        'image' => '',
        'content' => $content,
        'url' => $url,
      ];
    }

    return $result;
  }

  /**
   * Create filters available. Supported filtering by nid, title and language.
   *
   * @return array
   *   Array of available filter in article search.
   */
  private function getFilterSelect() {
    $result = [
      'nid' => [
        'name' => t('Article ID'),
        'description' => '',
        'required' => FALSE,
        'query_key' => 'nid',
        'type' => 'input',
      ],
      'title' => [
        'name' => t('Article Title'),
        'description' => '',
        'required' => FALSE,
        'query_key' => 'title',
        'type' => 'input',
      ],
    ];

    // If site is multilingual, allow filtering by language.
    if (Drupal::languageManager()->isMultilingual()) {
      $result['lang'] = [
        'name' => t('Language'),
        'description' => t('Please select language.'),
        'required' => FALSE,
        'query_key' => 'lang',
        'type' => 'dropdown',
      ];

      foreach (Drupal::languageManager()->getLanguages() as $language) {
        $result['lang']['values'][] = [
          'text' => $language->getName(),
          'value' => $language->getId(),
        ];
      }
    }

    return array_values($result);
  }

  /**
   * Gets article repository.
   *
   * @return \Drupal\clever_reach\Component\Repository\ArticleRepository
   *   Article repository class.
   */
  private function getArticleRepository() {
    if (NULL === $this->articleRepository) {
      $this->articleRepository = new ArticleRepository();
    }
    return $this->articleRepository;
  }

}
