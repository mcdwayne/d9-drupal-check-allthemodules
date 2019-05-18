<?php

namespace Drupal\formatter_suite;

use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Defines functions and constants used for branding.
 *
 * The module's administrator-visible pages may be branded with the
 * module's logo, credit text, and links.
 *
 * <B>Warning:</B> This class is strictly internal to the Formatter Suite
 * module. The class's existance, name, and content may change from
 * release to release without any promise of backwards compatability.
 *
 * @ingroup formatter_suite
 */
final class Branding {

  /*---------------------------------------------------------------------
   *
   * Constants
   *
   *---------------------------------------------------------------------*/

  /**
   * The module's logo file.
   *
   * @var string
   */
  const LOGO_FILE_NAME = 'LogoIconAndText24.png';

  /**
   * The module's images directory.
   *
   * @var string
   */
  const MODULE_IMAGES_SUBDIRECTORY = 'images';

  /**
   * The module's branding library.
   *
   * @var string
   */
  const MODULE_BRANDING_LIBRARY = 'formatter_suite/formatter_suite.branding';

  /**
   * The untranslated text for the National Science Foundation.
   *
   * @var string
   */
  const NSF_TEXT = 'National Science Foundation (NSF)';

  /**
   * The untranslated text for the University of California at San Diego.
   *
   * @var string
   */
  const UCSD_TEXT = 'University of California at San Diego (UCSD)';

  /**
   * The untranslated text for the San Diego Supercomputer Center.
   *
   * @var string
   */
  const SDSC_TEXT = 'San Diego Supercomputer Center (SDSC)';

  /**
   * The URL for the National Science Foundation.
   *
   * @var string
   */
  const NSF_URL = 'https://www.nsf.gov/';

  /**
   * The URL for the University of California at San Diego.
   *
   * @var string
   */
  const UCSD_URL = 'https://www.ucsd.edu/';

  /**
   * The URL for the San Diego Supercomputer Center.
   *
   * @var string
   */
  const SDSC_URL = 'https://www.sdsc.edu';

  /*---------------------------------------------------------------------
   *
   * Branding.
   *
   *---------------------------------------------------------------------*/

  /**
   * Adds branding to a field formatter form.
   *
   * This function wraps a field formatter's form with <div>s and adds
   * a logo image to the top. Module styling then adds a background, sets
   * colors, etc.
   *
   * @param array $form
   *   The renderable form array to which to add branding items.
   *
   * @return array
   *   Returns the form with the branding added.
   *
   * @internal
   * Field formatter plugins are used by the Field UI and Views UI modules
   * to present an administrator form to control the formatting of a field
   * in an entity. The Field UI and Views UI modules use AJAX to insert
   * the plugin's form within a larger form.
   *
   * Structurally the inserted form is expected to have one form element
   * for each setting defined by the plugin. The form elements must be
   * named after the setting and they cannot be nested within a container,
   * or any other render or form structure.
   *
   * This required structure prevents us from introducing a wrapper render
   * element around the form, then styling that wrapper. Instead, this
   * function adds a '#prefix' and '#suffix' to the form, plus an attached
   * branding library. The prefix adds a <div> and a logo image, while the
   * suffix closes the <div>.
   * @endinternal
   */
  public static function addFieldFormatterBranding(array &$form) {
    //
    // Setup
    // -----
    // Get module information.
    $module = \Drupal::moduleHandler()->getModule('formatter_suite');
    $moduleName = $module->getName();
    $modulePath = '/' . $module->getPath() . '/';
    $moduleImagesPath = $modulePath . self::MODULE_IMAGES_SUBDIRECTORY . '/';

    // Get the path to the module's logo.
    $logoPath = $moduleImagesPath . self::LOGO_FILE_NAME;

    // Define some CSS classes.
    $wrapperClass = 'formatter_suite-field-formatter-settings';
    $logoClass = 'formatter_suite-branding-logo';

    //
    // Create prefix & suffix HTML
    // ---------------------------
    // Create the image HTML.
    $logoImage = '<img class="' . $logoClass .
      '" alt="' . $moduleName .
      '" src="' . $logoPath . '">';

    // Create the prefix and suffix.
    $form['#prefix'] = '<div class="' . $wrapperClass . '">' . $logoImage;
    $form['#suffix'] = '</div>';

    // Attach the module's branding library.
    $form['#attached']['library'][] = self::MODULE_BRANDING_LIBRARY;

    return $form;
  }

  /**
   * Returns a branding banner.
   *
   * This function returns a render element that may be added to a page
   * to create a branding banner. The banner includes a container,
   * a logo image, and brief credit text with links and the module's
   * version number.
   *
   * @return array
   *   Renders a renderable form array containing branding items.
   */
  public static function getBannerBranding() {
    //
    // Setup
    // -----
    // Get module information.
    $module = \Drupal::moduleHandler()->getModule('formatter_suite');
    $modulePath = '/' . $module->getPath() . '/';
    $moduleImagesPath = $modulePath . self::MODULE_IMAGES_SUBDIRECTORY . '/';

    // To get the module's version number, we have to parse its YML info file.
    $moduleInfo = \Drupal::service('info_parser')->parse($module->getPathname());
    $moduleVersion = $moduleInfo['version'];

    // Get the path to the module's logo.
    $logoPath = $moduleImagesPath . self::LOGO_FILE_NAME;

    // Define some CSS classes.
    $containerClass = 'formatter_suite-banner-branding';
    $logoClass = 'formatter_suite-branding-logo';

    //
    // Create image HTML and links
    // ---------------------------
    // Create the image HTML.
    $logoImage = '<img class="' . $logoClass .
      '" alt="' . $module->getName() .
      '" src="' . $logoPath . '">';

    // Create links.
    $sdscLink = Link::fromTextAndUrl(
      self::SDSC_TEXT,
      Url::fromUri(self::SDSC_URL))->toString();
    $ucsdLink = Link::fromTextAndUrl(
      self::UCSD_TEXT,
      Url::fromUri(self::UCSD_URL))->toString();
    $nsfLink = Link::fromTextAndUrl(
      self::NSF_TEXT,
      Url::fromUri(self::NSF_URL))->toString();

    // Create credit text.
    $credit = t(
      "Developed by the @sdsc at the @ucsd and funded by the @nsf.",
      [
        '@sdsc' => $sdscLink,
        '@ucsd' => $ucsdLink,
        '@nsf'  => $nsfLink,
      ]);

    // Create version text.
    $version = t(
      'Version @version',
      [
        '@version' => $moduleVersion,
      ]);

    //
    // Return render elements
    // ----------------------
    // Return a render array with a container, the image, credits, and
    // the module version number.
    return [
      '#type'       => 'container',
      '#attributes' => [
        'class'     => [$containerClass],
      ],
      '#attached'   => [
        'library'   => [self::MODULE_BRANDING_LIBRARY],
      ],
      'logo'        => [
        '#markup'   => $logoImage,
      ],
      'credits'     => [
        '#type'     => 'html_tag',
        '#tag'      => 'div',
        '#value'   => $credit,
      ],
      'version'     => [
        '#type'     => 'html_tag',
        '#tag'      => 'div',
        '#value'    => $version,
      ],
    ];
  }

}
