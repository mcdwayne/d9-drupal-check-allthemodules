<?php

namespace Drupal\media_entity_d500px\Plugin\media\Source;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceBase;
use Drupal\media\MediaSourceFieldConstraintsInterface;
use Drupal\media_entity\MediaTypeException;

/**
 * Provides media type plugin for 500px.
 *
 * @MediaSource(
 *   id = "d500px",
 *   label = @Translation("500px"),
 *   description = @Translation("Provides business logic and metadata for 500px."),
 *   allowed_field_types = {"string_long", "string", "link"},
 *   default_thumbnail_filename = "500px.png"
 * )
 */
class D500px extends MediaSourceBase implements MediaSourceFieldConstraintsInterface  {

  /**
   * List of validation regular expressions.
   *
   * @var array
   */
  public static $validationRegexp = array(
    '@(?P<shortcode><div class=\'pixels-photo\'>\s*<p>\s*<img src=\'(?<thumbnail>https://drscdn.500px.org/photo/(?P<id>[0-9]+)/.*)\' alt=\'(.*)\'>\s*</p>\s*<a href=\'https://500px.com/photo/[0-9]+/[\w-]+\' alt=\'(.*)\'></a>\s*</div>\s*<script type=\'text/javascript\' src=\'https://500px.com/embed.js\'></script>)@i' => 'shortcode',
  );

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'use_500px_api' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['use_500px_api'] = [
      '#type' => 'select',
      '#title' => $this->t('Whether to use 500px api to fetch pics or not.'),
      '#description' => $this->t("In order to use 500px's API, you have to enable @link.", ['@link' => Link::fromTextAndUrl('D500px module', Url::fromUri('https://www.drupal.org/project/d500px'))->toString()]),
      '#default_value' => empty($this->configuration['use_500px_api']) ? 0 : $this->configuration['use_500px_api'],
      '#options' => [
        0 => $this->t('No'),
        1 => $this->t('Yes'),
      ],
      '#disabled' => TRUE,
    ];

    if (\Drupal::moduleHandler()->moduleExists('d500px')) {
      $form['use_500px_api']['#disabled'] = FALSE;
      $form['use_500px_api']['#description'] = $this->t("500px's API can be used to fetch some metadata which can then be stored in Drupal fields. In order to use 500px's API, you have to configure @link.", ['@link' => Link::createFromRoute('500px integration', 'd500px.settings')->toString()]);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    $attributes = array(
      'shortcode' => $this->t('500px shortcode'),
    );

    if ($this->configuration['use_500px_api']) {
      $attributes += array(
        'id' => $this->t('Picture ID'),
        'name' => $this->t('Picture name'),
        'description' => $this->t('Picture description'),
        'username' => $this->t('Author of the picture'),
        'camera' => $this->t('Name of the camera used for the picture'),
        'votes' => $this->t('Number of votes'),
      );
    }

    return $attributes;
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $attribute_name) {
    $matches = $this->matchRegexp($media);

    if (!$matches['shortcode']) {
      return FALSE;
    }

    if (!empty($matches[$attribute_name])) {
      return $matches[$attribute_name];
    }

    // Special case to download a thumbnail locally.
    if ($attribute_name == 'thumbnail_uri') {
      if (isset($matches['thumbnail'])) {
        $directory = $this->configFactory->get('media_entity_d500px.settings')->get('local_images');
        if (!file_exists($directory)) {
          file_prepare_directory($directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
        }

        $file_info = pathinfo($matches['thumbnail']);
        $local_uri = $this->configFactory->get('media_entity_d500px.settings')->get('local_images') . '/' . $file_info['filename'] . '.' . 'jpg';
        if (file_exists($local_uri)) {
          return $local_uri;
        }
        else {
          $image_data = file_get_contents($matches['thumbnail']);
          if ($image_data) {
            return file_unmanaged_save_data($image_data, $local_uri, FILE_EXISTS_REPLACE);
          }
        }
      }
    }

    // If we have auth settings return the other fields.
    if ($this->configuration['use_500px_api'] && $d500px = $this->fetchD500px($matches['id'])) {
      switch ($attribute_name) {
        case 'votes':
          if (isset($d500px->votes_count)) {
            return $d500px->votes_count;
          }
          return FALSE;

        default:
          if (isset($d500px->{$attribute_name})) {
            return $d500px->{$attribute_name};
          }
          return FALSE;
      }
    }

    return parent::getMetadata($media, $attribute_name);
  }

  /**
   * Runs preg_match on embed code.
   *
   * @param MediaInterface $media
   *   Media object.
   *
   * @return array|bool
   *   Array of preg matches or FALSE if no match.
   *
   * @see preg_match()
   */
  protected function matchRegexp(MediaInterface $media) {
    $matches = array();
    if (isset($this->configuration['source_field'])) {
      $source_field = $this->configuration['source_field'];
      if ($media->hasField($source_field)) {
        $property_name = $media->{$source_field}->first()->mainPropertyName();
        foreach (static::$validationRegexp as $pattern => $key) {
          if (preg_match($pattern, str_replace(["\r", "\n"],'', $media->{$source_field}->{$property_name}), $matches)) {
            return $matches;
          }
        }
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceFieldConstraints() {
    return ['D500pxEmbedCode' => []];
  }

  /**
   * Get a single pic using d500px service.
   *
   * We don't use dependency injection as we don't want strict dependency
   * with d500px module.
   *
   * @param string $id
   *   The pic ID.
   * @return array
   * @throws \Drupal\media_entity\MediaTypeException
   */
  protected function fetchD500px($id) {
    $d500pxintegration = \Drupal::service('d500px.d500pxintegration');
    $result = $d500pxintegration->requestD500px('photos/' . $id, ['image_size' => [100, 200]]);

    if ($result && isset($result->photo)) {
      return $result->photo;
    }
    else {
      throw new MediaTypeException(NULL, 'The media could not be retrieved.');
    }
  }

}
