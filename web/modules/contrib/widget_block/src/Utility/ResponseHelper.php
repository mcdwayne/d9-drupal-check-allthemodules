<?php
/**
 * @file
 * Contains \Drupal\widget_block\Utility\ResponseHelper.
 */

namespace Drupal\widget_block\Utility;

use Psr\Http\Message\ResponseInterface;
use Drupal\Core\Render\Markup;
use Drupal\widget_block\Entity\WidgetBlockConfigInterface;
use Drupal\widget_block\Renderable\WidgetMarkup;

/**
 * Contains helper method for response related operations.
 */
final class ResponseHelper {

    /**
     * Static class only.
     */
    private function __construct() {}

    /**
     * Validate whether the response has valid meta headers.
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     *   An instance of ResponseInterface.
     *
     * @throws \RuntimeException
     *   Indicates at least one meta header is missing.
     */
    public static function validateMeta(ResponseInterface $response) {
      // List of expected response headers which contain meta data.
      static $expected = [
        'X-Widget', 'X-Widget-Mode', 'X-Widget-Language', 
        'X-Widget-Cacheable', 'X-Widget-Created', 'X-Widget-Modified',
        'X-Widget-Refreshed',
      ];
      // Initialize $missing variable to an empty array. This will hold
      // the missing response headers.
      $missing = [];
      // Iterate through the expected response headers.
      foreach ($expected as $name) {
        // Get the response headers for given name.
        $response_headers = $response->getHeader($name);
        // Check whether the response headers are not present.
        if (count($response_headers) === 0) {
          // Append the header name to the list of missing headers.
          $missing[] = $name;
        }
      }
      // Validate whether response does not contain the required meta
      // headers.
      if (count($missing) > 0) {
        // Raise exception with the missing meta headers included.
        throw new \RuntimeException(printf('Missing required meta headers: %s', implode(', ', $missing)));
      }
    }

    /**
     * Validate whether the content type matches.
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     *   The response which should be validated.
     *
     * @throws \RuntimeException
     *   Indicates an mismatching content type.
     */
    public static function validateContentType(ResponseInterface $response, $content_type) {
      // Get the content type headers.
      $response_content_type_headers = $response->getHeader('Content-Type');
      // Extract the content type from the headers.
      $response_content_type = array_pop($response_content_type_headers);
      // Validate whether the content types do not match.
      if (strpos($response_content_type, $content_type) === FALSE) {
        // Raise exception due to mismatching content type.
        throw new RuntimeException(printf('Expected Content-Type "%s" but got "%s"', $content_type, $response_content_type));
      }
    }

    /**
     * Extract the widget related meta data from request.
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     *   An instance of ResponseInterface.
     *
     * @return array
     *   An array which contains the following entries:
     *   <ul>
     *     <li>id</li>
     *     <li>mode</li>
     *     <li>cacheable</li>
     *     <li>created</li>
     *     <li>modified</li>
     *     <li>refreshed</li>
     *     <li>language</li>
     *   </ul>
     *
     * @throws \RuntimeException
     *   Indicates failure to extract meta data from response.
     */
    public static function extractMeta(ResponseInterface $response) {
      // Validate the response metadata.
      static::validateMeta($response);

      $id_headers = $response->getHeader('X-Widget');
      // Get the unique widget identifier to which the response applies.
      $id = array_pop($id_headers);

      $mode_headers = $response->getHeader('X-Widget-Mode');
      // Get the include mode from the mode headers.
      $mode = array_pop($mode_headers);

      $language_headers = $response->getHeader('X-Widget-Language');
      // Get the language code from the language headers.
      $language = array_pop($language_headers);

      $cacheable_headers = $response->getHeader('X-Widget-Cacheable');
      // Determine whether the markup is cacheable.
      $cacheable = array_pop($cacheable_headers) === '1';

      $created_headers = $response->getHeader('X-Widget-Created');
      // Get the integer value for the created timestamp.
      $created = intval(array_pop($created_headers) ?: 0);

      $modified_headers = $response->getHeader('X-Widget-Modified');
      // Get the integer value for the modified timestamp.
      $modified = intval(array_pop($modified_headers) ?: 0);

      $refreshed_headers = $response->getHeader('X-Widget-Refreshed');
      // Get the integer value for the refreshed timestamp.
      $refreshed = intval(array_pop($refreshed_headers) ?: 0);

      return [
        $id,
        $mode,
        $language,
        $cacheable,
        $created,
        $modified,
        $refreshed
      ];
    }

    /**
     * Create markup from specified response.
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     *   An instance of ResponseInterface.
     * @param \Drupal\Core\Language\LanguageInterface $language
     *   Optional. The language for which the widget markup was generated. Defaults
     *   to the language code in the response.
     *
     * @return \Drupal\widget_block\Renderable\WidgetMarkupInterface
     *   An instance of WidgetMarkupInterface.
     *
     * @throws \Exception
     *   Indicates failure to create markup from response.
     */
    public static function createMarkup(ResponseInterface $response) {
      // Validate the whether response contains a non successful status code.
      if ($response->getStatusCode() !== 200) {
        // Raise exception due to unsuccessful response.
        throw new \RuntimeException('Cannot create markup from unsuccessful response');
      }

      // Extract the required meta data from the response.
      list ($id, $mode, $language, $cacheable, $created, $modified, $refreshed) = static::extractMeta($response);

      // Evaluate the type of include mode.
      switch ($mode) {
        // This case requires additional steps as assets are provided
        // in seperate properties.
        case WidgetBlockConfigInterface::MODE_SMART_SSI:
          // Validate the response content type.
          static::validateContentType($response, 'application/json');
          // Get the serialized data from the response.
          $serialized = $response->getBody()->getContents();
          // Unserialize the data using JSON.
          $unserialized = \Drupal::service('serialization.json')->decode($serialized);
          // Get the assets from the unserialized data.
          $assets = $unserialized['assets'];
          // Create markup for the unserialized content.
          $content = Markup::create($unserialized['content']);

          break;

        // Mode other than Smart SSI do not provide seperate
        // assets.
        default:
          // Validate the response content type.
          static::validateContentType($response, 'text/html');
          // Initialize $assets to an empty array as this is not supported.
          $assets = [];
          // Create markup based on the response content.
          $content = Markup::create($response->getBody()->getContents());

          break;
      }

      // Create the widget markup based on the extracted response data.
      return new WidgetMarkup($id, $mode, $language, $content, $assets, $cacheable, $created, $modified, $refreshed);
    }

}
