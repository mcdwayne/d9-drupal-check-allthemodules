<?php

/**
 * @file
 * Contains \Drupal\shortener\Plugin\Filter\UrlShortener.
 */

namespace Drupal\shortener\Plugin\Filter;

use Drupal\filter\Annotation\Filter;
use Drupal\Core\Annotation\Translation;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a filter to limit allowed HTML tags.
 *
 * @Filter(
 *   id = "url_shortener",
 *   title = @Translation("URL shortener"),
 *   description = @Translation("Replaces URLs with a shortened version."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   settings = {
 *     "shortener_url_behavior" = "short",
 *     "shortener_url_length" = 72
 *   },
 *   weight = -20
 * )
 */
class UrlShortener extends FilterBase {
  /**
   * Builds the settings form for the input filter.
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['shortener_url_behavior'] = array(
      '#type' => 'radios',
      '#title' => t('Behavior'),
      '#default_value' => $this->settings['shortener_url_behavior'],
      '#options' => array(
        'short' => t('Display the shortened URL by default, and add an "(expand)"/"(shorten)" link'),
        'strict' => t('Display the shortened URL by default, and do not allow expanding it'),
        'long' => t('Display the full URL by default, and add a "(shorten)"/"(expand)" link'),
      ),
    );
    $form['shortener_url_length'] = array(
      '#type' => 'textfield',
      '#title' => t('Maximum link text length'),
      '#default_value' => $this->settings['shortener_url_length'],
      '#maxlength' => 4,
      '#description' => t('URLs longer than this number of characters will be truncated to prevent long strings that break formatting. The link itself will be retained; just the text portion of the link will be truncated.'),
    );
    return $form;
  }

  /**
   * {@inheritdocs}
   */
  public function process($text, $langcode) {

    $length = $this->settings['shortener_url_length'];
    // Pass length to regexp callback.
    _filter_url_trim('', $length);
    // Pass behavior to regexp callback.
    $this->_shortener_url_behavior(NULL, FALSE, $this->settings['shortener_url_behavior'], $length);

    $text = ' ' . $text . ' ';

    // Match absolute URLs.
    $text = preg_replace_callback("`(<p>|<li>|<br\s*/?>|[ \n\r\t\(])((http://|https://)([a-zA-Z0-9@:%_+*~#?&=.,/;-]*[a-zA-Z0-9@:%_+*~#&=/;-]))([.,?!]*?)(?=(</p>|</li>|<br\s*/?>|[ \n\r\t\)]))`i", array(get_class($this), '_shortener_url_behavior'), $text);

    // Match www domains/addresses.
    $text = preg_replace_callback("`(<p>|<li>|[ \n\r\t\(])(www\.[a-zA-Z0-9@:%_+*~#?&=.,/;-]*[a-zA-Z0-9@:%_+~#\&=/;-])([.,?!]*?)(?=(</p>|</li>|<br\s*/?>|[ \n\r\t\)]))`i", array(get_class($this), '_shortener_url_parse_partial_links'), $text);
    $text = substr($text, 1, -1);

    // return new FilterProcessResult($text);
    $result = new FilterProcessResult($text);
    $result->setAttachments(array(
      'library' => array('shortener/shortener'),
    ));

    return $result;
  }

  /**
   * Processes matches on partial URLs and returns the "fixed" version.
   */
  function _shortener_url_parse_partial_links($match) {
    return _shortener_url_behavior($match, TRUE);
  }

  /**
   * Determines the link caption based on the filter behavior setting.
   */
  function _shortener_url_behavior($match, $partial = FALSE, $behavior = NULL, $max_length = NULL) {
    static $_behavior;
    if ($behavior !== NULL) {
      $_behavior = $behavior;
    }
    static $_max_length;
    if ($max_length !== NULL) {
      $_max_length = $max_length;
    }

    if (!empty($match)) {
      $match[2] = \Drupal\Component\Utility\Html::decodeEntities($match[2]);
      $caption = '';
      $href = $match[2];
      $title = check_url($match[2]);
      if ($_behavior == 'short' || $_behavior == 'strict') {
        $caption = shorten_url($match[2]);
        $href = $caption;
      }
      else {
        $caption = \Drupal\Component\Utility\Html::escape(_filter_url_trim($match[2]));
        if ($partial) {
          $href = 'http://' . check_url($match[2]);
        }
        $title = shorten_url($match[2]);
      }
      return $match[1] . '<a href="' . $href . '" title="' . $title . '" class="shortener-length-' . $_max_length . ' shortener-link shortener-' . $_behavior . '">' . $caption . '</a>' . $match[$partial ? 3 : 5];
    }
    return '';
  }
}
