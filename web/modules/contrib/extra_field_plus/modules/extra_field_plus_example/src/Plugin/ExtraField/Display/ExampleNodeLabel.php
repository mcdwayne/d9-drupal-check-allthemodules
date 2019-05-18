<?php

namespace Drupal\extra_field_plus_example\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\extra_field_plus\Plugin\ExtraFieldPlusDisplayBase;

/**
 * Example Extra field Display.
 *
 * @ExtraFieldDisplay(
 *   id = "example_node_label",
 *   label = @Translation("Example Node Label"),
 *   bundles = {
 *     "node.*"
 *   },
 *   visible = false
 * )
 */
class ExampleNodeLabel extends ExtraFieldPlusDisplayBase {

  /**
   * {@inheritdoc}
   */
  public function view(ContentEntityInterface $entity) {
    $settings = $this->getSettings();

    $link_to_entity = $settings['link_to_entity'];
    $wrapper = $settings['wrapper'];
    $label = $entity->label();
    $url = NULL;

    if ($link_to_entity) {
      $url = $entity->toUrl()->setAbsolute();
    }

    if ($url) {
      $element = [
        '#type' => 'html_tag',
        '#tag' => $wrapper,
        [
          '#type' => 'link',
          '#title' => $label,
          '#url' => $url,
        ],
      ];
    }
    else {
      $element = [
        '#type' => 'html_tag',
        '#tag' => $wrapper,
        '#value' => $label,
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {
    $form = parent::settingsForm();

    $form['link_to_entity'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Link to the entity'),
    ];

    $form['wrapper'] = [
      '#type' => 'select',
      '#title' => $this->t('Wrapper'),
      '#options' => [
        'span' => 'span',
        'p' => 'p',
        'h1' => 'h1',
        'h2' => 'h2',
        'h3' => 'h3',
        'h4' => 'h4',
        'h5' => 'h5',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultFormValues() {
    $values = parent::defaultFormValues();

    $values += [
      'link_to_entity' => FALSE,
      'wrapper' => 'span',
    ];

    return $values;
  }

}
