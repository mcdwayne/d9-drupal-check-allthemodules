<?php

namespace Drupal\applenews\Normalizer;

/**
 * Class ApplenewsTextComponentNormalizer.
 *
 * @package Drupal\applenews\Normalizer
 */
class ApplenewsTextComponentNormalizer extends ApplenewsComponentNormalizerBase {

  /**
   * Component type.
   *
   * @var string
   */
  protected $componentType = 'text';

  /**
   * {@inheritdoc}
   */
  public function normalize($data, $format = NULL, array $context = []) {
    $component_class = $this->getComponentClass($data['id']);
    $entity = $context['entity'];

    $field_name = $data['component_data']['text']['field_name'];
    $context['field_property'] = $data['component_data']['text']['field_property'];
    $text = $this->serializer->normalize($entity->get($field_name), $format, $context);
    $component = new $component_class($text);

    $component->setFormat($data['component_data']['format']);

    $component->setLayout($this->getComponentLayout($data['component_layout']));

    return $component;
  }

}
