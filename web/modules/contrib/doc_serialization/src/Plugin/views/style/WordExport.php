<?php

namespace Drupal\doc_serialization\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\rest\Plugin\views\style\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * A style plugin for Word export views.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "word_export",
 *   title = @Translation("Word export"),
 *   help = @Translation("Configurable row output for Word exports."),
 *   display_types = {"data"}
 * )
 */
class WordExport extends Serializer {

  /**
   * Constructs a Plugin object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\Serializer\SerializerInterface $serializer
   *   The serializer for the plugin instance.
   * @param array $serializer_formats
   *   The serializer formats for the plugin instance.
   * @param array $serializer_format_providers
   *   The serializer format providers for the plugin instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SerializerInterface $serializer, array $serializer_formats, array $serializer_format_providers) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer, $serializer_formats, $serializer_format_providers);

    $this->formats = ['docx'];
    $this->formatProviders = ['docx' => 'doc_serialization'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    switch ($form_state->get('section')) {
      case 'style_options':
        // Change format to radios instead, since multiple formats here do not
        // make sense as they do for REST exports.
        $form['formats']['#type'] = 'radios';
        $form['formats']['#default_value'] = reset($this->options['formats']);

        // Remove now confusing description.
        unset($form['formats']['#description']);

        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    // Transform the formats back into an array.
    $format = $form_state->getValue(['style_options', 'formats']);
    $form_state->setValue(['style_options', 'formats'], [$format => $format]);

    parent::submitOptionsForm($form, $form_state);
  }

}
