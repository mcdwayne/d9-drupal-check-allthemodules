<?php

namespace Drupal\mustache\Element;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\mustache\Exception\MustacheException;
use Drupal\mustache\Render\Markup;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Provides an element for rendering Mustache templates.
 *
 * @RenderElement("mustache")
 */
class Mustache extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#theme' => 'mustache',
      '#pre_render' => [
        [$class, 'generateContentMarkup'],
      ],
    ];
  }

  /**
   * Generates content markup for a Mustache template.
   *
   * @param array $element
   *   The element to render.
   *
   * @return array
   *   The element to render.
   *
   * @throws \Exception
   *   In case something went wrong on placeholder rendering,
   *   or when the template or data url could not be found.
   */
  public static function generateContentMarkup(array $element) {
    if (!isset($element['#cache'])) {
      // Caching will be bubbled up if necessary.
      $element['#cache'] = [];
    }

    // Load and encode the template content.
    /** @var \Drupal\mustache\MustacheTemplates $templates */
    $templates = \Drupal::service('mustache.templates');
    /** @var \Drupal\mustache\Summable\SummableScriptsInterface $summables */
    $summables = \Drupal::service('mustache.summables');
    $template_name = $element['#template'];
    $template_content = $templates->getContent($template_name);
    $template_encoded = trim(substr(json_encode($template_content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT), 1, -1));

    // Set default element values.
    $element_defaults = $templates->getElementDefaults($template_name);
    $sync_defaults = isset($element_defaults['#sync']) ? $element_defaults['#sync'] : [];
    unset($element_defaults['#sync']);
    $element += $element_defaults;

    // Attach any retrieved information about the template.
    $element['#template'] = [
      'name' => $template_name,
      'content' => $template_content,
      'encoded' => $template_encoded,
      'summable' => isset($element['#summable']) ? (bool) $element['#summable'] : $summables->isEnabled(),
    ];

    // Begin to determine the data to handle with.
    $data = isset($element['#data']) ? $element['#data'] : NULL;
    $select = isset($element['#select']) ? $element['#select'] : NULL;
    if (is_string($select)) {
      $select = [$select];
    }

    // Obtain the Json data url, if given.
    if ($url = static::getUrlFromParam($data)) {
      $data = NULL;
    }

    if (!empty($element['#sync'])) {
      $element['#use_sync'] = TRUE;
      $use_jquery = FALSE;
      $sync_options = &$element['#sync'];
      $sync_options += $sync_defaults;

      if (!isset($sync_options['items'])) {
        $extract_keys = ['eval', 'behaviors', 'data', 'select', 'increment', 'delay', 'period', 'limit', 'trigger', 'adjacent'];
        $sync_item = [];
        foreach ($extract_keys as $extract_key) {
          if (isset($sync_options[$extract_key])) {
            $sync_item[$extract_key] = $sync_options[$extract_key];
            unset($sync_options[$extract_key]);
          }
        }
        $sync_options['items'] = [$sync_item];
      }

      foreach ($sync_options['items'] as &$sync_item) {
        // Check for the Json data provider url, if given.
        if (!empty($sync_item['data'])) {
          $sync_url = static::getUrlFromParam($sync_item['data']);
        }
        elseif (isset($url)) {
          $sync_url = clone $url;
        }
        else {
          $sync_item['data'] = $data ?: [];
        }
        if (isset($sync_url)) {
          $sync_url->setAbsolute($sync_url->isExternal());
          $sync_item['url'] = $sync_url;
          $sync_item['data'] = $sync_url->toString();
        }
        $sync_item['data'] = json_encode($sync_item['data'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_FORCE_OBJECT);

        // Include nested selection, if specified.
        $sync_select = $select;
        if (!empty($sync_item['select'])) {
          $sync_select = $sync_item['select'];
          if (is_string($sync_select)) {
            $sync_select = [$sync_select];
          }
        }
        if (!empty($sync_select)) {
          $sync_item['select'] = json_encode($sync_select, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT);
        }

        // Include auto increment, if specified.
        if (isset($sync_item['increment']) && $sync_item['increment'] !== FALSE) {
          if (!is_array($sync_item['increment'])) {
            $sync_item['increment'] = [];
          }
          $sync_item['increment'] = json_encode($sync_item['increment']);
        }

        // Use jQuery for inner scripts execution.
        if (!empty($sync_item['eval'])) {
          $use_jquery = TRUE;
        }

        // Include (non-)execution of Drupal behaviors.
        if (isset($sync_item['behaviors'])) {
          $sync_item['behaviors'] = empty($sync_item['behaviors']) ? 'false' : 'true';
        }

        // Include and encode triggering elements, if specified.
        if (isset($sync_item['trigger'])) {
          foreach ($sync_item['trigger'] as &$trigger) {
            $trigger[1] = !empty($trigger[1]) && is_string($trigger[1]) ? $trigger[1] : 'load';
            $trigger[2] = !empty($trigger[2]) && is_int($trigger[2]) ? $trigger[2] : 1;
            if ($trigger[2] < 0) {
              // Always use -1 for triggering without any limits.
              $trigger[2] = -1;
            }
          }
        }
        if (!empty($sync_item['trigger'])) {
          $sync_item['trigger'] = json_encode($sync_item['trigger']);
        }

        // Validate and encode the adjacent option, if specified.
        if (!isset($sync_item['adjacent'])) {
          $sync_options['adjacent'] = NULL;
          $sync_item['adjacent'] = NULL;
        }
        elseif (!in_array($sync_item['adjacent'], ['beforebegin', 'afterbegin', 'beforeend', 'afterend'])) {
          throw new MustacheException(t('Invalid adjacent position :option given. See the Javascript documentation about Element.insertAdjacentHTML which position options are available.', [':option' => $sync_item['adjacent']]));
        }
        elseif (!isset($sync_options['adjacent'])) {
          $sync_options['adjacent'] = $sync_item['adjacent'];
        }
        if (!empty($sync_item['adjacent'])) {
          $sync_item['adjacent'] = json_encode($sync_item['adjacent']);
        }
      }

      // Build and attach the structure for DOM content synchronization.
      $element_id = 'msync-' . Crypt::randomBytesBase64(4);
      $attributes = new Attribute(['id' => $element_id, 'class' => ['mustache-sync', 'not-synced']]);
      $sync_options['attributes'] = $attributes;
      $sync_options['wrapper_tag'] = !empty($sync_options['wrapper_tag']) ? $sync_options['wrapper_tag'] : 'div';
      if ($use_jquery) {
        $element['#attached']['library'][] = 'core/jquery';
      }
      if ($element['#template']['summable']) {
        $element['#attached']['library'][] = $summables->getLibraryName($template_name);
      }
      else {
        $element['#attached']['library'][] = 'mustache/sync';
      }

      // Add further, custom defined attributes.
      if (isset($element['#attributes'])) {
        if (isset($element['#attributes']['class'])) {
          $attributes->addClass($element['#attributes']['class']);
          unset($element['#attributes']['class']);
        }
        foreach ($element['#attributes'] as $attr_name => $attr_value) {
          $attributes->setAttribute($attr_name, $attr_value);
        }
        unset($element['#attributes']);
      }
    }
    else {
      $element['#use_sync'] = FALSE;
      $element['#sync'] = [];
    }

    if (!empty($element['#placeholder'])) {
      // Instead of rendering the content via Mustache.php,
      // render an arbitrary placeholder, by respecting its cache metadata.
      $element_metadata = CacheableMetadata::createFromRenderArray($element);
      $placeholder_metadata = CacheableMetadata::createFromRenderArray($element['#placeholder']);
      $element_metadata->merge($placeholder_metadata)->applyTo($element);

      /** @var \Drupal\Core\Render\Renderer $renderer */
      $renderer = \Drupal::service('renderer');
      $rendered = $renderer->render($element['#placeholder']);
      $element['#content'] = Markup::create($rendered);
    }
    else {
      // Render the content via Mustache.php.
      if (!isset($data) && isset($url)) {
        // Rendering via Mustache.php requires some data,
        // which now must be fetched from the url.
        $client = \Drupal::httpClient();
        $uri = $url->setAbsolute(TRUE)->toString();
        try {
          $response = $client->request('GET', $uri);
        }
        catch (GuzzleException $e) {
          \Drupal::logger('mustache')->error(t('Failed to fetch data from @url. Exception message: @message', ['@url' => $uri, '@message' => $e->getMessage()]));
          return static::emptyElement();
        }

        if (!($response->getStatusCode() == 200)) {
          \Drupal::logger('mustache')->error(t('Failed to fetch data from @url. Returned status code was: @status', ['@url' => $uri, '@status' => $response->getStatusCode()]));
          return static::emptyElement();
        }

        $body = $response->getBody()->getContents();
        $data = json_decode($body, TRUE);
        if (NULL === $data) {
          \Drupal::logger('mustache')->error(t('Invalid Json received from from @url.', ['@url' => $uri]));
          return static::emptyElement();
        }
      }
      if (!isset($data)) {
        // In case we still don't have any data,
        // we cannot continue rendering.
        \Drupal::logger('mustache')->error(t('Failed to get data for rendering Mustache template @template', ['@template' => $template_name]));
        return static::emptyElement();
      }

      if (!empty($select) && !empty($data)) {
        if (!is_array($data)) {
          // In case something different was passed,
          // try to convert it. When it fails, it should fail hard.
          $data = (array) $data;
        }
        $subset_exists = NULL;
        $data = NestedArray::getValue($data, $select, $subset_exists);
        if (!$subset_exists) {
          // Subset must be an array.
          $data = [];
        }
      }

      /** @var \Mustache_Engine $mustache */
      $mustache = \Drupal::service('mustache.engine');
      $output = $mustache->loadTemplate($template_content)->render($data);
      $element['#content'] = Markup::create($output);
    }

    return $element;
  }

  /**
   * Returns an empty element which is not cacheable.
   *
   * This element would only be used at unusual circumstances.
   * Because it's not what the result should look like,
   * the caching is completely disabled for being able
   * to generate an expected result as soon as possible.
   *
   * @return array
   *   The empty element.
   */
  public static function emptyElement() {
    return ['#cache' => ['max-age' => 0], '#printed' => TRUE, '#markup' => Markup::create('')];
  }

  /**
   * Helper function to get the url from the given parameter.
   *
   * @param mixed $param
   *   The given parameter.
   *
   * @return \Drupal\Core\Url|null
   *   The url object, if found.
   */
  protected static function getUrlFromParam($param) {
    if ($param instanceof Url) {
      return $param;
    }
    elseif (is_string($param)) {
      try {
        return Url::fromUri($param);
      }
      catch (\InvalidArgumentException $e) {
        return Url::fromUserInput($param);
      }
    }
    return NULL;
  }

}
