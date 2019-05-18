<?php

namespace Drupal\mustache\Helpers;

use Drupal\Core\Url;
use Drupal\mustache\Exception\MustacheException;

/**
 * A helper class for building arrays to render Mustache templates.
 */
class MustacheRenderTemplate {

  /**
   * The render array representation.
   *
   * @var array
   */
  protected $render = ['#type' => 'mustache'];

  /**
   * MustacheRenderTemplate constructor.
   *
   * @param string $template
   *   The name of the Mustache template. It must be either defined
   *   by a hook implementation of hook_mustache_templates(), or
   *   is solely existent as a template file inside the theme.
   *   Example: The template name "foo_bar" is a template file being
   *   located inside the theme directory as "foo-bar.mustache.tpl".
   */
  public function __construct($template) {
    $this->render['#template'] = $template;
  }

  /**
   * Creates a new MustacheRenderTemplate instance.
   *
   * @param string $template
   *   The name of the Mustache template. It must be either defined
   *   by a hook implementation of hook_mustache_templates(), or
   *   is solely existent as a template file inside the theme.
   *   Example: The template name "foo_bar" is a template file being
   *   located inside the theme directory as "foo-bar.mustache.tpl".
   *
   * @return static
   */
  public static function build($template) {
    return new static($template);
  }

  /**
   * Set the data used for rendering.
   *
   * @param mixed $data
   *   An array or object which holds the data for PHP rendering.
   * @param array|null $select
   *   (Optional) The array holding the keys for nested data selection.
   *
   * @return $this
   *   The instance itself.
   */
  public function usingData($data, array $select = NULL) {
    $this->render['#data'] = $data;
    if (isset($select)) {
      $this->selectingSubsetFromData($select);
    }
    return $this;
  }

  /**
   * Define the url from where to receive Json-encoded data.
   *
   * @param \Drupal\Core\Url|string $url
   *   The url as object or string.
   * @param array|null $select
   *   (Optional) The array holding the keys for nested data selection.
   *
   * @return $this
   *   The instance itself.
   */
  public function usingDataFromUrl($url, array $select = NULL) {
    if (!($url instanceof Url) && !is_string($url)) {
      throw new MustacheException(t('The $url param must be a Url object or string.'));
    }
    if (is_string($url)) {
      try {
        $url = Url::fromUri($url);
      }
      catch (\InvalidArgumentException $e) {
        $url = Url::fromUserInput($url);
      }
    }
    $this->render['#data'] = $url;
    if (isset($select)) {
      $this->selectingSubsetFromData($select);
    }
    return $this;
  }

  /**
   * Specifies the subset to select out of the data.
   *
   * @param array $select
   *   The array holding the keys for nested data selection.
   *
   * @return $this
   *   The instance itself.
   */
  public function selectingSubsetFromData(array $select) {
    $this->render['#select'] = $select;
    return $this;
  }

  /**
   * Define a render array to use as a placeholder.
   *
   * @param array $placeholder
   *   An arbitrary render array of the placeholder.
   *
   * @return $this
   *   The instance itself.
   */
  public function withPlaceholder(array $placeholder) {
    $this->render['#placeholder'] = $placeholder;
    return $this;
  }

  /**
   * Determine and configure client-side DOM content synchronization.
   *
   * @return \Drupal\mustache\Helpers\SynchronizationOptions
   *   The synchronization options to define.
   */
  public function withClientSynchronization() {
    return new SynchronizationOptions($this->render);
  }

  /**
   * Get the render array result.
   *
   * @return array
   *   The render array result.
   */
  public function toRenderArray() {
    return $this->render;
  }

}

/**
 * Class SynchronizationOptions.
 *
 * @internal
 */
final class SynchronizationOptions {

  /**
   * The render array representation of the parent instance.
   *
   * @var array
   */
  private $render;

  /**
   * The synchronization item as array.
   *
   * @var array
   */
  private $item;

  /**
   * SynchronizationOptions constructor.
   *
   * @param array &$render_array
   *   The corresponding render array representation.
   */
  public function __construct(array &$render_array) {
    $this->render = &$render_array;
    if (!isset($render_array['#sync']['items'])) {
      $render_array['#sync']['items'] = [];
    }
    $i = count($render_array['#sync']['items']);

    $render_array['#sync']['items'][$i] = [
      'period' => 0,
      'delay' => 0,
    ];

    $this->item = &$render_array['#sync']['items'][$i];
  }

  /**
   * Define the url from where to receive Json-encoded data.
   *
   * @param \Drupal\Core\Url|string $url
   *   The url as object or string.
   * @param array|null $select
   *   (Optional) The array holding the keys for nested data selection.
   *
   * @return $this
   *   The instance itself.
   */
  public function usingDataFromUrl($url, array $select = NULL) {
    if (!($url instanceof Url) && !is_string($url)) {
      throw new MustacheException(t('The $url param must be a Url object or string.'));
    }
    if (is_string($url)) {
      try {
        $url = Url::fromUri($url);
      }
      catch (\InvalidArgumentException $e) {
        $url = Url::fromUserInput($url);
      }
    }
    $this->item['data'] = $url;
    if (isset($select)) {
      $this->selectingSubsetFromData($select);
    }
    return $this;
  }

