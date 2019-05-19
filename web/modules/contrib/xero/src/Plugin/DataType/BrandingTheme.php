<?php

namespace Drupal\xero\Plugin\DataType;

/**
 * @DataType(
 *   id = "xero_branding_theme",
 *   label = @Translation("Xero Branding Theme"),
 *   definition_class = "\Drupal\xero\TypedData\Definition\BrandingThemingDefinition",
 *   list_class = "\Drupal\xero\Plugin\DataType\XeroItemList"
 * )
 */
class BrandingTheme extends XeroTypeBase {
  static public $guid_name = 'BrandingThemeID';
  static public $xero_name = 'BrandingTheme';
  static public $plural_name = 'BrandingThemes';
  static public $label = 'Name';
}