<?php

namespace Drupal\markdown;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Markdown.
 */
class Markdown implements MarkdownInterface {

  use ContainerAwareTrait;
  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * The MarkdownParser Plugin Manager.
   *
   * @var \Drupal\markdown\MarkdownParsersInterface
   */
  protected $parsers;

  /**
   * Markdown constructor.
   *
   * @param \Drupal\markdown\MarkdownParsersInterface $markdown_parsers
   *   The MarkdownParser Plugin Manager service.
   */
  public function __construct(MarkdownParsersInterface $markdown_parsers) {
    $this->parsers = $markdown_parsers;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container = NULL) {
    if (!isset($container)) {
      $container = \Drupal::getContainer();
    }
    return new static(
      $container->get('plugin.manager.markdown.parser')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function load($id, $markdown = NULL, $parser = NULL, $filter = NULL, AccountInterface $account = NULL, LanguageInterface $language = NULL) {
    return static::create()->getParser($parser, $filter, $account)->load($id, $markdown, $language);
  }

  /**
   * {@inheritdoc}
   */
  public static function loadPath($id, $path, $parser = NULL, $filter = NULL, AccountInterface $account = NULL, LanguageInterface $language = NULL) {
    return static::create()->getParser($parser, $filter, $account)->loadPath($id, $path, $language);
  }

  /**
   * {@inheritdoc}
   */
  public static function loadUrl($id, $url, $parser = NULL, $filter = NULL, AccountInterface $account = NULL, LanguageInterface $language = NULL) {
    return static::create()->getParser($parser, $filter, $account)->loadUrl($id, $url, $language);
  }

  /**
   * {@inheritdoc}
   */
  public static function parse($markdown, $parser = NULL, $filter = NULL, AccountInterface $account = NULL, LanguageInterface $language = NULL) {
    return static::create()->getParser($parser, $filter, $account)->parse($markdown, $language);
  }

  /**
   * {@inheritdoc}
   */
  public static function parsePath($path, $parser = NULL, $filter = NULL, AccountInterface $account = NULL, LanguageInterface $language = NULL) {
    return static::create()->getParser($parser, $filter, $account)->parsePath($path, $language);
  }

  /**
   * {@inheritdoc}
   */
  public static function parseUrl($url, $parser = NULL, $filter = NULL, AccountInterface $account = NULL, LanguageInterface $language = NULL) {
    return static::create()->getParser($parser, $filter, $account)->parseUrl($url, $language);
  }

  /**
   * {@inheritdoc}
   */
  public function getParser($parser = NULL, $filter = NULL, AccountInterface $account = NULL) {
    return $this->parsers->createInstance($parser, [
      'filter' => $filter,
      'account' => $account,
    ]);
  }
}