  /**
   * Specifies the subset to select out of the data.
   *
   * @param array $select
   *   The array holding the keys for nested data selection.
   *
   * @return $this
   *   The instance itself.
   */
  public function selectingSubsetFromData(array $select) {
    $this->item['select'] = $select;
    return $this;
  }

  /**
   * Determine the interval in milliseconds to delay synchronization.
   *
   * @param int $milliseconds
   *   The interval in milliseconds to delay the synchronization start.
   *
   * @return $this
   *   The instance itself.
   */
  public function startsDelayed($milliseconds) {
    $this->item['delay'] = $milliseconds;
    return $this;
  }

  /**
   * Determine the interval in milliseconds to repeat synchronization.
   *
   * @param int $milliseconds
   *   The interval in milliseconds to repeat synchronization.
   *
   * @return $this
   *   The instance itself.
   */
  public function periodicallyRefreshesAt($milliseconds) {
    $this->item['period'] = $milliseconds;
    return $this;
  }

  /**
   * Determine the maximum number of synchronizations.
   *
   * @param int $n
   *   The maximum number of times to run the synchronization.
   *
   * @return $this
   *   The instance itself.
   */
  public function upToNTimes($n) {
    $this->item['limit'] = $n;
    return $this;
  }

  /**
   * Determine that the synchronization is being run only once.
   *
   * @return $this
   *   The instance itself.
   */
  public function once() {
    return $this->upToNTimes(1);
  }

  /**
   * Determine that the number of synchronizations is not limited.
   *
   * @return $this
   *   The instance itself.
   */
  public function unlimited() {
    return $this->upToNTimes(-1);
  }

  /**
   * Determine and configure a triggering element (optional).
   *
   * @param string $css_selector
   *   The CSS element selector.
   *
   * @return \Drupal\mustache\Helpers\TriggeringElementOptions
   *   The configurable options for the triggering element.
   */
  public function startsWhenElementWasTriggered($css_selector) {
    return new TriggeringElementOptions($css_selector, $this->render, $this->item);
  }

  /**
   * Determine the HTML tag to use (optional).
   *
   * By default, a div tag would wrap the rendered template.
   *
   * @param string $html_tag
   *   The HTML tag, e.g. span.
   *
   * @return $this
   *   The instance itself.
   */
  public function withWrapperTag($html_tag) {
    $this->render['#sync']['wrapper_tag'] = $html_tag;
    return $this;
  }

  /**
   * Set custom HTML attributes to the wrapper tag.
   *
   * @param array $attributes
   *   The HTML attributes to set.
   *
   * @return $this
   *   The instance itself.
   */
  public function withWrapperAttributes(array $attributes) {
    $this->render['#attributes'] = $attributes;
    return $this;
  }

  /**
   * Set whether inner script content should be executed.
   *
   * Script execution will be handled via jQuery by using jQuery.html().
   * Warning: Executing arbitrary scripts from untrusted sources
   * might lead your site to be vulnerable for XSS and other attacks.
   *
   * When enabled, Drupal attach and detach behaviors will
   * be executed too. In case you don't which to include Drupal
   * behaviors, you need to explicitly disable them via
   * ::executesDrupalBehaviors(FALSE).
   *
   * @param bool $eval
   *   Set to TRUE to enable inner script execution.
   *   By default, inner script execution is not enabled.
   *
   * @return $this
   *   The instance itself.
   */
  public function executesInnerScripts($eval = TRUE) {
    $this->item['eval'] = $eval;
    return $this;
  }

  /**
   * Enable or disable Drupal's attach and detach behaviors.
   *
   * By default, Drupal behaviors are not enabled, unless
   * script execution is not enabled.
   *
   * @param bool $behaviors
   *   Set to TRUE to enable behaviors, or FALSE
   *   to explicitly disable them, e.g. when eval is TRUE.
   *
   * @return $this
   *   The instance itself.
   */
  public function executesDrupalBehaviors($behaviors = TRUE) {
    $this->item['behaviors'] = $behaviors;
    return $this;
  }

  /**
   * Determine whether and how the rendered content should be inserted.
   *
   * By default, the inner HTML of the wrapping element's would be
   * completely replaced by the synchronized content. When a position
   * is specified though, it will be inserted accordingly.
   *
   * @param string $position
   *   Can be either 'beforebegin', 'afterbegin', 'beforeend'
   *   or 'afterend'. Have a look at the Javascript function
   *   Element.insertAdjacentHTML for more information about
   *   inserting HTML with the position parameter.
   *
   * @return $this
   *   The instance itself.
   */
  public function insertsAt($position) {
    $this->item['adjacent'] = $position;
    return $this;
  }

  /**
   * Enable automatic incrementing.
   *
   * @return \Drupal\mustache\Helpers\IncrementOptions
   *   The increment options.
   */
  public function increments() {
    return new IncrementOptions($this->render, $this->item);
  }

  /**
   * Get the render array result.
   *
   * @return array
   *   The render array result.
   */
  public function toRenderArray() {
    return $this->render;
  }

}

