<?php

namespace Drupal\ultimenu;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\InfoParserInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Transliteration\PhpTransliteration;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Path\AliasStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides Ultimenu utility methods.
 */
class UltimenuTool implements UltimenuToolInterface {

  use UltimenuTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The path matcher service.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * The alias storage service.
   *
   * @var \Drupal\Core\Path\AliasStorageInterface
   */
  protected $aliasStorage;

  /**
   * The info parser service.
   *
   * @var \Drupal\Core\Extension\InfoParserInterface
   */
  protected $infoParser;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The transliteration service.
   *
   * @var \Drupal\Core\Transliteration\PhpTransliteration
   */
  protected $transliteration;

  /**
   * Static cache for the theme regions.
   *
   * @var array
   */
  protected $themeRegions;

  /**
   * Constructs a Ultimenu object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AccountInterface $current_user, PathMatcherInterface $path_matcher, AliasStorageInterface $alias_storage, InfoParserInterface $info_parser, LanguageManagerInterface $language_manager, PhpTransliteration $transliteration) {
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
    $this->pathMatcher = $path_matcher;
    $this->aliasStorage = $alias_storage;
    $this->infoParser = $info_parser;
    $this->languageManager = $language_manager;
    $this->transliteration = $transliteration;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('path.matcher'),
      $container->get('path.alias_storage'),
      $container->get('info_parser'),
      $container->get('language_manager'),
      $container->get('transliteration')
    );
  }

  /**
   * Returns path matcher service.
   */
  public function getPathMatcher() {
    return $this->pathMatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function getShortenedHash($key) {
    return substr(sha1($key), 0, 8);
  }

  /**
   * {@inheritdoc}
   */
  public function getShortenedUuid($key) {
    list(, $uuid) = array_pad(array_map('trim', explode(":", $key, 2)), 2, NULL);
    $uuid = str_replace('.', '__', $uuid ?: $key);
    list($shortened_uuid,) = array_pad(array_map('trim', explode("-", $uuid, 2)), 2, NULL);
    return $shortened_uuid;
  }

  /**
   * {@inheritdoc}
   */
  public function truncateRegionKey($string, $max_length = self::MAX_LENGTH) {
    // Transliterate the string.
    $langcode = $this->languageManager->getCurrentLanguage()->getId();
    $transformed = $this->transliteration->transliterate($string, $langcode);

    // Decode it.
    $transformed = Html::decodeEntities($transformed);
    $transformed = mb_strtolower(str_replace(['menu-', '-menu'], '', $transformed));
    $transformed = preg_replace('/[\W\s]+/', '_', $transformed);

    // Trim trailing underscores.
    $transformed = trim($transformed, '_');
    $transformed = Unicode::truncate($transformed, $max_length, TRUE, FALSE);
    return $transformed;
  }

  /**
   * {@inheritdoc}
   */
  public function getRegionKey($link, $max_length = self::MAX_LENGTH) {
    $menu_name = $link->getMenuName();
    $key = $link->getPluginId();
    $title = $this->getTitle($link);
    $goodies = $this->getSetting('goodies');
    $is_mlid = isset($goodies['ultimenu-mlid']) && $goodies['ultimenu-mlid'];
    $is_hash = isset($goodies['ultimenu-mlid-hash']) && $goodies['ultimenu-mlid-hash'];
    $menu_name = $this->truncateRegionKey($menu_name, $max_length);

    if ($is_hash) {
      $menu_item = $this->getShortenedHash($key);
    }
    elseif ($is_mlid) {
      $menu_item = $this->getShortenedUuid($key);
    }
    else {
      $menu_item = $this->truncateRegionKey(trim($title), $max_length);
    }

    return 'ultimenu_' . $menu_name . '_' . $menu_item;
  }

  /**
   * Returns title.
   */
  public function getTitle($link) {
    return $this->extractTitle($link)['title'];
  }

  /**
   * Returns title with an icon class if available, e.g.: fa-mail|Contact us.
   */
  public function extractTitle($link) {
    $title = strip_tags($link->getTitle());
    $is_icon = substr($title, 0, 5) === 'icon-';
    $is_fontawesome = substr($title, 0, 3) === 'fa-';

    if (strpos($title, '|') !== FALSE && ($is_icon || $is_fontawesome)) {
      list($icon_class, $title) = array_pad(array_map('trim', explode("|", $title, 2)), 2, NULL);
      return ['icon' => $icon_class, 'title' => $title, 'fa' => $is_fontawesome];
    }

    // Ever had a client which adds an empty space to a menu title? I did.
    return ['title' => trim($title)];
  }

  /**
   * Returns titles as both HTML and plain text titles.
   */
  public function extractTitleHtml($link) {
    $icon = '';
    $goodies = $this->getSetting('goodies');
    $titles = $this->extractTitle($link);
    $title_html = $title = $titles['title'];

    if (!empty($titles['icon'])) {
      $icon_class = $titles['fa'] ? 'fa ' . $titles['icon'] : 'icon ' . $titles['icon'];
      $icon = '<span class="ultimenu__icon ' . $icon_class . '" aria-hidden="true"></span>';
    }

    if (!empty($goodies['menu-desc']) && $description = $link->getDescription()) {
      // Render description, if so configured.
      // If you override this, be sure to have proper sanitization.
      $description = '<small>' . strip_tags($description, '<em><strong><i><b>') . '</small>';
      $title_html = !empty($goodies['desc-top']) ? $description . $title : $title . $description;
    }

    // Holds the title in a separate SPAN for easy positioning if it has icon.
    if ($icon) {
      $title_html = $icon . '<span class="ultimenu__title">' . $title_html . '</span>';
    }

    return ['title' => $title, 'title_html' => $title_html];
  }

  /**
   * {@inheritdoc}
   */
  public function parseThemeInfo(array $ultimenu_regions = []) {
    if (!isset($this->themeRegions)) {
      $theme = $this->getThemeDefault();
      $file = drupal_get_path('theme', $theme) . '/' . $theme . '.info.yml';

      // Parse theme .info.yml file.
      $info = $this->infoParser->parse($file);

      $regions = [];
      foreach ($info['regions'] as $key => $region) {
        if (array_key_exists($key, $ultimenu_regions)) {
          $regions[$key] = $region;
        }
      }

      $this->themeRegions = $regions;
    }
    return $this->themeRegions;
  }

  /**
   * Checks if user has access to view a block, including its path visibility.
   */
  public function isAllowedBlock(EntityInterface $block, array $config) {
    $access = $block->access('view', $this->currentUser, TRUE);
    $allowed = $access->isAllowed();

    // If not allowed, checks block visibility by paths and roles.
    // Ensures we are on the same page before checking visibility by roles.
    if (!$allowed && $match = $this->isPageMatch($block, $config)) {
      // If we have visibility by roles, still restrict access accordingly.
      if ($roles = $this->getAllowedRoles($block)) {
        $allowed = $this->isAllowedByRole($block, $roles);
      }
      else {
        // Assumes visibility by paths in the least.
        $allowed = !empty($match);
      }
    }
    return $allowed;
  }

  /**
   * Returns block visibility request path.
   */
  public function getRequestPath(EntityInterface $block) {
    if ($visibility = $block->getVisibility()) {
      return empty($visibility['request_path']) ? FALSE : $visibility['request_path'];
    }
    return FALSE;
  }

  /**
   * Returns block visibility pages, only concerns if negate is empty.
   */
  public function getVisiblePages(EntityInterface $block) {
    $pages = '';
    if ($request_path = $this->getRequestPath($block)) {
      $pages = empty($request_path['negate']) ? $request_path['pages'] : '';
    }
    return $pages;
  }

  /**
   * Checks block visibility roles.
   */
  public function getAllowedRoles(EntityInterface &$block) {
    if ($visibility_config = $block->getVisibility()) {
      if (isset($visibility_config['user_role'])) {
        return array_values($visibility_config['user_role']['roles']);
      }
    }
    return [];
  }

  /**
   * Checks if the user has access by defined roles.
   */
  public function isAllowedByRole(EntityInterface &$block, array $roles = []) {
    $current_user_roles = array_values($this->currentUser->getRoles());
    foreach ($current_user_roles as $role) {
      if (in_array($role, $roles)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Checks if the visible pages match the current path.
   */
  public function isPageMatch(EntityInterface $block, array $config = []) {
    $page_match = FALSE;
    if ($pages = $this->getVisiblePages($block)) {
      $path = $config['current_path'];

      $langcode = $this->languageManager->getCurrentLanguage()->getId();
      $path_alias = mb_strtolower($this->aliasStorage->lookupPathAlias($path, $langcode));
      $page_match = $this->pathMatcher->matchPath($path_alias, $pages);
      if ($path_alias != $path) {
        $page_match = $page_match || $this->pathMatcher->matchPath($path, $pages);
      }
    }

    return $page_match;
  }

}
