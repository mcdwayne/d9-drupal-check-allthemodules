<?php

namespace Drupal\markdown\Traits;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Trait MarkdownTrait.
 */
trait MarkdownTrait {

  /**
   * The Markdown service.
   *
   * @var \Drupal\markdown\Markdown
   */
  protected static $markdown;

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\markdown\MarkdownInterface::getParser()
   * @see \Drupal\markdown\MarkdownInterface::load()
   * @see \Drupal\markdown\Plugin\Markdown\MarkdownParserInterface::load()
   *
   * @return \Drupal\markdown\ParsedMarkdownInterface|null
   *   A ParsedMarkdown object or NULL if it doesn't exist and $markdown was
   *   not provided as a fallback.
   */
  public function loadMarkdown($id, $markdown = NULL, $parser = NULL, $filter = NULL, AccountInterface $account = NULL, LanguageInterface $language = NULL) {
    return $this->markdown()->getParser($parser, $filter, $account)->load($id, $markdown, $language);
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\markdown\MarkdownInterface::getParser()
   * @see \Drupal\markdown\MarkdownInterface::loadPath()
   * @see \Drupal\markdown\Plugin\Markdown\MarkdownParserInterface::loadPath()
   *
   * @return \Drupal\markdown\ParsedMarkdownInterface
   *   A ParsedMarkdown object.
   */
  public function loadMarkdownPath($id, $path, $parser = NULL, $filter = NULL, AccountInterface $account = NULL, LanguageInterface $language = NULL) {
    return $this->markdown()->getParser($parser, $filter, $account)->loadPath($id, $path, $language);
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\markdown\MarkdownInterface::getParser()
   * @see \Drupal\markdown\MarkdownInterface::loadUrl()
   * @see \Drupal\markdown\Plugin\Markdown\MarkdownParserInterface::loadUrl()
   *
   * @return \Drupal\markdown\ParsedMarkdownInterface
   *   A ParsedMarkdown object.
   */
  public function loadMarkdownUrl($id, $url, $parser = NULL, $filter = NULL, AccountInterface $account = NULL, LanguageInterface $language = NULL) {
    return $this->markdown()->getParser($parser, $filter, $account)->loadUrl($id, $url, $language);
  }

  /**
   * Retrieves the Markdown service.
   *
   * @return \Drupal\markdown\Markdown
   *   The Markdown service.
   */
  public function markdown() {
    if (!isset(static::$markdown)) {
      static::$markdown = \Drupal::service('markdown');
    }
    return static::$markdown;
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\markdown\MarkdownInterface::getParser()
   * @see \Drupal\markdown\MarkdownInterface::parse()
   * @see \Drupal\markdown\Plugin\Markdown\MarkdownParserInterface::parse()
   *
   * @return \Drupal\markdown\ParsedMarkdownInterface
   *   A ParsedMarkdown object.
   */
  public function parseMarkdown($markdown, $parser = NULL, $filter = NULL, AccountInterface $account = NULL, LanguageInterface $language = NULL) {
    return $this->markdown()->getParser($parser, $filter, $account)->parse($markdown, $language);
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\markdown\MarkdownInterface::getParser()
   * @see \Drupal\markdown\MarkdownInterface::parsePath()
   * @see \Drupal\markdown\Plugin\Markdown\MarkdownParserInterface::parsePath()
   *
   * @return \Drupal\markdown\ParsedMarkdownInterface
   *   A ParsedMarkdown object.
   */
  public function parseMarkdownPath($path, $parser = NULL, $filter = NULL, AccountInterface $account = NULL, LanguageInterface $language = NULL) {
    return $this->markdown()->getParser($parser, $filter, $account)->parsePath($path, $language);
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\markdown\MarkdownInterface::getParser()
   * @see \Drupal\markdown\MarkdownInterface::parseUrl()
   * @see \Drupal\markdown\Plugin\Markdown\MarkdownParserInterface::parseUrl()
   *
   * @return \Drupal\markdown\ParsedMarkdownInterface
   *   A ParsedMarkdown object.
   */
  public function parseMarkdownUrl($url, $parser = NULL, $filter = NULL, AccountInterface $account = NULL, LanguageInterface $language = NULL) {
    return $this->markdown()->getParser($parser, $filter, $account)->parseUrl($url, $language);
  }

}
