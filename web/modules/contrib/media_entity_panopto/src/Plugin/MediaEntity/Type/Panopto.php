<?php

namespace Drupal\media_entity_panopto\Plugin\MediaEntity\Type;

use Drupal\Core\Form\FormStateInterface;
use Drupal\media_entity\MediaInterface;
use Drupal\media_entity\MediaTypeBase;
use Drupal\media_entity_panopto\PanoptoMetaData;

/**
 * Provides media type plugin for Panopto.
 *
 * @MediaType(
 *   id = "panopto",
 *   label = @Translation("Panopto"),
 *   description = @Translation("Provides media type plugin for Panopto.")
 * )
 *
 */
class Panopto extends MediaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'source_field' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = [];

    $options = [];
    $bundle = $form_state->getFormObject()->getEntity();
    $allowed_field_types = ['string', 'string_long', 'link'];
    foreach ($this->entityFieldManager->getFieldDefinitions('media', $bundle->id()) as $field_name => $field) {
      if (in_array($field->getType(), $allowed_field_types) && !$field->getFieldStorageDefinition()->isBaseField()) {
        $options[$field_name] = $field->getLabel();
      }
    }

    $form['source_field'] = [
      '#type' => 'select',
      '#title' => t('Field with source information'),
      '#description' => t('Field on media entity that stores Video URL. You can create a bundle without selecting a value for this dropdown initially. This dropdown can be populated after adding fields to the bundle.'),
      '#default_value' => empty($this->configuration['source_field']) ? NULL : $this->configuration['source_field'],
      '#options' => $options,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function providedFields() {
    return [
      'name',
    ];
  }

  /**
   * Returns the meta data for the Panopto video.
   *
   * @param string $url
   *   The URL to the panopto video.
   *
   * @return bool|array
   *   FALSE if there was a problem retrieving the oEmbed data, otherwise
   *   an array of the data is returned.
   */
  protected function oEmbed($url) {
    
    $video_details = $this->parsePanoptoUrl($url);
    $meta_data_obj = new PanoptoMetaData($video_details['video_id'], $video_details['client_server']);
    try {
      $new_data = $meta_data_obj->media_panopto_get_meta_data();
    }
    catch (Exception $e) {
      $new_data = FALSE;
      \Drupal::logger('media_entity_panopto')->error($e->faultstring);
    }
    return $new_data;
  }

  /**
   * Runs preg_match on embed code/URL.
   *
   * @param MediaInterface $media
   *   Media object.
   *
   * @return string|false
   *   The Panopto url or FALSE if there is no field or it contains invalid
   *   data.
   */
  protected function getPanoptoUrl(MediaInterface $media) {
    if (isset($this->configuration['source_field'])) {
      $source_field = $this->configuration['source_field'];
      if ($media->hasField($source_field)) {
        $property_name = $media->{$source_field}->first()->mainPropertyName();
        return $media->{$source_field}->{$property_name};
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getField(MediaInterface $media, $name) {
    $content_url = $this->getPanoptoUrl($media);
    if ($content_url === FALSE) {
      return FALSE;
    }

    switch ($name) {
      case 'name':
        $data = $this->oEmbed($content_url);
        if ($data === FALSE) {
          return FALSE;
        }
        return $data->Name;

      case 'embed_url':
        return str_replace('Viewer.aspx?', 'Embed.aspx?', $content_url);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function thumbnail(MediaInterface $media) {
    return $this->getDefaultThumbnail();
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultThumbnail() {
    return $this->config->get('icon_base') . '/Panopto.png';
  }

  /**
   * Runs preg_match on embed code/URL.
   *
   * @param MediaInterface $media
   *   Media object.
   *
   * @return string|false
   *   The Panopto url or FALSE if there is no field or it contains invalid
   *   data.
   */
  protected function parsePanoptoUrl($url) {
    $matches = array();
    $patterns = array(
      '@.*//(.*)/Panopto/Pages/Viewer\.aspx\?id\=([^"\&]+)@i',
      '@.*//(.*)/Panopto/Pages/Embed\.aspx\?id\=([^"\&]+)@i',
    );
    foreach ($patterns as $pattern) {
      preg_match($pattern, $url, $matches);
      if (!empty($matches[1]) && !empty($matches[2])) {
        return array(
          'client_server' => $matches[1],
          'video_id' => $matches[2],
        );
      }
    }
    return FALSE;
  }

}
