<?php

namespace Drupal\markdown\Twig\Markdown;

/**
 * Class MarkdownNode.
 */
class Node extends \Twig_Node {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Twig_Node $value, $line, $tag = NULL) {
    parent::__construct(['value' => $value], ['name' => $tag], $line, $tag);
  }

  /**
   * {@inheritdoc}
   */
  public function compile(\Twig_Compiler $compiler) {
    $compiler->addDebugInfo($this)
      ->write('ob_start();' . PHP_EOL)
      ->subcompile($this->getNode('value'))
      ->write('$content = ob_get_clean();' . PHP_EOL)
      ->write('preg_match("/^\s*/", $content, $matches);' . PHP_EOL)
      ->write('$lines = explode("\n", $content);' . PHP_EOL)
      ->write('$content = preg_replace(\'/^\' . $matches[0]. \'/\', "", $lines);' . PHP_EOL)
      ->write('$content = implode("\n", $content);' . PHP_EOL)
      ->write('echo $this->env->getTags()["markdown"]
                                    ->getMarkdown()
                                    ->parse($content);
                ' . PHP_EOL);
  }

}
