<?php

/**
 * @file
 * Definition of Drupal\breakpoint\Breakpoint.
 */

namespace Drupal\breakpoint;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\breakpoint\InvalidBreakpointException;
use Drupal\breakpoint\InvalidBreakpointNameException;
use Drupal\breakpoint\InvalidBreakpointSourceException;
use Drupal\breakpoint\InvalidBreakpointSourceTypeException;
use Drupal\breakpoint\InvalidBreakpointMediaQueryException;

/**
 * Defines the Breakpoint entity.
 */
class Breakpoint extends ConfigEntityBase {

  /**
   * Denotes that a breakpoint or breakpoint group is defined by a theme.
   */
  const SOURCE_TYPE_THEME = 'theme';

  /**
   * Denotes that a breakpoint or breakpoint group is defined by a module.
   */
  const SOURCE_TYPE_MODULE = 'module';

  /**
   * Denotes that a breakpoint or breakpoint group is defined by the user.
   */
  const SOURCE_TYPE_CUSTOM = 'custom';

  /**
   * The breakpoint ID (config name).
   *
   * @var string
   */
  public $id;

  /**
   * The breakpoint UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * The breakpoint name (machine name).
   *
   * @var string
   */
  public $name;

  /**
   * The breakpoint label.
   *
   * @var string
   */
  public $label;

  /**
   * The breakpoint media query.
   *
   * @var string
   */
  public $mediaQuery = '';

  /**
   * The original media query.
   *
   * This is used to store the original media query as defined by the theme or
   * module, so reverting the breakpoint can be done without reloading
   * everything from the theme/module yaml files.
   *
   * @var string
   */
  public $originalMediaQuery = '';

  /**
   * The breakpoint source.
   *
   * @var string
   */
  public $source = 'user';

  /**
   * The breakpoint source type.
   *
   * @var string
   *   Allowed values:
   *     Breakpoint::SOURCE_TYPE_THEME
   *     Breakpoint::SOURCE_TYPE_MODULE
   *     Breakpoint::SOURCE_TYPE_CUSTOM
   */
  public $sourceType = Breakpoint::SOURCE_TYPE_CUSTOM;

  /**
   * The breakpoint status.
   *
   * @var string
   */
  public $status = TRUE;

  /**
   * The breakpoint weight.
   *
   * @var weight
   */
  public $weight = 0;

  /**
   * The breakpoint multipliers.
   *
   * @var multipliers
   */
  public $multipliers = array();

  /**
   * The breakpoint overridden status.
   *
   * @var boolean
   */
  public $overridden = FALSE;

  /**
   * Overrides Drupal\config\ConfigEntityBase::__construct().
   */
  public function __construct(array $values = array(), $entity_type = 'breakpoint') {
    parent::__construct($values, $entity_type);
  }

  /**
   * Overrides Drupal\config\ConfigEntityBase::save().
   */
  public function save() {
    if (empty($this->id)) {
      $this->id = $this->getConfigName();
    }
    if (empty($this->label)) {
      $this->label = drupal_ucfirst($this->name);
    }

    // Check if everything is valid.
    if (!$this->isValid()) {
      throw new InvalidBreakpointException('Invalid data detected.');
    }
    // Remove ununsed multipliers.
    $this->multipliers = array_filter($this->multipliers);

    // Always add '1x' multiplier.
    if (!array_key_exists('1x', $this->multipliers)) {
      $this->multipliers = array('1x' => '1x') + $this->multipliers;
    }
    return parent::save();
  }

  /**
   * Get config name.
   *
   * @return string
   */
  public function getConfigName() {
    return $this->sourceType . '.' . $this->source . '.' . $this->name;
  }

  /**
   * Override a breakpoint and save it.
   *
   * @return Drupal\breakpoint\Breakpoint|false
   */
  public function override() {
    // Custom breakpoint can't be overridden.
    if ($this->overridden || $this->sourceType === Breakpoint::SOURCE_TYPE_CUSTOM) {
      return FALSE;
    }

    // Mark breakpoint as overridden.
    $this->overridden = TRUE;
    $this->originalMediaQuery = $this->mediaQuery;
    $this->save();
    return $this;
  }