/**
 * Class IncrementOptions.
 *
 * @internal
 */
final class IncrementOptions {

  /**
   * The render array representation of the parent instance.
   *
   * @var array
   */
  protected $render;

  /**
   * The sync item as array representation.
   *
   * @var array
   */
  protected $syncItem;

  /**
   * The increment options.
   *
   * @var array
   */
  protected $increment;

  /**
   * IncrementOptions constructor.
   *
   * @param array &$render_array
   *   The corresponding render array representation.
   * @param array &$sync_item
   *   The sync item as array representation.
   */
  public function __construct(array &$render_array, array &$sync_item) {
    $this->render = &$render_array;
    $this->syncItem = &$sync_item;
    if (!isset($sync_item['increment'])) {
      $sync_item['increment'] = [];
    }
    $this->increment = &$sync_item['increment'];
  }

  /**
   * Define the url parameter to increment.
   *
   * @param string $key
   *   The url parameter. Default parameter would be 'page'.
   *
   * @return $this
   *   The instance itself.
   */
  public function atParamKey($key) {
    $this->increment['key'] = $key;
    return $this;
  }

  /**
   * Define the step size to increment.
   *
   * Example: When set to 5, the parameter value
   * is being incremented by +5.
   *
   * @param int $step
   *   The step size to set. Default step size is 1.
   *
   * @return $this
   *   The instance itself.
   */
  public function withStepSize($step) {
    $this->increment['step'] = $step;
    return $this;
  }

  /**
   * Define an offset from where to start incrementing.
   *
   * Example: When offset is set to 100 and step size is
   * set to 1, the first increment would be 101.
   *
   * @param int $offset
   *   The offset value to set. The default offset value is 0.
   *
   * @return $this
   *   The instance itself.
   */
  public function startingAt($offset) {
    $this->increment['offset'] = $offset;
    return $this;
  }

  /**
   * Define the maximum number of increments.
   *
   * @param int $n
   *   The limit of increments. Default is unlimited (-1).
   *
   * @return $this
   *   The instance itself.
   */
  public function upToNTimes($n) {
    $this->increment['max'] = $n;
    return $this;
  }

  /**
   * Define that the number of increments is unlimited.
   *
   * @return $this
   *   The instance itself.
   */
  public function unlimited() {
    return $this->upToNTimes(-1);
  }

  /**
   * Define that the increment is a loop.
   *
   * When enabled, the increment starts back at the offset, once
   * it has reached its increment limit. It would also restart
   * increments once it has received an empty or "not found" result
   * from the specified url endpoint.
   *
   * @param bool $loops
   *   Set to TRUE to enable, or FALSE to turn off looping.
   *   Default is enabled.
   *
   * @return $this
   *   The instance itself.
   */
  public function loops($loops = TRUE) {
    $this->increment['loop'] = $loops;
    return $this;
  }

}

/**
 * Class TriggeringElementOptions.
 *
 * @internal
 */
final class TriggeringElementOptions {

  /**
   * The CSS element selector.
   *
   * @var string
   */
  protected $selector;

  /**
   * The render array representation of the parent instance.
   *
   * @var array
   */
  protected $render;

  /**
   * The sync item as array representation.
   *
   * @var array
   */
  protected $syncItem;

  /**
   * The part of the render array which holds the trigger options.
   *
   * @var array
   */
  protected $trigger;

  /**
   * TriggeringElementOptions constructor.
   *
   * @param string $css_selector
   *   The CSS element selector.
   * @param array &$render_array
   *   The corresponding render array representation.
   * @param array &$sync_item
   *   The sync item as array representation.
   */
  public function __construct($css_selector, array &$render_array, array &$sync_item) {
    $this->render = &$render_array;
    $this->syncItem = &$sync_item;
    $this->selector = $css_selector;
    if (!isset($sync_item['trigger'])) {
      $sync_item['trigger'] = [];
    }
    $i = count($sync_item['trigger']);
    $sync_item['trigger'][$i] = [$css_selector, 'load', 1];
    $this->trigger = &$sync_item['trigger'][$i];
  }

  /**
   * Determine the Javascript event which would trigger at the element.
   *
   * @param string $event
   *   The Javascript triggering event, e.g. 'click'.
   *
   * @return $this
   *   The instance itself.
   */
  public function atEvent($event) {
    $this->trigger[1] = $event;
    return $this;
  }

  /**
   * Determine how many times the synchronization is being triggered.
   *
   * @param int $n
   *   The maximum number of times to trigger the synchronization.
   *
   * @return $this
   *   The instance itself.
   */
  public function upToNTimes($n) {
    $this->trigger[2] = $n;
    return $this;
  }

  /**
   * Determine that the synchronization is being triggered only once.
   *
   * @return $this
   *   The instance itself.
   */
  public function once() {
    return $this->upToNTimes(1);
  }

  /**
   * Determine that the synchronization is always being triggered.
   *
   * @return $this
   *   The instance itself.
   */
  public function always() {
    return $this->upToNTimes(-1);
  }

  /**
   * Get the render array result.
   *
   * @return array
   *   The render array result.
   */
  public function toRenderArray() {
    return $this->render;
  }

}
