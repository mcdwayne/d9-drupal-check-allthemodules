<?php

namespace Drupal\markjs_search\Entity;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Annotation\ConfigEntityType;

/**
 * Define MarkJS profile configuration entity.
 *
 * @ConfigEntityType(
 *   id = "markjs_profile",
 *   label = @Translation("MarkJS Profile"),
 *   admin_permission = "administer markjs profiles",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   config_prefix = "profile",
 *   handlers = {
 *     "form" = {
 *       "add" = "\Drupal\markjs_search\Form\MarkjsProfileForm",
 *       "edit" = "\Drupal\markjs_search\Form\MarkjsProfileForm",
 *       "delete" = "\Drupal\markjs_search\Form\MarkjsProfileDeleteForm"
 *     },
 *     "list_builder" = "\Drupal\markjs_search\Controller\MarkjsProfileList",
 *     "route_provider" = {
 *       "html" = "\Drupal\markjs_search\Entity\Routing\MarkjsProfileRouteProviderDefault"
 *     }
 *   },
 *   links = {
 *     "collection" = "/admin/config/search/markjs-search",
 *     "add-form" = "/admin/config/search/markjs-search/add",
 *     "edit-form" = "/admin/config/search/markjs-search/{markjs_profile}/edit",
 *     "delete-form" = "/admin/config/search/markjs-search/{markjs_profile}/delete"
 *   }
 * )
 */
class MarkjsProfileEntity extends ConfigEntityBase {

  /**
   * @var string
   */
  public $id;

  /**
   * @var string
   */
  public $label;

  /**
   * @var string
   */
  public $element = 'mark';

  /**
   * @var string
   */
  public $class_name;

  /**
   * @var string
   */
  public $exclude;

  /**
   * @var string
   */
  public $accuracy = 'partially';

  /**
   * @var bool
   */
  public $separate_word_search = TRUE;

  /**
   * @var bool
   */
  public $diacritics = TRUE;

  /**
   * @var string
   */
  public $synonyms;

  /**
   * @var bool
   */
  public $iframes = FALSE;

  /**
   * @var int
   */
  public $iframes_timeout = 5000;

  /**
   * @var bool
   */
  public $case_sensitive = FALSE;

  /**
   * @var bool
   */
  public $across_elements = FALSE;

  /**
   * @var bool
   */
  public $ignore_joiners = FALSE;

  /**
   * @var string
   */
  public $ignore_punctuation;

  /**
   * @var string
   */
  public $wildcard = 'disabled';

  /**
   * @var array
   */
  public $callbacks = [];

  /**
   * @var bool
   */
  public $debug = FALSE;

  /**
   * @var string
   */
  public $log = 'console';

  /**
   * Get callback function name.
   *
   * @param $name
   *   The callback name.
   * @param null $default_value
   *   A default value if the callback name doesn't exist.
   *
   * @return mixed|null
   *   The callback function name.
   */
  public function getCallback($name, $default_value = NULL) {
    return isset($this->callbacks[$name])
      ? $this->callbacks[$name]
      : $default_value;
  }

  /**
   * Get the formatted MarkJS options.
   *
   * @return array
   *   An array of formatted options.
   */
  public function getFormattedOptions() {
    $options = [
      'element' => $this->element,
      'className' => $this->class_name,
      'exclude' => $this->getFormattedList('exclude'),
      'accuracy' => $this->accuracy,
      'wildcard' => $this->wildcard,
      'diacritics' => $this->diacritics,
      'synonyms' => $this->synonyms,
      'iframes' => $this->iframes,
      'iframesTimeout' => $this->iframes_timeout,
      'caseSensitive' => $this->case_sensitive,
      'acrossElements' => $this->across_elements,
      'ignoreJoiners' => $this->ignore_joiners,
      'ignorePunctuation' => $this->getFormattedList('ignore_punctuation'),
      'debug' => $this->debug,
      'log' => $this->log
    ] + $this->getFormattedCallbacks();

    return array_filter($options);
  }

  /**
   * Get the formatted list.
   *
   * @param $property
   *   The list property.
   *
   * @return array
   *   A formatted list array.
   */
  protected function getFormattedList($property) {
    if (!isset($this->{$property})) {
      return [];
    }
    $value = $this->{$property};

    return array_filter(array_map('trim', explode(',', $value)));
  }

  /**
   * Get the formatted MarkJS callbacks.
   *
   * @return array
   *   An array of formatted callbacks.
   */
  protected function getFormattedCallbacks() {
    return [
      'each' => $this->getCallback('each'),
      'done' => $this->getCallback('done'),
      'filter' => $this->getCallback('filter'),
      'noMatch' => $this->getCallback('no_match'),
    ];
  }
}
