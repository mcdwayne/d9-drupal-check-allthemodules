<?php

namespace Drupal\views_render_view\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Random;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Component\Utility\Html;
use Drupal\views\Views;


/**
 * A handler to provide a field that is completely custom by the administrator.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("views_render_view_field")
 */
class ViewsRenderViewField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing -- to override the parent query.
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['view_to_insert'] = ['default' => ''];
    $options['view_arguments'] = ['default' => ''];
    $options['contextual_filters'] = ['default' => FALSE];
    $options['separator'] = ['default' => ','];
    $options['hide_alter_empty'] = ['default' => FALSE];
    return $options;
  }

/**
 * Return the replacement patterns. 
 * Ripped from lines 861 through 904 from FieldPluginBase
 *
 * @return HTML
 *   An HTML string of replacement patterns
 */
  public function getReplacementPatterns() {
    // Get a list of the available fields and arguments for token replacement.

    // Setup the tokens for fields.
    $previous = $this->getPreviousFieldLabels();
    $optgroup_arguments = (string) t('Arguments');
    $optgroup_fields = (string) t('Fields');
    foreach ($previous as $id => $label) {
      $options[$optgroup_fields]["{{ $id }}"] = substr(strrchr($label, ":"), 2 );
    }
    // Add the field to the list of options.
    $options[$optgroup_fields]["{{ {$this->options['id']} }}"] = substr(strrchr($this->adminLabel(), ":"), 2 );

    foreach ($this->view->display_handler->getHandlers('argument') as $arg => $handler) {
      $options[$optgroup_arguments]["{{ arguments.$arg }}"] = $this->t('@argument title', ['@argument' => $handler->adminLabel()]);
      $options[$optgroup_arguments]["{{ raw_arguments.$arg }}"] = $this->t('@argument input', ['@argument' => $handler->adminLabel()]);
    }

    $this->documentSelfTokens($options[$optgroup_fields]);

    // Default text.

    $output = [];
    $output[] = [
      '#markup' => '<p>' . $this->t('You must add some additional fields to this display before using this field. These fields may be marked as <em>Exclude from display</em> if you prefer. Note that due to rendering order, you cannot use fields that come after this field; if you need a field not listed here, rearrange your fields.') . '</p>',
    ];
    // We have some options, so make a list.
    if (!empty($options)) {
      $output[] = [
        '#markup' => '<p>' . $this->t("The following replacement tokens are available for this field. Note that due to rendering order, you cannot use fields that come after this field; if you need a field not listed here, rearrange your fields.") . '</p>',
      ];
      foreach (array_keys($options) as $type) {
        if (!empty($options[$type])) {
          $items = [];
          foreach ($options[$type] as $key => $value) {
            $items[] = $key . ' == ' . $value;
          }
          $item_list = [
            '#theme' => 'item_list',
            '#items' => $items,
          ];
          $output[] = $item_list;
        }
      }
    }
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $view_display = $this->view->storage->id() . ':' . $this->view->current_display;

    $options = ['' => $this->t('-Select-')];
    $options += Views::getViewsAsOptions(FALSE, 'all', $view_display, FALSE, TRUE);
    $form['view_to_insert'] = [
      '#type' => 'select',
      '#title' => $this->t('View to insert'),
      '#default_value' => $this->options['view_to_insert'],
      '#description' => $this->t('The view to insert into this field.'),
      '#options' => $options,
    ];
    $form['contextual_filters'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include contextual filters'),
      '#default_value' => $this->options['contextual_filters'],
    ];
    $form['view_arguments'] = [
      '#title' => $this->t('Contextual filters'),
      '#description' => $this->t('The arguments to pass to the view. Separate each argument with a comma (,) or you many specify another separator below. You may enter data from this view as per the "Replacement patterns" below.'),
      '#type' => 'textfield',
      '#default_value' => $this->options['view_arguments'],
        '#states' => [
          'visible' => [
            ':input[name="options[contextual_filters]"]' => ['checked' => TRUE],
          ],
        ],
        '#maxlength' => 255,
    ];
    $form['separator'] = [
      '#title' => $this->t('Separator'),
      '#description' => $this->t('The separator that qualifies the arguments above.'),
      '#type' => 'textfield',
      '#default_value' => $this->options['separator'],
        '#states' => [
          'visible' => [
            ':input[name="options[contextual_filters]"]' => ['checked' => TRUE],
          ],
        ],
        '#maxlength' => 4,
        '#size' => 4,
    ];

    $output = $this->getReplacementPatterns();

    // This construct uses 'hidden' and not markup because process doesn't
    // run. It also has an extra div because the dependency wants to hide
    // the parent in situations like this, so we need a second div to
    // make this work.
    $form['help'] = [
      '#type' => 'details',
      '#title' => $this->t('Replacement patterns'),
      '#value' => $output,
      '#states' => [
        'visible' => [
          [
            ':input[name="options[contextual_filters]"]' => ['checked' => TRUE],
          ],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   * [Mostly] ripped from view area
   */
  public function render(ResultRow $values) {
    if (!empty($this->options['view_to_insert'])) {
      list($view_name, $display_id) = explode(':', $this->options['view_to_insert']);

      $view = \Drupal::entityTypeManager()->getStorage('view')->load($view_name)->getExecutable();

      if (empty($view) || !$view->access($display_id)) {
        return [];
      }
      $view->setDisplay($display_id);

      // Avoid recursion
      $view->parent_views += $this->view->parent_views;
      $view->parent_views[] = "$view_name:$display_id";

      // Check if the view is part of the parent views of this view
      $search = "$view_name:$display_id";
      if (in_array($search, $this->view->parent_views)) {
        drupal_set_message(t("Recursion detected in view @view display @display.", ['@view' => $view_name, '@display' => $display_id]), 'error');
      }
      else {
        if (!empty($this->options['view_arguments'])) {
          $tokens = $this->getRenderTokens([]);
          $args = strip_tags(Html::decodeEntities(strtr($this->options['view_arguments'], $tokens)));
          $args = preg_split('/'.$this->options['separator'].'/', $args);
          $output = $view->preview($display_id, $args);
        }
        else {
          $output = $view->preview($display_id);
        }
        $this->isEmpty = $view->display_handler->outputIsEmpty();
        return $output;
      }
    }
    return [];
  }

}
