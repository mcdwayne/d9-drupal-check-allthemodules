<?php

namespace Drupal\micon;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Render\Markup;

/**
 * Class MiconIconize.
 *
 * @package Drupal\micon
 */
class MiconIconize extends TranslatableMarkup {

  /**
   * The Micon management service.
   *
   * @var \Drupal\micon\MiconManager
   */
  protected $miconManager;

  /**
   * The Micon icon management service.
   *
   * @var \Drupal\micon\MiconIconManager
   */
  protected $miconDiscoveryManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The MiconIcon object.
   *
   * @var \Drupal\micon\MiconIcon|null
   */
  protected $icon;

  /**
   * The string to match within icon definitions.
   *
   * @var string
   */
  protected $matchString;

  /**
   * The match prefix to append to the match string.
   *
   * @var array
   */
  protected $matchPrefix = [''];

  /**
   * The Icon display options.
   *
   * @var array
   */
  protected $display = [
    'iconOnly' => FALSE,
    'iconPosition' => 'before',
  ];

  /**
   * {@inheritdoc}
   */
  public function __construct($string = '', array $arguments = [], array $options = [], TranslationInterface $string_translation = NULL) {
    if (is_a($string, '\Drupal\Core\StringTranslation\TranslatableMarkup')) {
      $arguments = $string->getArguments();
      $options = $string->getOptions();
      $string_translation = $string->getStringTranslation();
      $string = $string->getUntranslatedString();
    }
    parent::__construct($string, $arguments, $options, $string_translation);
    $this->miconManager = \Drupal::service('micon.icon.manager');
    $this->miconDiscoveryManager = \Drupal::service('plugin.manager.micon.discovery');
    $this->renderer = \Drupal::service('renderer');
  }

  /**
   * Return a class instance.
   */
  public static function iconize($string = '', array $arguments = [], array $options = [], TranslationInterface $string_translation = NULL) {
    return new static(
      $string,
      $arguments,
      $options,
      $string_translation
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $return = $this->getTitle();
    $icon = $this->getIcon();
    if ($icon) {
      if (!$return) {
        $output = [
          '#theme' => 'micon_icon',
          '#icon' => $icon,
        ];
      }
      else {
        $output = [
          '#theme' => 'micon',
          '#icon' => $icon,
          '#title' => Markup::create($return),
          '#icon_only' => $this->display['iconOnly'],
          '#position' => $this->display['iconPosition'],
        ];
      }
      return $this->renderer->render($output);
    }

    return $return;
  }

  /**
   * Only show the icon.
   *
   * @param bool $iconOnly
   *   (optional) Whether to hide the string and only show the icon.
   *
   * @return $this
   */
  public function setIconOnly($iconOnly = TRUE) {
    $this->display['iconOnly'] = $iconOnly;
    return $this;
  }

  /**
   * Show the icon before the title.
   *
   * @return $this
   */
  public function setIconBefore() {
    $this->display['iconPosition'] = 'before';
    return $this;
  }

  /**
   * Show the icon before the title.
   *
   * @return $this
   */
  public function setIconAfter() {
    $this->display['iconPosition'] = 'after';
    return $this;
  }

  /**
   * Given a string, check to see if we have an Micon package icon match.
   *
   * If found it will be set it as the current icon. Using this method to set
   * the icon will skip any automatic text icon lookup.
   *
   * @param string $icon_id
   *   The ID if the icon that should be used. This ID is defined in the
   *   Micon package.
   *
   * @return $this
   */
  public function setIcon($icon_id) {
    $this->icon = $this->miconManager->getIconMatch($icon_id);
    return $this;
  }

  /**
   * Given a string, return the MiconIcon match.
   *
   * If an icon has been found and set using setIcon() that icon will be
   * immediately returned.
   *
   * @param bool $force_match
   *   Force a match lookup even if $this->icon is already set.
   *
   * @return \Drupal\micon\MiconIcon|null
   *   The MiconIcon if found, else null.
   */
  public function getIcon($force_match = FALSE) {
    if ($force_match || !$this->icon) {
      $this->getMatch($this->getMatchString());
    }
    return $this->icon;
  }

  /**
   * Render the object as the title only.
   *
   * @return string
   *   The translated string.
   */
  public function getTitle() {
    return parent::render();
  }

  /**
   * Match a string agaist definition and packages.
   *
   * Match a string against the icon definitions and then against the
   * Micon icon packages and return it as a MiconIcon if it exists.
   *
   * @param string $string
   *   A string that will be used to search through the icon definitions as well
   *   as the Micon icons to return a confirmed match.
   *
   * @return \Drupal\micon\MiconIcon|null
   *   The MiconIcon if found, else null.
   */
  public function getMatch($string) {
    foreach ($this->getMatchPrefix() as $prefix) {
      if ($icon_id = $this->miconDiscoveryManager->getDefinitionMatch($prefix . $string)) {
        $this->setIcon($icon_id);
        break;
      }
    }
    return $this->icon;
  }

  /**
   * The prefix that will be appended to the match string.
   *
   * @param string $string
   *   A string that will be appended to the match string. This is useful when
   *   you want to provide icons with more specific replacements.
   *
   * @return $this
   */
  public function addMatchPrefix($string) {
    $this->matchPrefix[] = $string . '.';
    return $this;
  }

  /**
   * Return the match prefix.
   *
   * @return string
   *   The match prefix.
   */
  public function getMatchPrefix() {
    return $this->matchPrefix;
  }

  /**
   * The machine string to use as the match when looking for icons.
   *
   * @param string $string
   *   A string that will be used to search through the icon definitions as well
   *   as the Micon icons to return a confirmed match.
   *
   * @return $this
   */
  public function setMatchString($string) {
    if (is_a($string, '\Drupal\Core\StringTranslation\TranslatableMarkup')) {
      $string = $string->getUntranslatedString();
    }
    $this->matchString = strtolower(strip_tags($string));
    return $this;
  }

  /**
   * Return cleaned and lowercase string.
   */
  protected function getMatchString() {
    if (!isset($this->matchString)) {
      $this->setMatchString($this->getUntranslatedString());
    }
    return $this->matchString;
  }

}
