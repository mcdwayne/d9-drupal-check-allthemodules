<?php

namespace Drupal\drd\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\drd\Entity\RequirementInterface;
use Drupal\views\Plugin\views\field\Standard;
use Drupal\views\ResultRow;

/**
 * A handler to display the requirement category.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("drd_requirement_category")
 */
class RequirementCategory extends Standard {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    $this->realField = 'category';
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['drd_status'] = ['default' => 'ok'];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['drd_status'] = [
      '#type' => 'select',
      '#title' => $this->t('Status'),
      '#options' => [
        'ok' => $this->t('OK'),
        'warning' => $this->t('Warning'),
        'error' => $this->t('Error'),
      ],
      '#default_value' => $this->options['drd_status'],
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $requirement = $values->_entity;
    if ($requirement instanceof RequirementInterface) {
      $category = $requirement->getCategory();
      $class = [$category];
      $class[] = $this->options['drd_status'];
      $output = '<span title="' . $category . '" class="' . implode(' ', $class) . '">' . $category . '</span>';
    }
    else {
      $output = $this->getValue($values);
    }
    return Markup::create('<div class="drd-remote-status-single">' . $output . '</div>');
  }

}
