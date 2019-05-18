<?php

namespace Drupal\media_entity_spotify\Plugin\Field\FieldFormatter;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Field\Annotation\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media_entity\MediaTypeInterface;
use Drupal\media_entity_spotify\Plugin\MediaEntity\Type\Spotify;

/**
 * Plugin implementation of the 'spotify_embed' formatter.
 *
 * @FieldFormatter(
 *   id = "spotify_embed",
 *   label = @Translation("Spotify embed"),
 *   field_types = {
 *     "link", "string", "string_long"
 *   }
 * )
 */
class SpotifyEmbedFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'playlist' => [
          'theme' => 'dark',
          'view' => 'list',
          'width' => '300px',
          'height' => '380px',
        ],
        'track' => [
          'theme' => 'dark',
          'view' => 'list',
          'width' => '300px',
          'height' => '80px',
        ],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $playlist_settings = $this->getSetting('playlist');
    $track_settings = $this->getSetting('track');

    $elements['playlist'] = [
      '#type' => 'fieldset',
      '#description' => $this->t('Settings for playlists and albums.'),
      '#title' => $this->t('Playlist/album'),
    ];

    $elements['playlist']['theme'] = [
      '#title' => $this->t('Theme'),
      '#type' => 'select',
      '#options' => $this->getThemes(),
      '#default_value' => $playlist_settings['theme'],
      '#description' => $this->t('The theme for the embedded player.'),
    ];

    $elements['playlist']['view'] = [
      '#title' => $this->t('View'),
      '#type' => 'select',
      '#options' => $this->getViewTypes(),
      '#default_value' => $playlist_settings['view'],
      '#description' => $this->t('The view for the embedded player.'),
    ];

    $elements['playlist']['width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Width'),
      '#default_value' => $playlist_settings['width'],
      '#min' => 1,
      '#required' => TRUE,
      '#description' => $this->t('Width of embedded player.'),
    ];

    $elements['playlist']['height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Height'),
      '#default_value' => $playlist_settings['height'],
      '#min' => 1,
      '#required' => TRUE,
      '#description' => $this->t('Height of embedded player.'),
    ];

    $elements['track'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Track'),
      '#description' => $this->t('Settings for tracks.'),
    ];

    $elements['track']['theme'] = [
      '#title' => $this->t('Theme'),
      '#type' => 'select',
      '#options' => $this->getThemes(),
      '#default_value' => $track_settings['theme'],
      '#description' => $this->t('The theme for the embedded player.'),
    ];

    $elements['track']['view'] = [
      '#title' => $this->t('View'),
      '#type' => 'select',
      '#options' => $this->getViewTypes(),
      '#default_value' => $track_settings['view'],
      '#description' => $this->t('The view for the embedded player.'),
      '#ajax' => [
        'callback' => [$this, 'updateTrackSizeSettings'],
        'wrapper' => 'track-height-wrapper',
      ]
    ];

    $elements['track']['width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Width'),
      '#default_value' => $track_settings['width'],
      '#min' => 1,
      '#required' => TRUE,
      '#description' => $this->t('Width of embedded player.'),
    ];

    $elements['track']['height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Height'),
      '#default_value' => $track_settings['height'],
      '#required' => TRUE,
      '#description' => $this->t('Height of embedded player.'),
      '#prefix' => '<div id="track-height-wrapper">',
      '#suffix' => '</div>',
    ];

    // Get default value for view.
    $values = $form_state->getValues();
    if (count($values)) {
      $field_name = $this->fieldDefinition->getName();
      $settings = $values['fields'][$field_name]['settings_edit_form']['settings'];
    }
    $view = isset($settings['track']['view']) ? $settings['track']['view'] : $track_settings['view'];

    // Set the track height to 80px for list view.
    if ($view == 'list') {
      $elements['track']['height']['#value'] = '80px';
      $elements['track']['height']['#attributes']['disabled'] = TRUE;
    }

    return $elements;
  }

  public function updateTrackSizeSettings(array &$form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    return $form['fields'][$field_name]['plugin']['settings_edit_form']['settings']['track']['height'];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $settings = $this->getSettings();
    $view_types = $this->getViewTypes();
    $themes = $this->getThemes();

    return [
      $this->t('Playlist/Album'),
      $this->t('Theme: @theme', ['@theme' => $themes[$settings['playlist']['theme']]]),
      $this->t('View: @view', ['@view' => $view_types[$settings['playlist']['view']]]),
      $this->t('Size: @width x @height', [
        '@width' => $settings['playlist']['width'],
        '@height' => $settings['playlist']['height'],
      ]),
      '',
      $this->t('Track'),
      $this->t('Theme: @theme', ['@theme' => $themes[$settings['track']['theme']]]),
      $this->t('View: @view', ['@view' => $view_types[$settings['track']['view']]]),
      $this->t('Size: @width x @height', [
        '@width' => $settings['track']['width'],
        '@height' => $settings['track']['height'],
      ]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    /** @var \Drupal\media_entity\MediaInterface $media_entity */
    $media_entity = $items->getEntity();

    $element = [];
    if (($media_type = $media_entity->getType()) && $media_type instanceof Spotify) {
      /** @var MediaTypeInterface $item */
      foreach ($items as $delta => $item) {
        if (($uri = $media_type->getField($media_entity, 'uri')) && ($type = $media_type->getField($media_entity, 'type'))) {

          // Get settings for this type.
          $settings = $this->getSetting($type);

          // Render a media_spotify_embed.
          $element[$delta] = [
            '#theme' => 'media_spotify_embed',
            '#uri' => $uri,
            '#width' => $settings['width'],
            '#height' => $settings['height'],
            '#player_theme' => $settings['theme'],
            '#view' => $settings['view'],
          ];
        }
      }
    }

    return $element;
  }

  /**
   * Returns an array of view types.
   *
   * @return array
   *  An array of view types.
   */
  protected function getViewTypes() {
    return [
      'list' => $this->t('List'),
      'coverart' => $this->t('Cover Art'),
    ];
  }

  /**
   * Returns an array of themes.
   *
   * @return array
   *  An array of themes.
   */
  protected function getThemes() {
    return [
      'dark' => $this->t('Dark'),
      'white' => $this->t('Light'),
    ];
  }
}