  /**
   * Revert a breakpoint and save it.
   *
   * @return Drupal\breakpoint\Breakpoint|false
   */
  public function revert() {
    if (!$this->overridden || $this->sourceType === Breakpoint::SOURCE_TYPE_CUSTOM) {
      return FALSE;
    }

    $this->overridden = FALSE;
    $this->mediaQuery = $this->originalMediaQuery;
    $this->save();
    return $this;
  }

  /**
   * Duplicate a breakpoint.
   *
   * The new breakpoint inherits the media query.
   *
   * @return Drupal\breakpoint\Breakpoint
   */
  public function duplicate() {
    return entity_create('breakpoint', array(
      'mediaQuery' => $this->mediaQuery,
    ));
  }

  /**
   * Shortcut function to enable a breakpoint and save it.
   *
   * @see breakpoint_action_confirm_submit()
   */
  public function enable() {
    if (!$this->status) {
      $this->status = TRUE;
      $this->save();
    }
  }

  /**
   * Shortcut function to disable a breakpoint and save it.
   *
   * @see breakpoint_action_confirm_submit()
   */
  public function disable() {
    if ($this->status) {
      $this->status = FALSE;
      $this->save();
    }
  }

  /**
   * Check if the breakpoint is valid.
   *
   * @throws Drupal\breakpoint\InvalidBreakpointSourceTypeException
   * @throws Drupal\breakpoint\InvalidBreakpointSourceException
   * @throws Drupal\breakpoint\InvalidBreakpointNameException
   * @throws Drupal\breakpoint\InvalidBreakpointMediaQueryException
   *
   * @see isValidMediaQuery()
   */
  public function isValid() {
    // Check for illegal values in breakpoint source type.
    if (!in_array($this->sourceType, array(
        Breakpoint::SOURCE_TYPE_CUSTOM,
        Breakpoint::SOURCE_TYPE_MODULE,
        Breakpoint::SOURCE_TYPE_THEME)
      )) {
      throw new InvalidBreakpointSourceTypeException(format_string('Invalid source type @source_type', array(
        '@source_type' => $this->sourceType,
      )));
    }
    // Check for illegal characters in breakpoint source.
    if (preg_match('/[^a-z_]+/', $this->source)) {
      throw new InvalidBreakpointSourceException(format_string("Invalid value '@source' for breakpoint source property. Breakpoint source property can only contain lowercase letters and underscores.", array('@source' => $this->source)));
    }
    // Check for illegal characters in breakpoint names.
    if (preg_match('/[^0-9a-z_\-]/', $this->name)) {
      throw new InvalidBreakpointNameException(format_string("Invalid value '@name' for breakpoint name property. Breakpoint name property can only contain lowercase alphanumeric characters, underscores (_), and hyphens (-).", array('@name' => $this->name)));
    }
    return $this::isValidMediaQuery($this->mediaQuery);
  }

