<?php

namespace Drupal\bibcite_entity\Plugin\views\field;

use Drupal\bibcite\CitationStylerInterface;
use Drupal\bibcite\Entity\CslStyleInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * Field handler to render bibcite reference as citation.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("bibcite_citation")
 */
class Citation extends FieldPluginBase {

  /**
   * Serializer service.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * Citation styler service.
   *
   * @var \Drupal\bibcite\CitationStylerInterface
   */
  protected $styler;

  /**
   * {@inheritdoc}
   */
  public function query() {}

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Serializer $serializer, CitationStylerInterface $styler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->serializer = $serializer;
    $this->styler = $styler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('serializer'), $container->get('bibcite.citation_styler'));
  }

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();

    $options['csl_style'] = ['default' => ''];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['csl_style'] = [
      '#type' => 'select',
      '#title' => $this->t('CSL style'),
      '#empty_option' => $this->t('- Default -'),
      '#options' => array_map(function (CslStyleInterface $style) {
        return $style->label();
      }, $this->styler->getAvailableStyles()),
      '#default_value' => $this->options['csl_style'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    return [
      '#theme' => 'bibcite_citation',
      '#data' => $this->serializer->normalize($values->_entity, 'csl'),
      '#style' => $this->options['csl_style'] ?: NULL,
    ];
  }

}
