<?php

namespace Drupal\spamspan\TwigExtension;

/**
 * Provides the SpamSpan filter function within Twig templates.
 */
class SpamSpanExtension extends \Twig_Extension {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;
  
  /**
   * Constructor of SpamSpanExtension.
   *
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer.
   */
  public function __construct($renderer) {
    $this->renderer = $renderer;
  }
  
  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return [
      new \Twig_SimpleFilter('spamspan', [$this, 'spamSpanFilter'], ['is_safe' => ['html']]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'spamspan.twig_extension';
  }

  /**
   * Applying spamspan filter to the given string.
   *
   * @param string $string
   *   Text, maybe containing email addresses.
   *
   * @return string
   *   The input text with emails replaced by spans
   */
  public function spamSpanFilter($string) {
    $template_attached = ['#attached' => ['library' => ['spamspan/obfuscate']]];
    $this->renderer->render($template_attached);
    return spamspan($string);
  }

}
