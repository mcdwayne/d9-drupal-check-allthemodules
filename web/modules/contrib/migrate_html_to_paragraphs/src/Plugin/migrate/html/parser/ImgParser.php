<?php

namespace Drupal\migrate_html_to_paragraphs\Plugin\migrate\html\parser;

/**
 * Migration HTML - img parser.
 *
 * @MigrateHtmlParserPlugin(
 *   id = "html_parser_img"
 * )
 */
class ImgParser extends HtmlTagParser {

  /**
   * {@inheritdoc}
   */
  protected function definePattern() {
    return '/<img[^>]*>/iSu';
  }

  /**
   * {@inheritdoc}
   */
  protected function parseTag($tag) {
    $data = [
      'type'  => 'img',
      'tag'   => $tag,
      'src'   => $this->parseTagSource($tag),
      'class' => $this->parseTagClass($tag),
      'alt'   => $this->parseTagAlt($tag),
      'title' => $this->parseTagTitle($tag),
    ];

    return $data;
  }

  /**
   * Helper to parse the src from the img tag.
   *
   * @param string $tag
   *   The img tag.
   *
   * @return string|null
   *   The parsed source (src).
   */
  protected function parseTagSource($tag) {
    return $this->parseTagByPattern($tag, '/src="([^"]*)"/iSu');
  }

  /**
   * Helper to parse the class from the img tag.
   *
   * @param string $tag
   *   The img tag.
   *
   * @return int|null
   *   Returns the class or NULL if not found.
   */
  protected function parseTagClass($tag) {
    return $this->parseTagByPattern($tag, '/class="([^"]*)"/iSu');
  }

  /**
   * Helper to parse the alt from the img tag.
   *
   * @param string $tag
   *   The img tag.
   *
   * @return int|null
   *   Returns the alt or NULL if not found.
   */
  protected function parseTagAlt($tag) {
    $alt = $this->parseTagByPattern($tag, '/alt="([^"]*)"/iSu');
    return $this->maximumLength($alt, 512);
  }

  /**
   * Helper to parse the title from the img tag.
   *
   * @param string $tag
   *   The img tag.
   *
   * @return int|null
   *   Returns the title or NULL if not found.
   */
  protected function parseTagTitle($tag) {
    $title = $this->parseTagByPattern($tag, '/title="([^"]*)"/iSu');
    return $this->maximumLength($title, 1024);
  }

  /**
   * This function limits a string to a given limit.
   *
   * @param string $string
   *   String to limit.
   * @param int $limit
   *   The maximum limit to be applied to the string.
   *
   * @return string
   *   The string, possibly limited to the given maximum limit.
   */
  private function maximumLength($string, $limit) {
    return substr($string, 0, $limit);
  }

  /**
   * {@inheritdoc}
   */
  public function getCorrespondingProcessorPluginId() {
    return 'html_process_img';
  }

}
