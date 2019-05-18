<?php
/**
 * @file
 * Contains \Drupal\facebook_album\Plugin\Block\FacebookAlbumBlock.
 */

namespace Drupal\facebook_album\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a facebook album block block type.
 *
 * @Block(
 *   id = "facebook_album",
 *   admin_label = @Translation("Facebook Album"),
 *   category = @Translation("Facebook"),
 * )
 */
class FacebookAlbumBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {

    $config = $this->getConfiguration();

    $form['page_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Facebook Page ID'),
      '#default_value' => $config['page_id'],
      '#required' => TRUE,
      '#description' => $this->t('The page ID of the page you want to pull the albums from. For example, if your page is https://facebook.com/acromediainc, you would enter acromediainc.'),
    ];

    $form['display'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Display settings'),
    ];
    $form['display']['album_visibility'] = [
      '#type' => 'select',
      '#title' => $this->t('Album Visibility'),
      '#default_value' => $config['album_visibility'],
      '#options' => [
        0 => $this->t('Exclude the listed albums'),
        1 => $this->t('Only show the specified albums'),
      ],
    ];
    $form['display']['albums'] = [
      '#type' => 'textarea',
      '#default_value' => implode("\n", $config['albums']),
      '#description' => $this->t('Leave blank to show all albums. Specify albums by using their album IDs. Enter one ID per line.'),
    ];
    $form['display']['album_limit'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Album Limit'),
      '#default_value' => $config['album_limit'],
      '#description' => $this->t('Leave blank or set to 0 if you want to load all albums'),
    ];
    $form['display']['show_title'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Album Title'),
      '#default_value' => $config['show_title'],
    ];
    $form['display']['show_description'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Album Description'),
      '#default_value' => $config['show_description'],
    ];
    $form['display']['show_location'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Album Location'),
      '#default_value' => $config['show_location'],
    ];
    $form['display']['album_thumb_width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Album Thumbnail Width'),
      '#default_value' => $config['album_thumb_width'],
      '#required' => TRUE,
    ];
    $form['display']['album_thumb_height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Album Thumbnail Height'),
      '#default_value' => $config['album_thumb_height'],
      '#required' => TRUE,
    ];
    $form['display']['thumb_width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Photo Thumbnail Width'),
      '#default_value' => $config['thumb_width'],
      '#required' => TRUE,
    ];
    $form['display']['thumb_height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Photo Thumbnail Height'),
      '#default_value' => $config['thumb_height'],
      '#required' => TRUE,
    ];
    $form['display']['colorbox'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Colorbox Options'),
      '#default_value' => $config['colorbox'],
      '#description' => $this->t('Specify any additional Colorbox options here. i.e. "transition:\'elastic\', speed:350"'),
    ];

    return $form;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    // Ensure numeric, non-zero values
    foreach ($values['display'] as $display_key => $display_values) {
      if (strpos($display_key, '_width') !== FALSE || strpos($display_key, '_height') !== FALSE) {
        if (!is_numeric($display_values) || $display_values <= 0) {
          $form_state->setErrorByName('display][' . $display_key, $this->t("Invalid value"));
        }
      }
    }
    parent::blockValidate($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('page_id', $form_state->getValue('page_id'));

    // Get the display values
    $fieldset_values = $form_state->getValue('display');

    // Save Albums as an array
    $albums = explode("\r\n", $fieldset_values['albums']);
    $this->setConfigurationValue('albums', $albums);

    // Save the rest
    foreach ($fieldset_values as $key => $value) {
      if ($key != 'albums') {
        $this->setConfigurationValue($key, $value);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Due to not having access to any entity info, we're storing settings via a hash
    // @TODO: Find a better way for this
    $config = $this->getConfiguration();
    $hash = hash_hmac('md5', serialize($config), 'facebook_album');

    // Output the wrappers
    $content = [];
    $content['#attributes']['class'] = [
      'block',
      'block-facebook-album'
    ];

    $content[] = [
      '#theme_wrappers' => [
        'container' => [
          '#attributes' => [
            'id' => 'fba-' . $hash,
            'class' => ['facebook-album-container'],
          ],
        ],
      ],
      'header' => [
        '#markup' => '<div class="fb-album-header"></div>',
      ],
      'albums' => [
        '#markup' => '<div class="facebook-album-images-container"></div>',
      ],
      'loading' => [
        '#markup' => '<div class="fb-loading-icon"></div>',
      ]
    ];

    // Add the library and settings
    $content['#attached'] = [
      'library' => [
        'facebook_album/facebook_album',
      ],
      'drupalSettings' => [
        'facebook_album' => [
          'fba-' . $hash => $config,
        ],
      ],
    ];

    return $content;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'page_id' => '',
      'album_visibility' => 0,
      'albums' => [],
      'album_limit' => 0,
      'show_title' => 1,
      'show_description' => 1,
      'show_location' => 1,
      'album_thumb_width' => 365,
      'album_thumb_height' => 250,
      'thumb_width' => 160,
      'thumb_height' => 120,
      'colorbox' => 'maxWidth: "95%", maxHeight: "95%"',
    ];
  }

}
