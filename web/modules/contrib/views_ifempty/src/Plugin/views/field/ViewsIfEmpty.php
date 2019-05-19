<?php

/**
 * @file
 * Contains \Drupal\views_ifempty\Plugin\views\field\ViewsIfEmpty.
 */

namespace Drupal\views_ifempty\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to output an alternate field when a field is empty.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("views_ifempty")
 */
class ViewsIfEmpty extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function allowAdvancedRender() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->field_alias = 'views_ifempty_' . $this->position;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['emptyfield'] = array('default' => '');
    $options['outputfield'] = array('default' => '');
    $options['reverse'] = array('default' => FALSE);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Scan all the fields and add them as options for our field selectors.
    $fields = array(
      0 => '- ' . $this->t('no field selected') . ' -',
    );
    foreach ($this->view->display_handler->getHandlers('field') as $field => $handler) {
      // We only use fields up to (not including) this one.
      if ($field == $this->options['id']) {
        break;
      }
      $fields[$field] = $handler->adminLabel();
    }

    $form['emptyfield'] = array(
      '#type' => 'select',
      '#title' => $this->t('If this field is empty'),
      '#description' => $this->t('Check this field to see if is empty. This field will be output normally if not empty.'),
      '#options' => $fields,
      '#default_value' => $this->options['emptyfield'],
    );

    $form['outputfield'] = array(
      '#type' => 'select',
      '#title' => $this->t('Then output this field'),
      '#description' => $this->t('Only output this field when the other field is empty. This field will be hidden if the other field is not empty.'),
      '#options' => $fields,
      '#default_value' => $this->options['outputfield'],
    );

    $form['reverse'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Reverse'),
      '#description' => $this->t('Reverse the normal behavior. Show the output field if the other field is <em>not</em> empty. If the other field is empty, output nothing.'),
      '#default_value' => $this->options['reverse'],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    if (empty($form_state->getValue(array('options', 'emptyfield')))) {
      $form_state->setError($form['emptyfield'], $this->t('Empty field must be specified.'));
    }
    if (empty($form_state->getValue(array('options', 'outputfield')))) {
      $form_state->setError($form['outputfield'], $this->t('Output field must be specified.'));
    }
    if ($form_state->getValue(array('options', 'emptyfield')) === $form_state->getValue(array('options', 'outputfield'))) {
      $form_state->setError($form['outputfield'], $this->t('The output field must be different from the empty field.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    if (!empty($this->options['emptyfield']) && !empty($this->options['outputfield'])) {
      return $this->t('If @emptyfield !is empty, output @outputfield', array(
        '@emptyfield' => $this->options['emptyfield'],
        '!is' => ($this->options['reverse']) ? t('is not') : t('is'),
        '@outputfield' => $this->options['outputfield'],
      ));
    }
    else {
      return $this->t('Invalid field selection');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $emptyfield = $this->options['emptyfield'];
    $outputfield = $this->options['outputfield'];

    // Double-check that the field has been configured properly.
    if (!empty($emptyfield) && !empty($outputfield) && $emptyfield != $outputfield) {
      // Get all the available fields.
      $fields = $this->view->display_handler->getHandlers('field');
      if (isset($fields[$emptyfield]) && isset($fields[$outputfield])) {
        // Is emptyfield empty? If so, output outputfield.
        if (empty($fields[$emptyfield]->last_render)) {
          // If we've selected to reverse the behavior, output nothing.
          if ($this->options['reverse']) {
            $this->last_render = '';
          }
          // Output outputfield.
          else {
            $this->last_render = $fields[$outputfield]->last_render;
          }
        }
        // Emptyfield is not empty.
        else {
          // If we've selected to reverse the behavior, output $outputfield.
          if ($this->options['reverse']) {
            $this->last_render = $fields[$outputfield]->last_render;
          }
          // Output emptyfield.
          else {
            $this->last_render = $fields[$emptyfield]->last_render;
          }
        }
      }
    }
    return $this->last_render;
  }
}
