<?php

namespace Drupal\markdown\Twig\Markdown;

use Drupal\markdown\MarkdownInterface;

/**
 * Class MarkdownTokenParser.
 */
class TokenParser extends \Twig_TokenParser {

  /**
   * An instance of a markdown processor to use.
   *
   * @var \Drupal\markdown\MarkdownInterface
   */
  protected $markdown;

  /**
   * {@inheritdoc}
   */
  public function __construct(MarkdownInterface $markdown) {
    $this->markdown = $markdown;
  }

  /**
   * {@inheritdoc}
   */
  public function parse(\Twig_Token $token) {
    $line = $token->getLine();
    $this->parser->getStream()->expect(\Twig_Token::BLOCK_END_TYPE);
    $body = $this->parser->subparse(function (\Twig_Token $token) {
      return $token->test('endmarkdown');
    }, TRUE);
    $this->parser->getStream()->expect(\Twig_Token::BLOCK_END_TYPE);
    return new Node($body, $line, $this->getTag());
  }

  /**
   * {@inheritdoc}
   */
  public function getTag() {
    return 'markdown';
  }

  /**
   * Return the markdown instance being used.
   *
   * @return \Drupal\markdown\MarkdownInterface
   *   The Markdown instance.
   */
  public function getMarkdown() {
    return $this->markdown;
  }

}
