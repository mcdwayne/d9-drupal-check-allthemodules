<?php
namespace Drupal\mediawiki_api\Plugin\Filter;

use Drupal\Component\Utility\UrlHelper;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * @Filter(
 *   id = "filter_mediawiki",
 *   title = @Translation("MediaWiki API Filter"),
 *   description = @Translation("Users can use MediaWiki code formats. MediaWiki syntax is automatically recognized."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class FilterMediaWiki extends FilterBase {
  /**
   * Performs the filter processing.
   *
   * @param string $text
   *   The text string to be filtered.
   * @param string $langcode
   *   The language code of the text to be filtered.
   *
   * @return \Drupal\filter\FilterProcessResult
   *   The filtered text, wrapped in a FilterProcessResult object, and possibly
   *   with associated assets, cacheability metadata and placeholders.
   *
   * @see \Drupal\filter\FilterProcessResult
   */
  public function process($text, $langcode) {
    // Check URLs
    $pattern = '/\[\[(.+)\]\]/mU';
    preg_match_all($pattern, $text, $matches);

    $search = array();
    $replace = array();
    foreach ($matches[1] as $key => $title) {
      if (!isset($this->settings['mediawiki_api_article_path']) && substr_count($title, 'File:') > 0) {
        $search[] = $title;
        $replace[] = $title . '|link=';
        continue;
      }
      $nodes = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->loadByProperties(['title' => $title]);
      if ($node = reset($nodes)) {
        $alias = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $node->id());
        $search[] = '[' . $title . ']';
        $replace[] = \Drupal::request()->getSchemeAndHttpHost() . $alias . ' ' . $title;
      }
    }
    $text = str_replace($search, $replace, $text);

    // Query
    $query = array(
      'action' => 'parse',
      'format' => 'php',
      'text' => $text,
    );

    // Init curl request
    $ch = curl_init($this->settings['mediawiki_api_url']);

    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded;charset=UTF-8', 'Expect:'));
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS,  UrlHelper::buildQuery($query));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

    // Execute
    $res = curl_exec($ch);

    curl_close($ch);

    // Check the result is valid
    if ($res) {
      $data = unserialize($res);

      // Remove section edit link.
      $ptext = preg_replace('|<span class="mw-editsection">.*?</span>|U', '', $data['parse']['text']['*']);

      // Fix local links to images.
      if (isset($this->settings['mediawiki_api_article_path'])) {
        $search = str_replace('$1','', $this->settings['mediawiki_api_article_path']);
        $replace = str_replace('/api.php','', $this->settings['mediawiki_api_url']) . $search;
        $ptext = str_replace('<a href="' . $search, '<a href="' . $replace, $ptext);
      }
      return new FilterProcessResult($ptext);
    }
    else {
      return new FilterProcessResult($text);
    }
  }

  /**
   * Return settings form.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['mediawiki_api_url'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('MediaWiki API URL'),
      '#default_value' => isset($this->settings['mediawiki_api_url']) ? $this->settings['mediawiki_api_url'] : '',
      '#description' => $this->t('The system will call this URL to generate the HTML code.'),
    );
    $form['mediawiki_api_article_path'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('MediaWiki $wgArticlePath'),
      '#default_value' => isset($this->settings['mediawiki_api_article_path']) ? $this->settings['mediawiki_api_article_path'] : '',
      '#description' => $this->t('Enter $wgArticlePath from MediaWiki LocalSettings.php to enable pages/image linking.'),
    );
    return $form;
  }

  /**
   * Return tips for filter.
   *
   * @param bool $long
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string|void|null
   */
  public function tips($long = FALSE) {
    if ($long) {
      return $this->t('MediaWiki syntax is automatically recognized.
Further information about <a href="http://www.mediawiki.org/wiki/Help:Formatting">MediaWiki syntax reference</a>.');
    }
    else {
      return $this->t('MediaWiki syntax is automatically recognized.');
    }
  }
}