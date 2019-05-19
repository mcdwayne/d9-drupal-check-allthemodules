<?php

namespace Drupal\skype\Manager;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Template\Attribute;

class SkypeManager {

  protected $config;
  protected $currentPath;
  protected $aliasManager;
  protected $pathMatcher;

  /**
   * SkypeManager constructor.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   */
  public function __construct(ConfigFactoryInterface $config_factory, CurrentPathStack $current_path, AliasManagerInterface $alias_manager, PathMatcherInterface $path_matcher) {
    $this->config = $config_factory->get('skype.settings');
    $this->currentPath = $current_path;
    $this->aliasManager = $alias_manager;
    $this->pathMatcher = $path_matcher;
  }

  /**
   * Checks if shield has to be shown on current page.
   * @return bool
   */
  public function loadSkypeChatCanvas() {
    $page_match = &drupal_static(__FUNCTION__);

    if (!$this->config->get('enable_chat')) {
      return FALSE;
    }

    // Cache visibility result if function is called more than once.
    if (!isset($page_match)) {
      $visibility_path_mode = $this->config->get('exclude_mode');
      $visibility_path_pages = $this->config->get('exclude_pages');

      // Match path if necessary.
      if (!empty($visibility_path_pages)) {
        // Convert path to lowercase. This allows comparison of the same path
        // with different case. Ex: /Page, /page, /PAGE.
        $pages = Unicode::strtolower($visibility_path_pages);
        // Compare the lowercase path alias (if any) and internal path.
        $path = $this->currentPath->getPath();
        $path_alias = Unicode::strtolower($this->aliasManager->getAliasByPath($path));
        $page_match = $this->pathMatcher->matchPath($path_alias, $pages) || (($path != $path_alias) && $this->pathMatcher->matchPath($path, $pages));
        // When $visibility_request_path_mode has a value of 0, the shield
        // is displayed on all pages except those listed in $pages. When
        // set to 1, it is displayed only on those pages listed in $pages.
        $page_match = !($visibility_path_mode xor $page_match);
      }
      else {
        $page_match = TRUE;
      }

    }
    return $page_match;
  }

  /**
   * Returns message recipient.
   * @return string
   */
  public function getMessageRecipient() {
    return $this->config->get('message_recipient');
  }

  /**
   * Returns chat id.
   * @return string
   */
  public function getChatId() {
    return $this->config->get('chat_id');
  }

  /**
   * Returns atrributes.
   * @return \Drupal\Core\Template\Attribute
   */
  public function getAttributes() {
    $attributes = [];
    $classes = [$this->config->get('initiate_chat')];

    $initiate_chat = $this->config->get('initiate_chat');
    $button_style = $this->config->get('button_style');
    if ($initiate_chat == 'skype-button') {
      // If button is chosen, add class to indicate button style.
      array_push($classes, $button_style);

      if ($button_style == 'rectangle' || $button_style == 'rounded') {
        // Allow to add button text and button color.
        if ($button_text = $this->config->get('button_text')) {
          $attributes['data-text'] = $button_text;
        }
        if ($button_color = $this->config->get('button_color')) {
          $attributes['data-color'] = $button_color;
        }

        if($this->config->get('text_only')){
          array_push($classes, 'textonly');
        }
      }
    }elseif ($initiate_chat == 'skype-chat') {
      $attributes['data-can-collapse'] = $this->config->get('chat_can_collapse') ? 'true': 'false';
      $attributes['data-can-close'] = $this->config->get('chat_can_close') ? 'true': 'false';
      $attributes['data-can-upload-file'] = $this->config->get('chat_can_upload_file') ? 'true': 'false';
      $attributes['data-entry-animation'] = $this->config->get('chat_enable_animation') ? 'true': 'false';
      $attributes['data-show-header'] = $this->config->get('chat_enable_header') ? 'true': 'false';
    }
    $attributes['class'] = $classes;

    return new Attribute($attributes);
  }

}