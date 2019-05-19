<?php 

namespace Drupal\soembed\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * @Filter(
 *   id = "filter_soembed",
 *   title = @Translation("Simple oEmbed filter"),
 *   description = @Translation("Embeds media for URL that supports oEmbed standard."),
 *   settings = {
 *   "soembed_maxwidth" = 500,
 *   "soembed_replace_inline" = FALSE,
 *   "soembed_providers" = "#https?://(www\.)?youtube.com/watch.*#i | http://www.youtube.com/oembed | true
#https?://youtu\.be/.*#i | http://www.youtube.com/oembed | true 
#https?://(www\.)?vimeo\.com/.*#i | http://vimeo.com/api/oembed.json | true
#http://(www\.)?hulu\.com/watch/.*#i | http://www.hulu.com/api/oembed.json | true 
#https?://(www\.)?twitter.com/.+?/status(es)?/.*#i | https://api.twitter.com/1/statuses/oembed.json | true 
#https?://(www\.)?instagram.com/p/.*#i | https://api.instagram.com/oembed | true
#https?://maps.google.com/maps.*#i | google-maps | LOCAL
#https?://docs.google.com/(document|spreadsheet)/.*#i | google-docs | LOCAL"
 *   },
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class SoEmbedFilter extends FilterBase {

  public function process($text, $langcode) {

    $lines = explode("\n", $text);

    if (!empty($this->settings['soembed_replace_inline'])) {
      // Match in-line urls. (First () in pattern is needed because the callback
      // expects the url in the second pair of parentheses).
      $lines = preg_replace_callback('#()(https?://[^\s<]+)#', array($this, 'embed'), $lines);
    }
    else {
      $lines = preg_replace_callback('#^(<p>)?(https?://\S+?)(</p>)?$#', array($this, 'embed'), $lines);
    }

    $text = implode("\n", $lines);

    return new FilterProcessResult($text);
  }


  /**
   * Callback function to process each URL
   */
  private function embed($match) {

    static $providers = [];

    if (empty($providers)) {
      $providers_string = $this->settings['soembed_providers'];
      $providers_line = explode("\n", $providers_string);
      foreach ($providers_line as $value) {
        $items = explode(" | ", $value);
        $key = array_shift($items);
        $providers[$key] = $items;
      }
    }

    $url = $match[2];

    foreach ($providers as $matchmask => $data) {
      list($providerurl, $regex) = $data;

      $regex = preg_replace('/\s+/', '', $regex);

      if ($regex == 'false') {
        $regex = false;
      }

      if (!$regex) {
        $matchmask = '#' . str_replace( '___wildcard___', '(.+)', preg_quote(str_replace('*', '___wildcard___', $matchmask), '#')) . '#i';
      }
      if (preg_match($matchmask, $url)) {
        $provider = $providerurl;
        break;
      }
    }

    if ($regex === 'LOCAL') {
      $output = $this->getContents($provider, $url);
    }
    elseif (!empty($provider)) {
      $client = \Drupal::httpClient();
      $response = '';

      try {
        $request = $client->get($provider . '?url=' . $url . '&format=json&maxwidth=' . $this->settings['soembed_maxwidth']);
        $response = $request->getBody();
      }

      catch (RequestException $e) {
        watchdog_exception('soembed', $e->getMessage());
      }
      
      if (!empty($response)) {
        $embed = json_decode($response);
        if (!empty($embed->html)) {
         $output = $embed->html;
        }
        elseif ($embed->type == 'photo') {
          $output = '<img src="' . $embed->url . '" title="' . $embed->title . '" style="width: 100%" />';
          $output = '<a href="' . $url . '">' . $output .'</a>';
        }
      }
    }

    $output = empty($output) ? $url : $output;

    if (count($match) > 3) {
      $output = $match[1] . $output . $match[3]; // Add <p> and </p> back.
    }

    return $output;
  }

  /**
   * Locally create HTML after pattern study for sites that don't support oEmbed.
   */
  private function getContents($provider, $url) {
    $width = variable_get('soembed_maxwidth', 0);

    switch ($provider) {
      case 'google-maps':
        //$url    = str_replace('&', '&amp;', $url); Though Google encodes ampersand, it seems to work without it.
        $height = (int)($width / 1.3);
        $embed  = "<iframe width='{$width}' height='{$height}' frameborder='0' scrolling='no' marginheight='0' marginwidth='0' src='{$url}&output=embed'></iframe><br /><small><a href='{$url}&source=embed' style='color:#0000FF;text-align:left'>View Larger Map</a></small>";
        break;
      case 'google-docs':
        $height = (int)($width * 1.5);
        $embed  = "<iframe width='{$width}' height='{$height}' frameborder='0' src='{$url}&widget=true'></iframe>";
        break;
      default:
        $embed = $url;
    }

    return $embed;
  }

  /**
   * Define settings for text filter.
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['soembed_providers'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Providers'),
      '#default_value' => $this->settings['soembed_providers'],
      '#description' => $this->t('A list of oEmbed providers. Add your own by adding a new line and using this pattern: [Url to match] | [oEmbed endpoint] | [Use regex (true or false)]'),
    );
    $form['soembed_maxwidth'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Maximum width of media embed'),
      '#default_value' => $this->settings['soembed_maxwidth'],
      '#description' => $this->t('Set the maximum width of an embedded media. The unit is in pixels, but only put a number in the textbox.'),
    );
    $form['soembed_replace_inline'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Replace in-line URLs'),
      '#default_value' => $this->settings['soembed_replace_inline'],
      '#description' => $this->t('If this option is checked, the filter will recognize URLs even when they are not on their own lines.'),
    );
    return $form;
  }

}

