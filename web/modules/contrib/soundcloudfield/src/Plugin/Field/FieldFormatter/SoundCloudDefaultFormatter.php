<?php

namespace Drupal\soundcloudfield\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'soundcloud_default' formatter.
 *
 * @FieldFormatter(
 *   id = "soundcloud_default",
 *   module = "soundcloudfield",
 *   label = @Translation("Default (HTML5 player)"),
 *   field_types = {
 *     "soundcloud"
 *   }
 * )
 */
class SoundCloudDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'soundcloud_player_type' => 'classic',
      'soundcloud_player_width' => SOUNDCLOUDFIELD_DEFAULT_WIDTH,
      'soundcloud_player_height' => SOUNDCLOUDFIELD_DEFAULT_HTML5_PLAYER_HEIGHT,
      'soundcloud_player_height_sets' => SOUNDCLOUDFIELD_DEFAULT_HTML5_PLAYER_HEIGHT_SETS,
      'soundcloud_player_visual_height' => SOUNDCLOUDFIELD_DEFAULT_VISUAL_PLAYER_HEIGHT,
      'soundcloud_player_autoplay' => '',
      'soundcloud_player_color' => 'ff7700',
      'soundcloud_player_hiderelated' => '',
      'soundcloud_player_showartwork' => '',
      'soundcloud_player_showcomments' => TRUE,
      'soundcloud_player_showplaycount' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['soundcloud_player_type'] = array(
      '#title' => $this->t('HTML5 player type'),
      '#description' => $this->t('Select which HTML5 player to use.'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('soundcloud_player_type'),
      '#options' => array(
        'classic' => 'Classic',
        'visual' => 'Visual Player (new)',
      ),
    );

    $elements['soundcloud_player_width'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Width'),
      '#size' => 4,
      '#default_value' => $this->getSetting('soundcloud_player_width'),
      '#description' => $this->t('Player width in percent. Default is @width.', array('@width' => SOUNDCLOUDFIELD_DEFAULT_WIDTH)),
    );

    $elements['soundcloud_player_height'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Height'),
      '#size' => 4,
      '#default_value' => $this->getSetting('soundcloud_player_height'),
      '#states' => array(
        'visible' => array(
          ':input[name*="soundcloud_player_type"]' => array('value' => 'classic'),
        ),
      ),
    );

    $elements['soundcloud_player_height_sets'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Height for sets'),
      '#size' => 4,
      '#default_value' => $this->getSetting('soundcloud_player_height_sets'),
      '#states' => array(
        'visible' => array(
          ':input[name*="soundcloud_player_type"]' => array('value' => 'classic'),
        ),
      ),
    );

    $elements['soundcloud_player_visual_height'] = array(
      '#type' => 'select',
      '#title' => $this->t('Height of the visual player'),
      '#size' => 4,
      '#default_value' => $this->getSetting('soundcloud_player_visual_height'),
      '#options' => array(
        300 => '300px',
        450 => '450px',
        600 => '600px',
      ),
      '#states' => array(
        'visible' => array(
          ':input[name*="soundcloud_player_type"]' => array('value' => 'visual'),
        ),
      ),
    );

    $elements['soundcloud_player_autoplay'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Play audio automatically when loaded (autoplay).'),
      '#default_value' => $this->getSetting('soundcloud_player_autoplay'),
    );

    $elements['soundcloud_player_color'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Player color.'),
      '#default_value' => $this->getSetting('soundcloud_player_color'),
      '#description' => $this->t('Player color in hexadecimal format. Default is ff7700. Turn on the jQuery Colorpicker module if available.'),
    );

    $elements['soundcloud_player_hiderelated'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Hide related tracks.'),
      '#default_value' => $this->getSetting('soundcloud_player_hiderelated'),
    );

    $elements['soundcloud_player_showartwork'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show artwork.'),
      '#default_value' => $this->getSetting('soundcloud_player_showartwork'),
    );

    $elements['soundcloud_player_showcomments'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show comments.'),
      '#default_value' => $this->getSetting('soundcloud_player_showcomments'),
    );

    $elements['soundcloud_player_showplaycount'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show play count.'),
      '#default_value' => $this->getSetting('soundcloud_player_showplaycount'),
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $settings = $this->getSettings();

    $summary[] = $this->t('Displays the SoundCloud player.');

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();
    $settings = $this->getSettings();

    // Get the "common" settings.
    $width = $this->getSetting('soundcloud_player_width');
    $autoplay = $this->getSetting('soundcloud_player_autoplay') ? 'true' : 'false';
    $showcomments = $this->getSetting('soundcloud_player_showcomments') ? 'true' : 'false';
    $showplaycount = $this->getSetting('soundcloud_player_showplaycount') ? 'true' : 'false';
    $showartwork = $this->getSetting('soundcloud_player_showartwork') ? 'true' : 'false';
    $color = $this->getSetting('soundcloud_player_color') ? $this->getSetting('soundcloud_player_color') : 'ff7700';

    //
    $oembed_endpoint = 'http://soundcloud.com/oembed';

    // Get 'HTML5 player'-specific settings.
    $html5_player_height = (empty($settings['html5_player']['html5_player_height']) ? SOUNDCLOUDFIELD_DEFAULT_HTML5_PLAYER_HEIGHT : $settings['html5_player']['html5_player_height']);
    $html5_player_height_sets = (empty($settings['html5_player']['html5_player_height_sets']) ? SOUNDCLOUDFIELD_DEFAULT_HTML5_PLAYER_HEIGHT_SETS : $settings['html5_player']['html5_player_height_sets']);
    $visual_player = ($this->getSetting('soundcloud_player_type') == 'visual') ? 'true' : 'false';

    foreach ($items as $delta => $item) {
      $output = '';
      $encoded_url = urlencode($item->url);

      // Set the proper height for this item.
      // - classic player: track default is 166px, set default is 450px.
      // - visual player: player height it's the same for tracks and sets.
      if ($visual_player == 'true') {
        $iframe_height = $settings['visual_player']['visual_player_height'];
      }
      else {
        $parsed_url = parse_url($item->url);
        $splitted_url = explode("/", $parsed_url['path']);
        // An artist page or a set or a track?
        $iframe_height = (!isset($splitted_url[2]) || $splitted_url[2] == 'sets') ? $html5_player_height_sets : $html5_player_height;
      }

      // Create the URL.
      $oembed_url = $oembed_endpoint . '?iframe=true&url=' . ($encoded_url);

      // curl get.
      $soundcloud_curl_get = _soundcloudfield_curl_get($oembed_url);

      if ($soundcloud_curl_get != ' ') {
        // Load in the oEmbed XML.
        $oembed = simplexml_load_string($soundcloud_curl_get);

        // Replace player default settings with our settings,
        // set player width and height first.
        $final_iframe = preg_replace('/(width=)"([^"]+)"/', 'width="' . $width . '%"', $oembed->html);
        $final_iframe = preg_replace('/(height=)"([^"]+)"/', 'height="' . $iframe_height . '"', $oembed->html);
        // Set autoplay.
        if (preg_match('/auto_play=(true|false)/', $final_iframe)) {
          $final_iframe = preg_replace('/auto_play=(true|false)/', 'auto_play=' . $autoplay, $final_iframe);
        }
        else {
          $final_iframe = preg_replace('/">/', '&auto_play=' . $autoplay . '">', $final_iframe);
        }
        // Show comments?
        if (preg_match('/show_comments=(true|false)/', $final_iframe)) {
          $final_iframe = preg_replace('/show_comments=(true|false)/', 'show_comments=' . $showcomments, $final_iframe);
        }
        else {
          $final_iframe = preg_replace('/">/', '&show_comments=' . $showcomments . '">', $final_iframe);
        }
        // Show playcount?
        if (preg_match('/show_playcount=(true|false)/', $final_iframe)) {
          $final_iframe = preg_replace('/show_playcount=(true|false)/', 'show_playcount=' . $showplaycount, $final_iframe);
        }
        else {
          $final_iframe = preg_replace('/">/', '&show_playcount=' . $showplaycount . '">', $final_iframe);
        }
        // Show artwork?
        if (preg_match('/show_artwork=(true|false)/', $final_iframe)) {
          $final_iframe = preg_replace('/show_artwork=(true|false)/', 'show_artwork=' . $showartwork, $final_iframe);
        }
        else {
          $final_iframe = preg_replace('/">/', '&show_artwork=' . $showartwork . '">', $final_iframe);
        }
        // Set player color.
        if (preg_match('/color=([a-zA-Z0-9]{6})/', $final_iframe)) {
          $final_iframe = preg_replace('/color=([a-zA-Z0-9]{6})/', 'color=' . $color, $final_iframe);
        }
        else {
          $final_iframe = preg_replace('/">/', '&color=' . $color . '">', $final_iframe);
        }
        // Set HTML5 player type based on formatter: classic/visual player.
        if (preg_match('/visual=(true|false)/', $final_iframe)) {
          $final_iframe = preg_replace('/visual=(true|false)/', 'visual=' . $visual_player, $final_iframe);
        }
        else {
          $final_iframe = preg_replace('/">/', '&visual=' . $visual_player . '">', $final_iframe);
        }
        // Final output. Use '$oembed->html' for original embed code.
        $output = html_entity_decode($final_iframe);
      }
      else {
        $output = $this->t('The SoundCloud content at <a href=":url">:url</a> is not available, or it is set to private.', [':url' => $item->url]);
      }

      // Extract field item attributes for the theme function, and unset them
      // from the $item so that the field template does not re-render them.
      $item_attributes = $item->_attributes;
      unset($item->_attributes);

      // Render each element as markup.
      $elements[$delta] = array(
        '#markup' => $output,
        '#allowed_tags' => ['iframe'],
      );

//      $elements[$delta] = array(
//        '#markup' => $item->value,
//        '#markup' => $item->processed,
//      );
    }

    return $elements;
  }

  protected function renderEmbedCode($track_id, $width, $height, $autoplay) {
    return [
      '#type' => 'html_tag',
      '#tag' => 'iframe',
      '#attributes' => [
        'width' => $width,
        'height' => $height,
        'frameborder' => '0',
        'allowfullscreen' => 'allowfullscreen',
        'src' => sprintf('https://w.soundcloud.com/player/%s?autoplay=%s', $track_id, $autoplay),
      ],
    ];
  }

}
