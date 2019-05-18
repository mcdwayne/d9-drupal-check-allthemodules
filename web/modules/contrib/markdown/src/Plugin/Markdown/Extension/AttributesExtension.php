<?php

namespace Drupal\markdown\Plugin\Markdown\Extension;

use League\CommonMark\Environment;
use League\CommonMark\EnvironmentAwareInterface;
use Webuni\CommonMark\AttributesExtension\AttributesExtension as WebuniAttributesExtension;

/**
 * Class AttributesExtension.
 *
 * @MarkdownExtension(
 *   parser = "thephpleague/commonmark",
 *   id = "webuni/commonmark-attributes-extension",
 *   checkClass = "\Webuni\CommonMark\AttributesExtension\AttributesExtension",
 *   composer = "webuni/commonmark-attributes-extension",
 *   label = @Translation("Attributes"),
 *   description = @Translation("Adds a syntax to define attributes on the various HTML elements in markdown’s output."),
 *   homepage = "https://github.com/webuni/commonmark-attributes-extension",
 * )
 */
class AttributesExtension extends CommonMarkExtension implements EnvironmentAwareInterface {

  /**
   * {@inheritdoc}
   */
  public function setEnvironment(Environment $environment) {
    $environment->addExtension(new WebuniAttributesExtension());
  }

}
