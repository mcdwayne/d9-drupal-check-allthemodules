<?php

namespace Drupal\markdown\Plugin\Markdown\Extension;

use League\CommonMark\Environment;
use League\CommonMark\EnvironmentAwareInterface;
use Webuni\CommonMark\TableExtension\TableExtension as WebuniTableExtension;

/**
 * Class TableExtension.
 *
 * @MarkdownExtension(
 *   parser = "thephpleague/commonmark",
 *   id = "webuni/commonmark-table-extension",
 *   checkClass = "\Webuni\CommonMark\TableExtension\TableExtension",
 *   composer = "webuni/commonmark-table-extension",
 *   label = @Translation("Table"),
 *   description = @Translation("Adds the ability to create tables in CommonMark documents."),
 *   homepage = "https://github.com/webuni/commonmark-table-extension",
 * )
 */
class TableExtension extends CommonMarkExtension implements EnvironmentAwareInterface {

  /**
   * {@inheritdoc}
   */
  public function setEnvironment(Environment $environment) {
    $environment->addExtension(new WebuniTableExtension());
  }

}
