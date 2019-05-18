<?php

namespace Drupal\auto_retina\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\image\ImageStyleInterface;

/**
 * Provide the core functionality for the auto_retina module.
 */
class AutoRetina {

  /**
   * An object instance.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * AutoRetina constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   An instance of the config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Adds retina data to the image style based on the uri.
   *
   * @param \Drupal\image\ImageStyleInterface $style
   *   The image style, which will be modified to include the retina settings
   *   based on the uri.
   * @param string $retina_uri
   *   The retina uri.
   *
   * @return string
   *   The non-retina version of the $retina_uri.
   */
  public function prepareStyle(ImageStyleInterface $style, $retina_uri) {
    $settings = [
      'suffix' => '',
      'multiplier' => 1,
      'quality_multiplier' => 1,
    ];
    $original_uri = $retina_uri;
    if (($retina_info = $this->parsePath($retina_uri))) {
      list(, $base, $suffix, $extension) = $retina_info;
      $magnification = preg_replace('/[^0-9\.]/', '', $retina_info[2]) * 1;
      $original_uri = $base . '.' . $extension;
      $settings = [
        'suffix' => $suffix,
        'multiplier' => $magnification,
        'quality_multiplier' => $this->configFactory
            ->get('auto_retina.settings')
            ->get('quality_multiplier') * 1,
      ];
    }
    foreach ($settings as $key => $value) {
      $style->setThirdPartySetting('auto_retina', $key, $value);
    }

    return $original_uri;
  }

  /**
   * Checks if a path is a retina image per settings.
   *
   * @param string $path
   *   E.g. "my-file@2x.png".
   *
   * @return bool
   *   True if a path contains a filename of a magnified image.
   */
  public function isPathRetina($path) {
    $info = $this->parsePath($path);

    return !empty($info[0]);
  }

  /**
   * Parses a "retina" path and returns it's parts.
   *
   * @param string $path
   *   E.g. "my-file@2x.png".
   *
   * @return array
   *   list($original, $base, $suffix, $extension) =
   *   $this->parsePath($path);
   */
  protected function parsePath($path) {
    $info = $this->getSettings();
    preg_match($info['regex'], $path, $matches);

    return $matches;
  }

  /**
   * Returns the module settings array.
   *
   * @return array
   *   - suffix
   *   - regex
   */
  public function getSettings() {
    $suffix = $this->configFactory->get('auto_retina.settings')
      ->get('suffix');
    $regex_pattern = $this->configFactory->get('auto_retina.settings')
      ->get('regex');
    $regex_suffix = '(?:' . implode('|', array_map('preg_quote', explode(' ', $suffix))) . ')';
    $regex = str_replace('SUFFIX', $regex_suffix, $regex_pattern);
    if (strpos($regex, 0, 1) !== '/') {
      $regex = '/' . $regex . '/i';
    }

    return [
      'suffix' => $suffix,
      'regex' => $regex,
    ];
  }

  /**
   * Returns the retina URI of an URI.
   *
   * @param string $uri
   *   If $uri is already a magnified path then return $uri, otherwise convert
   *   it.
   * @param int $magnification
   *   The magnification, e.g. "1.5" or "2".  This must be configured as a
   *   suffix in settings.
   *
   * @return string
   *   The magnified URL based on $magnification.
   *
   * @throws \InvalidArgumentException
   *   When $multiplier is not configured.
   */
  public function getRetinaUri($uri, $magnification = 2) {
    // Just pass through if already a retina path.
    if ($this->parsePath($uri)) {
      return $uri;
    }
    $settings = $this->getSettings();
    $parts = pathinfo($uri);
    $derivative_uri = '';
    if ($parts['dirname'] && $parts['dirname'] !== '.') {
      $derivative_uri = $parts['dirname'] . '/';
    }

    $suffix = array_filter(explode(' ', $settings['suffix']), function ($suffix) use ($magnification) {
      return strstr($suffix, strval($magnification)) !== FALSE;
    });
    if (empty($suffix)) {
      throw new \InvalidArgumentException("The multiplier \"$magnification\" has not been configured in the settings, so it cannot be used.");
    }
    $suffix = array_shift($suffix);
    $derivative_uri .= $parts['filename'] . $suffix . '.' . $parts['extension'];

    return $derivative_uri;
  }

  /**
   * Optimize the style effect for our magnified image.
   *
   * @param int $effect_width
   *   The target width of the effect. This is used as the base for
   *   magnification.
   * @param int|null $effect_height
   *   The target height or null.
   * @param int|float $magnification
   *   The magnification value.
   * @param int $source_width
   *   The native width of the original source image.
   *
   * @return array
   *   - optimum_width int
   *   - is_suboptimum bool
   *   - percent_of_optimum int
   */
  public function optimizeImageSize($effect_width, $effect_height, $magnification, $source_width) {

    // Determine if we need to cap our retina width due to poor quality of original.
    $max_width = NULL;
    if ($source_width < $effect_width) {
      $max_width = $effect_width;
    }
    elseif (!($magnification * $effect_width) <= $source_width) {
      $max_width = $source_width;
    }

    if (!empty($effect_height)) {
      $new_width = $effect_width * $magnification;
      $optimum_retina_width = $new_width;
      if ($max_width) {
        $new_width = min($new_width, $max_width);
      }
      $effect_height = intval(($effect_height / $effect_width) * $new_width);
      $effect_width = intval($new_width);
    }
    else {
      $effect_width *= $magnification;
      $optimum_retina_width = $effect_width;
      if ($max_width) {
        $effect_width = min($effect_width, $max_width);
      }
    }

    return [
      'width' => $effect_width,
      'height' => $effect_height,
      'optimum_width' => intval($optimum_retina_width),
      'is_suboptimum' => $effect_width < $optimum_retina_width,
      'percent_of_optimum' => intval($effect_width * 100 / $optimum_retina_width),
    ];
  }

}
