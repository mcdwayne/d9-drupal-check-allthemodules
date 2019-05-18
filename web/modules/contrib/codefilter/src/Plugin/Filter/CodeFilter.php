<?php

namespace Drupal\codefilter\Plugin\Filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Text filter for highlighting PHP source code.
 *
 * @Filter(
 *   id = "codefilter",
 *   module = "codefilter",
 *   title = @Translation("Code filter"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   settings = {
 *     "nowrap_expand" = 0
 *   }
 * )
 */
class CodeFilter extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $form['nowrap_expand'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Expand code boxes on hover.'),
      '#description' => $this->t('By default, code boxes inherit text wrapping from the active theme. With this setting, code boxes will not wrap, but will expand to full width on hover (with Javascript).'),
      '#default_value' => $this->settings['nowrap_expand'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    if ($long) {
      return $this->t('To post pieces of code, surround them with &lt;code&gt;...&lt;/code&gt; tags. For PHP code, you can use &lt;?php ... ?&gt;, which will also colour it based on syntax.');
    }
    else {
      return $this->t('You may post code using &lt;code&gt;...&lt;/code&gt; (generic) or &lt;?php ... ?&gt; (highlighted PHP) tags.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    // Escape code tags to prevent other filters from acting on them.
    $text = preg_replace_callback('@<code>(.+?)</code>@s', [get_class($this), 'codeTagCallback'], $text);
    $text = preg_replace_callback('@[\[<](\?php)(.+?)(\?)[\]>]@s', [get_class($this), 'phpTagCallback'], $text);

    // Replace code.
    $text = preg_replace_callback('@\[codefilter_code\](.+?)\[/codefilter_code\]@s', [get_class($this), 'processCodeCallback'], $text);
    $text = preg_replace_callback('@\[codefilter_php\](.+?)\[/codefilter_php\]@s', [get_class($this), 'processPHPCallback'], $text);

    // A hack, so we can conditionally nowrap based on filter settings.
    // @todo Refactor how replacements are done so we can do this more cleanly.
    if ($this->settings['nowrap_expand']) {
      $text = str_replace('class="codeblock"', 'class="codeblock nowrap-expand"', $text);
    }
    return new FilterProcessResult($text);
  }

  /**
   * Callback to escape content of <code> tags.
   *
   * @param array $matches
   *   An array of matches passed by preg_replace_callback().
   *
   * @return string
   *   A formatted string.
   */
  public static function codeTagCallback(array $matches) {
    return self::escape($matches[1], 'code');
  }

  /**
   * Callback to escape content of <?php ?>, [?php ?], <% %>, and [% %] tags.
   *
   * @param array $matches
   *   An array of matches passed by preg_replace_callback().
   *
   * @return string
   *   A formatted string.
   */
  public static function phpTagCallback(array $matches) {
    return self::escape($matches[2], 'php');
  }

  /**
   * Callback to replace content of the <code> elements.
   *
   * @param array $matches
   *   An array of matches passed by preg_replace_callback().
   *
   * @return string
   *   A formatted string.
   */
  public static function processCodeCallback(array $matches) {
    return self::processCode($matches[1]);
  }

  /**
   * Callback to replace content of the <?php ?> elements.
   *
   * @param array $matches
   *   An array of matches passed by preg_replace_callback().
   *
   * @return string
   *   A formatted string.
   */
  public static function processPHPCallback(array $matches) {
    return self::processPHP($matches[1]);
  }

  /**
   * Escape code blocks.
   *
   * @param string $text
   *   The string to escape.
   * @param string $type
   *   The type of code block, either 'code' or 'php'.
   *
   * @return string
   *   The escaped string.
   */
  public static function escape($text, $type = 'code') {
    // Note, pay attention to odd preg_replace-with-/e behaviour on slashes.
    $text = Html::escape(str_replace('\"', '"', $text));

    // Protect newlines from line break converter.
    $text = str_replace(["\r", "\n"], ['', '&#10;'], $text);

    // Add codefilter escape tags.
    $text = "[codefilter_$type]{$text}[/codefilter_$type]";

    return $text;
  }

  /**
   * Processes chunks of escaped code into HTML.
   */
  public static function processCode($text) {
    // Undo linebreak escaping.
    $text = str_replace('&#10;', "\n", $text);
    // Inline or block level piece?
    $multiline = strpos($text, "\n") !== FALSE;
    // Note, pay attention to odd preg_replace-with-/e behaviour on slashes.
    $text = preg_replace("/^\n/", '', preg_replace('@</?(br|p)\s*/?>@', '', str_replace('\"', '"', $text)));
    // Trim leading and trailing linebreaks.
    $text = trim($text, "\n");
    // Escape newlines.
    $text = nl2br($text);

    // PHP code in regular code.
    $text = preg_replace_callback('/&lt;\?php.+?\?&gt;/s', [get_class(), 'processPHPInline'], $text);

    $text = '<code>' . self::fixSpaces(str_replace(' ', '&nbsp;', $text)) . '</code>';
    $text = $multiline ? '<div class="codeblock">' . $text . '</div>' : $text;
    // Remove newlines to avoid clashing with the linebreak filter.
    return str_replace("\n", '', $text);
  }

  /**
   * Helper function for processCode().
   */
  public static function processPHPInline($matches) {
    // Undo nl2br.
    $text = str_replace('<br />', '', $matches[0]);
    // Decode entities (the highlighter re-entifies) and highlight text.
    $text = highlight_string(Html::decodeEntities($text), 1);
    // Remove PHPs own added code tags.
    $text = str_replace(['<code>', '</code>', "\n"], ['', '', ''], $text);
    return $text;
  }

  /**
   * Processes chunks of escaped PHP code into HTML.
   */
  public static function processPHP($text) {
    // Note, pay attention to odd preg_replace-with-/e behaviour on slashes.
    // Undo possible linebreak filter conversion.
    $text = preg_replace('@</?(br|p)\s*/?>@', '', str_replace('\"', '"', $text));
    // Undo the escaping in the prepare step.
    $text = Html::decodeEntities($text);
    // Trim leading and trailing linebreaks.
    $text = trim($text, "\r\n");
    // Highlight as PHP.
    $text = '<div class="codeblock">' . highlight_string("<?php\n$text\n?>", 1) . '</div>';
    // Remove newlines to avoid clashing with the linebreak filter.
    $text = str_replace("\n", '', $text);
    return self::fixSpaces($text);
  }

  /**
   * Replace html space elements with literal space characters.
   *
   * @param string $text
   *   A string to fix spaces for.
   *
   * @return string
   *   A formatted string.
   */
  public static function fixSpaces($text) {
    $text = preg_replace('@&nbsp;(?!&nbsp;)@', ' ', $text);
    // A single space before text is ignored by browsers. If a single space
    // follows a break tag, replace it with a non-breaking space.
    $text = preg_replace('@<br /> ([^ ])@', '<br />&nbsp;$1', $text);
    return $text;
  }

}
