<?php

/**
 * @file
 * Contains \Drupal\smartling\Encoder\SmartlingXmlEncoder.
 */

namespace Drupal\smartling_config_translation\Encoder;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\serialization\Encoder\XmlEncoder;
use Drupal\smartling\Encoder\XmlEncoder as CustomXmlEncoder;

/**
 * Adds Smartling XML support for serializer.
 */
class SmartlingConfigTranslationXmlEncoder extends XmlEncoder {

  /**
   * {@inheritdoc}
   */
  static protected $format = ['smartling_xml'];

  /**
   * Smartling settings config object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Constructs SmartlingXmlEncoder object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config) {
    $this->config = $config->get('smartling.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseEncoder() {
    if (!isset($this->baseEncoder)) {
      $placeholder = $this->config->get('expert.custom_regexp_placeholder');

      //@todo: move this to config
      $comments = " smartling.translate_paths = document/%/%, document/% \n smartling.string_format_paths = html : document/%/%, html : document/% ";
      //$this->config->get('expert.xml_comments');

      // @todo Simplify custom encoder.
      $this->baseEncoder = new CustomXmlEncoder('document',
        array_merge(array_filter(explode("\n", $comments)), [
          "smartling.placeholder_format_custom = $placeholder",
        ])
      );
    }

    return $this->baseEncoder;
  }

}