  /**
   * Is the breakpoint editable.
   *
   * @return boolean
   */
  public function isEditable() {
    // Custom breakpoints are always editable.
    if ($this->sourceType == Breakpoint::SOURCE_TYPE_CUSTOM) {
      return TRUE;
    }
    // Overridden breakpoints are editable.
    if ($this->overridden) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Check if a mediaQuery is valid.
   *
   * @throws Drupal\breakpoint\InvalidBreakpointMediaQueryException
   *
   * @return true
   *
   * @see http://www.w3.org/TR/css3-mediaqueries/
   * @see http://www.w3.org/Style/CSS/Test/MediaQueries/20120229/reports/implement-report.html
   * @see https://github.com/adobe/webkit/blob/master/Source/WebCore/css/
   */
  public static function isValidMediaQuery($media_query) {
    $media_features = array(
      'width' => 'length', 'min-width' => 'length', 'max-width' => 'length',
      'height' => 'length', 'min-height' => 'length', 'max-height' => 'length',
      'device-width' => 'length', 'min-device-width' => 'length', 'max-device-width' => 'length',
      'device-height' => 'length', 'min-device-height' => 'length', 'max-device-height' => 'length',
      'orientation' => array('portrait', 'landscape'),
      'aspect-ratio' => 'ratio', 'min-aspect-ratio' => 'ratio', 'max-aspect-ratio' => 'ratio',
      'device-aspect-ratio' => 'ratio', 'min-device-aspect-ratio' => 'ratio', 'max-device-aspect-ratio' => 'ratio',
      'color' => 'integer', 'min-color' => 'integer', 'max-color' => 'integer',
      'color-index' => 'integer', 'min-color-index' => 'integer', 'max-color-index' => 'integer',
      'monochrome' => 'integer', 'min-monochrome' => 'integer', 'max-monochrome' => 'integer',
      'resolution' => 'resolution', 'min-resolution' => 'resolution', 'max-resolution' => 'resolution',
      'scan' => array('progressive', 'interlace'),
      'grid' => 'integer',
    );
    if ($media_query) {
      // Strip new lines and trim.
      $media_query = str_replace(array("\r", "\n"), ' ', trim($media_query));

      // Remove comments /* ... */.
      $media_query = preg_replace('/\/\*[\s\S]*?\*\//', '', $media_query);

      // Check mediaQuery_list: S* [mediaQuery [ ',' S* mediaQuery ]* ]?
      $parts = explode(',', $media_query);
      foreach ($parts as $part) {
        // Split on ' and '
        $query_parts = explode(' and ', trim($part));
        $media_type_found = FALSE;
        foreach ($query_parts as $query_part) {
          $matches = array();
          // Check expression: '(' S* media_feature S* [ ':' S* expr ]? ')' S*
          if (preg_match('/^\(([\w\-]+)(:\s?([\w\-\.]+))?\)/', trim($query_part), $matches)) {
            // Single expression.
            if (isset($matches[1]) && !isset($matches[2])) {
              if (!array_key_exists($matches[1], $media_features)) {
                throw new InvalidBreakpointMediaQueryException('Invalid media feature detected.');
              }
            }
            // Full expression.
            elseif (isset($matches[3]) && !isset($matches[4])) {
              $value = trim($matches[3]);
              if (!array_key_exists($matches[1], $media_features)) {
                throw new InvalidBreakpointMediaQueryException('Invalid media feature detected.');
              }
              if (is_array($media_features[$matches[1]])) {
                // Check if value is allowed.
                if (!array_key_exists($value, $media_features[$matches[1]])) {
                  throw new InvalidBreakpointMediaQueryException('Value is not allowed.');
                }
              }
              else {
                switch ($media_features[$matches[1]]) {
                  case 'length':
                    $length_matches = array();
                    if (preg_match('/^(\-)?(\d+(?:\.\d+)?)?((?:|em|ex|px|cm|mm|in|pt|pc|deg|rad|grad|ms|s|hz|khz|dpi|dpcm))$/i', trim($value), $length_matches)) {
                      // Only -0 is allowed.
                      if ($length_matches[1] === '-' && $length_matches[2] !== '0') {
                        throw new InvalidBreakpointMediaQueryException('Invalid length detected.');
                      }
                      // If there's a unit, a number is needed as well.
                      if ($length_matches[2] === '' && $length_matches[3] !== '') {
                        throw new InvalidBreakpointMediaQueryException('Unit found, value is missing.');
                      }
                    }
                    else {
                      throw new InvalidBreakpointMediaQueryException('Invalid unit detected.');
                    }
                    break;
                }
              }
            }
          }

          // Check [ONLY | NOT]? S* media_type
          elseif (preg_match('/^((?:only|not)?\s?)([\w\-]+)$/i', trim($query_part), $matches)) {
            if ($media_type_found) {
              throw new InvalidBreakpointMediaQueryException('Only one media type is allowed.');
            }
            $media_type_found = TRUE;
          }
          else {
            throw new InvalidBreakpointMediaQueryException('Invalid media query detected.');
          }
        }
      }
      return TRUE;
    }
    throw new InvalidBreakpointMediaQueryException('Media query is empty.');
  }
}
