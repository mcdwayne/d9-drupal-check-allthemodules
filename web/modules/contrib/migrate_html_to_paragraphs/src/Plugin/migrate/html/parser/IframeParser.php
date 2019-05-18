<?php

namespace Drupal\migrate_html_to_paragraphs\Plugin\migrate\html\parser;

/**
 * Migration HTML - iframe parser.
 *
 * @MigrateHtmlParserPlugin(
 *   id = "html_parser_iframe"
 * )
 */
class IframeParser extends HtmlTagParser {

  /**
   * {@inheritdoc}
   */
  protected function definePattern() {
    return '/<iframe[^>]*><\/iframe>/iSu';
  }

  /**
   * {@inheritdoc}
   */
  protected function parseTag($tag) {
    $data = [
      'type'   => 'iframe',
      'tag'    => $tag,
      'src'    => $this->parseTagSource($tag),
      'width'  => $this->parseTagWidth($tag),
      'height' => $this->parseTagHeight($tag),
      'class'  => $this->parseTagClass($tag),
      'style'  => $this->parseTagStyle($tag),
    ];

    return $data;
  }

  /**
   * Helper to parse the src from the iframe tag.
   *
   * @param string $tag
   *   The iframe tag.
   *
   * @return string|null
   *   The parsed source (src).
   */
  protected function parseTagSource($tag) {
    return $this->parseTagByPattern($tag, '/src="([^"]*)"/iSu');
  }

  /**
   * Helper to parse the width from the iframe tag.
   *
   * @param string $tag
   *   The iframe tag.
   *
   * @return int|null
   *   Returns the width or NULL if not found.
   */
  protected function parseTagWidth($tag) {
    $width = $this->parseTagByPattern($tag, '/width="([0-9]+)"/iSu');
    if ($width) {
      $width = (int) $width;
    }
    return $width;
  }

  /**
   * Helper to parse the height from the iframe tag.
   *
   * @param string $tag
   *   The iframe tag.
   *
   * @return int|null
   *   Returns the height or NULL if not found.
   */
  protected function parseTagHeight($tag) {
    $height = $this->parseTagByPattern($tag, '/height="([0-9]+)"/iSu');
    if ($height) {
      $height = (int) $height;
    }
    return $height;
  }

  /**
   * Helper to parse the class from the iframe tag.
   *
   * @param string $tag
   *   The iframe tag.
   *
   * @return int|null
   *   Returns the class or NULL if not found.
   */
  protected function parseTagClass($tag) {
    return $this->parseTagByPattern($tag, '/class="([^"]*)"/iSu');
  }

  /**
   * Helper to parse the style from the iframe tag.
   *
   * @param string $tag
   *   The iframe tag.
   *
   * @return int|null
   *   Returns the style or NULL if not found.
   */
  protected function parseTagStyle($tag) {
    return $this->parseTagByPattern($tag, '/style="([^"]*)"/iSu');
  }

  /**
   * {@inheritdoc}
   */
  public function getCorrespondingProcessorPluginId() {
    return 'html_process_iframe';
  }

}
