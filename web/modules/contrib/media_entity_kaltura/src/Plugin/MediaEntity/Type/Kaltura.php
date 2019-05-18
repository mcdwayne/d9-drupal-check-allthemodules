<?php

namespace Drupal\media_entity_kaltura\Plugin\MediaEntity\Type;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media_entity\MediaInterface;
use Drupal\media_entity\MediaTypeBase;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use DOMDocument;

/**
 * Provides media type plugin for kaltura.
 *
 * @MediaType(
 *   id = "kaltura",
 *   label = @Translation("Kaltura"),
 *   description = @Translation("Provides business logic and metadata for Kaltura.")
 * )
 */
class Kaltura extends MediaTypeBase {

  /**
   * @var array
   */
  protected $kaltura;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * @inheritDoc
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, ConfigFactoryInterface $configFactory, ClientInterface $httpClient) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $configFactory->get('media_entity.settings'));
    $this->configFactory = $configFactory;
    $this->httpClient = $httpClient;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('config.factory'),
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'source_url_field' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = [];

    $options = ['' => $this->t('Select')];
    $bundle = $form_state->getFormObject()->getEntity();
    $allowed_field_types = ['string', 'string_long', 'link'];
    foreach ($this->entityFieldManager->getFieldDefinitions('media', $bundle->id()) as $field_name => $field) {
      if (in_array($field->getType(), $allowed_field_types) && !$field->getFieldStorageDefinition()->isBaseField()) {
        $options[$field_name] = $field->getLabel();
      }
    }

    $disabled = !count($options);
    if ($disabled) {
      $options = ['' => $this->t('Add fields to the media bundle')];
    }

    $form['source_url_field'] = [
      '#type' => 'select',
      '#title' => t('Kaltura URL source field'),
      '#description' => t('Select the field on the media entity that stores kaltura URL.'),
      '#default_value' => isset($this->configuration['source_url_field']) ? $this->configuration['source_url_field'] : '',
      '#options' => $options,
      '#disabled' => $disabled,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function providedFields() {
    return [
      'entry_id' => $this->t('The entry id'),
      'flash_vars' => $this->t('Flash variables'),
      'partner_id' => $this->t('The partner id'),
      'ui_conf_id' => $this->t('The conf id'),
      'thumbnail_uri' => $this->t('URI of the thumbnail'),
    ];
  }

  /**
   * @inheritDoc
   */
  public function getField(MediaInterface $media, $name) {
    if (($url = $this->getMediaUrl($media)) && ($data = $this->getData($url)) && isset($data['src'])) {
      $data['src'] = urldecode($data['src']);
      switch ($name) {
        case 'thumbnail_uri':
          if (isset($data['thumbnail_url'])) {
            $destination = $this->configFactory->get('media_entity_kaltura.settings')->get('thumbnail_destination');
            $hash = md5($data['thumbnail_url']);
            $local_uri = $destination . '/' . $hash;

            // Save the file if it does not exist.
            if (!file_exists($local_uri)) {
              file_prepare_directory($destination, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);

              $image = file_get_contents($data['thumbnail_url']);
              file_unmanaged_save_data($image, $local_uri, FILE_EXISTS_REPLACE);

              return $local_uri;
            }
          }
          return FALSE;

        case 'entry_id':
          // Extract the id from the src.
          preg_match('/entry_id=([a-zA-Z0-9_]*)/', $data['src'], $matches);
          if (!count($matches)) {
            return FALSE;
          }
          return $matches[1];

        case 'partner_id':
          // Extract the id from the src.
          preg_match('/\/p\/([a-zA-Z0-9_]*)/', $data['src'], $matches);
          if (!count($matches)) {
            return FALSE;
          }
          return $matches[1];

        case 'ui_conf_id':
          // Extract the id from the src.
          preg_match('/\/uiconf_id\/([a-zA-Z0-9_]*)/', $data['src'], $matches);
          if (!count($matches)) {
            return FALSE;
          }
          return $matches[1];

        case 'flash_vars':
          preg_match_all('/flashvars\[([A-Za-z0-9_\-\.]+)\]=([A-Za-z0-9_\-\.]+)/', $data['src'], $matches);
          if (!count($matches)) {
            return FALSE;
          }
          $vars = [];
          foreach ($matches[0] as $i => $m) {
            $vars[$matches[1][$i]] = $matches[2][$i];
          }
          return $vars;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function thumbnail(MediaInterface $media) {
    if ($thumbnail_image = $this->getField($media, 'thumbnail_uri')) {
      return $thumbnail_image;
    }

    return $this->getDefaultThumbnail();
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultThumbnail() {
    return $this->config->get('icon_base') . '/kaltura.png';
  }

  /**
   * Returns the episode id from the source_url_field.
   *
   * @param \Drupal\media_entity\MediaInterface $media
   *   The media entity.
   *
   * @return string|bool
   *   The episode if from the source_url_field if found. False otherwise.
   */
  protected function getMediaUrl(MediaInterface $media) {
    if (isset($this->configuration['source_url_field'])) {
      $source_url_field = $this->configuration['source_url_field'];
      if ($media->hasField($source_url_field)) {
        if (!empty($media->{$source_url_field}->first())) {
          $property_name = $media->{$source_url_field}->first()
            ->mainPropertyName();
          return $media->{$source_url_field}->{$property_name};
        }
      }
    }
    return FALSE;
  }

  /**
   * Returns oembed data for a Kaltura url.
   * supports standard frontend and preview (for playlist vids)
   *
   * @param string $url
   *   The kaltura Url.
   *
   * @return array
   *   An array of embed data.
   */
  protected function getData($url) {
    $cid = 'media_embed_kaltura:' . md5($url);

    if ($cache = \Drupal::cache()->get($cid)) {
      $kaltura = $cache->data;
    }
    else {
      $kaltura = [];
      $response = $this->httpClient->get($url);
      $data = (string) $response->getBody();

      $dom = new DOMDocument();
      // Kaltura pages have broken markup...
      $internalErrors = libxml_use_internal_errors(TRUE);
      $dom->loadHTML($data);
      libxml_use_internal_errors($internalErrors);

      // Search for the embed.
      $nodes = $dom->getElementsByTagName('meta');
      foreach ($nodes as $node) {
        $prop = $node->getAttribute('property');
        if ($prop == 'og:video:url') {
          $kaltura['src'] = $node->getAttribute('content');
          break;
        }
      }
      // Thumbnail.
      $nodes = $dom->getElementsByTagName('meta');
      foreach ($nodes as $node) {
        $property = $node->getAttribute('property');
        if ($property == 'og:image') {
          $kaltura['thumbnail_url'] = $node->getAttribute('content');
          $kaltura['thumbnail_url'] = str_replace("http://cdnapi", "https://cdnapisec", $this->kaltura['thumbnail_url']);
          break;
        }
      }

      // haven't found a src? search for a codegenerator.
      if (!isset($kaltura['src'])) {
        $nodes = $dom->getElementsByTagName('script');
        foreach ($nodes as $node) {
          $inner = $node->nodeValue;
          if (preg_match_all('/kEmbedCodeGenerator\((.*)\)\.getCode/', $inner, $matches)) {
            $json = $matches[1][0];
            $json_data = json_decode($json, TRUE);
            $kaltura['src'] = sprintf("https://cdnapisec.kaltura.com/p/%s/sp/%s00/embedIframeJs/uiconf_id/%s/partner_id/%s?iframeembed=true&playerId=kaltura_player",
              $json_data['partnerId'], $json_data['partnerId'], $json_data['uiConfId'], $json_data['partnerId']);
            if (isset($json_data['flashVars'])) {
              $params = [];
              foreach ($json_data['flashVars'] as $k => $v) {
                $params['flashvars[' . $k . ']'] = $v;
              }
              $kaltura['src'] .= '&' . http_build_query($params);
            }
          }
        }
      }

      \Drupal::cache()->set($cid, $kaltura);
    }

    return $kaltura;
  }

}
