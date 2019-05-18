<?php

namespace Drupal\applenews;

/**
 * Applenews publisher manager.
 *
 * @package Drupal\applenews
 */
interface PublisherInterface {

  /**
   * Retrieves channel details.
   *
   * @param string $channel_id
   *   Channel ID.
   *
   * @return mixed
   *   Channel details.
   *
   * @see vendor/chapter-three/apple-news-api/examples/PublisherAPI/GetChannel.php
   */
  public function getChannel($channel_id);

  /**
   * Retrieve article.
   *
   * @param string $article_id
   *   Unique article UUID.
   *
   * @return mixed
   *   An article object.
   *
   * @see vendor/chapter-three/apple-news-api/examples/PublisherAPI/GetArticle.php
   */
  public function getArticle($article_id);

  /**
   * Retrieves section details.
   *
   * @param string $section_id
   *   Unique section UUID.
   *
   * @return mixed
   *   Section details, if avaiable. NULL otherwise.
   *
   * @see vendor/chapter-three/apple-news-api/examples/PublisherAPI/GetSection.php
   */
  public function getSection($section_id);

  /**
   * Retrieves available sections.
   *
   * @param string $channel_id
   *   Unique channel UUID.
   *
   * @return mixed
   *   Sections.
   *
   * @see vendor/chapter-three/apple-news-api/examples/PublisherAPI/GetSections.php
   */
  public function getSections($channel_id);

  /**
   * Creates new article.
   *
   * @param string $channel_id
   *   Unique channel UUID.
   * @param array $data
   *   An array of data to post.
   *
   * @return object
   *   Response object if successful. NULL otherwise.
   *
   * @see vendor/chapter-three/apple-news-api/examples/PublisherAPI/PostArticle.php
   */
  public function postArticle($channel_id, array $data);

  /**
   * Update an existing article.
   *
   * @param string $article_id
   *   Unique article UUID.
   * @param array $data
   *   An array of article data.
   *
   * @return mixed
   *   Response object if update succefull. NULL otherwise.
   *
   * @see vendor/chapter-three/apple-news-api/examples/PublisherAPI/UpdateArticle.php
   */
  public function updateArticle($article_id, array $data);

  /**
   * Delete an article.
   *
   * @param string $article_id
   *   Unique article UUID.
   *
   * @return mixed
   *   Mixed deleted status.
   *
   * @see vendor/chapter-three/apple-news-api/examples/PublisherAPI/UeleteArticle.php
   */
  public function deleteArticle($article_id);

}
