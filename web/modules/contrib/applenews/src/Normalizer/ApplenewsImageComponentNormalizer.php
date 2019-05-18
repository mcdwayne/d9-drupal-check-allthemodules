<?php

namespace Drupal\applenews\Normalizer;

/**
 * Normalizer for "image" type com.
 */
class ApplenewsImageComponentNormalizer extends ApplenewsComponentNormalizerBase {

  protected $componentType = 'image';

  /**
   * {@inheritdoc}
   */
  public function normalize($data, $format = NULL, array $context = []) {
    $component_class = $this->getComponentClass($data['id']);
    $entity = $context['entity'];

    $field_name = $data['component_data']['URL']['field_name'];
    $context['field_property'] = $data['component_data']['URL']['field_property'];
    $field_value = $this->serializer->normalize($entity->get($field_name), $format, $context);
    $component = new $component_class($this->getUrl($field_value));

    $field_name = $data['component_data']['caption']['field_name'];
    $context['field_property'] = $data['component_data']['caption']['field_property'];
    $text = $this->serializer->normalize($entity->get($field_name), $format, $context);
    $component->setCaption($text);
    $component->setLayout($this->getComponentLayout($data['component_layout']));

    return $component;
  }

  /**
   * Gets image URL.
   *
   * @param array $file
   *   File array.
   *
   * @return null|string
   *   String URL.
   */
  protected function getUrl(array $file) {
    if (isset($file['uri'][0]['value'])) {
      return file_create_url($file['uri'][0]['value']);
    }
    return NULL;
  }

}
