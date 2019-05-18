<?php

namespace Drupal\cck_select_other\Plugin\views\filter;

use Drupal\cck_select_other\EntityDisplayTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\options\Plugin\views\filter\ListField;
use Drupal\views\FieldAPIHandlerTrait;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;

/**
 * Select other filter handler.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("select_other")
 *
 * @todo Remove use on core internal plugins. This one is particularly useful so make a decision about it later.
 */
class SelectOtherFilter extends ListField {

  use FieldAPIHandlerTrait;
  use EntityDisplayTrait;

  /**
   * @var \Drupal\views\Plugin\views\query\Sql
   */
  public $query = NULL;

  /**
   * @var \Drupal\Core\Field\FieldDefinitionInterface
   */
  protected $instance;

  protected $valueFormType = 'select';

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    /** @var \Drupal\Core\Field\FieldDefinitionInterface $instance */
    $this->instance = $this->getFieldDefinition();

    $settings = $this->getWidgetSettings($this->instance);

    if ($settings) {
      $this->valueOptions['other'] = isset($settings['other_label']) ? $settings['other_label'] : $this->t('Other');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);

    $old_form = $form['value'];
    $form['value'] = [
      '#tree' => TRUE,
      '#type' => 'container',
      '#attributes' => [
        'class' => ['form-select-other-wrapper', 'cck-select-other-wrapper'],
      ],
      'select_other_list' => $old_form,
      'select_other_text_input' => [
        '#type' => 'textfield',
        '#title' => $this->t('Provide other option'),
        '#size' => 30,
        '#attributes' => [
          'class' => ['form-text', 'form-select-other-text-input'],
        ],
      ],
    ];

    $form['value']['select_other_list']['#attributes']['class'][] = 'form-select-other-list';

    if ($form_state->get('exposed')) {

      // Set the parents on the exposed element to a long string instead of an
      // array like should be able to do in a normal form array. DrupalWTF.
      if ($identifier = $this->options['expose']['identifier']) {
        $form['value']['select_other_list']['#parents'] = [$identifier . '_select_other_list'];
        $form['value']['select_other_text_input']['#parents'] = [$identifier . '_select_other_text_input'];
      }

      // Set multiple.
      $form['value']['select_other_list']['#multiple'] = $this->options['expose']['multiple'];

      // Attach JavaScript.
      $element_class = 'form-item-' . str_replace('_', '-', $identifier) . '-select-other-list';
      $form['value']['#attached'] = [
        'library' => ['cck_select_other/widget'],
        'drupalSettings' => [
          'CCKSelectOther' => [
            $identifier => [$element_class],
          ],
        ],
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function valueSubmit($form, FormStateInterface $form_state) {
    parent::valueSubmit($form, $form_state);

    if (is_array($form['value']['select_other_list']['#value'])) {
      $values = [];
      foreach ($form['value']['select_other_list']['#value'] as $key => $value) {
        if ($key === 'other') {
          $values[] = $form['value']['select_other_text_input']['#value'];
        }
        else {
          $values[] = $value;
        }
      }
    }
    else {
      if ($form['value']['select_other_list']['#value'] === 'other') {
        $values = $form['value']['select_other_text_input']['#value'];
      }
      else {
        $values = $form['value']['select_other_list']['#value'];
      }
    }

    // Set the form state based on whether or not the form is exposed to the
    // user because the form element #parents arbitrarily change.
    $parents = $form_state->get('exposed') ? [$this->options['expose']['identifier']] : ['options', 'value'];
    $form_state->setValueForElement(['#parents' => $parents], $values);
  }

  /**
   * {@inheritdoc}
   */
  public function acceptExposedInput($input) {
    // Take the mangled form input and morph it into the correct input that the
    // parent views filter code expects.
    if ($this->options['exposed']) {
      $identifier = $this->options['expose']['identifier'];
      $input[$identifier] = $input[$identifier . '_select_other_list'];

      // Remove the other value from input and replace with the text input
      // value.
      if (is_array($input[$identifier])) {
        if (isset($input[$identifier . '_select_other_list']) &&
          in_array('other', $input[$identifier . '_select_other_list'])
        ) {
          unset($input[$identifier]['other']);
          $input[$identifier][] = $input[$identifier .
          '_select_other_text_input'];
        }
      }
      else {
        if (isset($input[$identifier . '_select_other_list']) &&
            $input[$identifier . '_select_other_list'] === 'other') {
          $input[$identifier] = $input[$identifier . '_select_other_text_input'];
        }
      }
    }

    $ret = parent::acceptExposedInput($input);
    return $ret;
  }

  /**
   * {@inheritdoc}
   */
  protected function opSimple() {
    if (empty($this->value)) {
      return;
    }

    if (in_array('other', $this->value)) {
      $this->ensureMyTable();

      $values = array_diff($this->valueOptions, $this->value);
      $operator = ($this->operator === 'in') ? 'not in' : 'in';
      $this->query->addWhere($this->options['group'], "$this->tableAlias.$this->realField", array_values($values), $operator);
    }
    else {
      parent::opSimple();
    }
  }

}
