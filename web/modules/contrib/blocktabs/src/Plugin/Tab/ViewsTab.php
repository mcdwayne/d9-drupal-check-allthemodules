<?php

namespace Drupal\blocktabs\Plugin\Tab;

use Drupal\Core\Form\FormStateInterface;
use Drupal\blocktabs\ConfigurableTabBase;
use Drupal\blocktabs\BlocktabsInterface;
use Drupal\views\Views;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;

/**
 * Views tab.
 *
 * @Tab(
 *   id = "views_tab",
 *   label = @Translation("views tab"),
 *   description = @Translation("views tab.")
 * )
 */
class ViewsTab extends ConfigurableTabBase {

  /**
   * {@inheritdoc}
   */
  public function addTab(BlocktabsInterface $blocktabs) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $markup = $this->t('view name:') . $this->configuration['view_name'] . '; ';
    $markup .= $this->t('display:') . $this->configuration['view_display'] . '; ';
    if (!empty($this->configuration['view_arg'])) {
      $markup .= $this->t('argument:') . $this->configuration['view_arg'];
    }
    $summary = [
      '#markup' => '(' . $markup . ')',
    ];

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'view_name' => NULL,
      'view_display' => NULL,
      'view_arg' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $view_options = Views::getViewsAsOptions(TRUE, 'enabled', NULL, FALSE, TRUE);
    $data = $form_state->getValue('data');
    $default_view_name = isset($data['view_name']) ? $data['view_name'] : $this->configuration['view_name'];	
    $form['view_name'] = [
      '#type' => 'select',
      '#title' => $this->t('view name'),
      '#options' => $view_options,
      '#default_value' => $default_view_name,
      // '#field_suffix' => '',
      // Drupal\blocktabs\Plugin\Tab\ViewsTab.
      '#ajax' => [
        'callback' => [$this, 'updateDisplay'],
        'event' => 'change',
      ],
      '#required' => TRUE,
    ];

    $display_options = [];
    if ($default_view_name) {
      $view = Views::getView($default_view_name);
      foreach ($view->storage->get('display') as $name => $display) {
        $display_options[$name] = $display['display_title'] . ' (' . $display['id'] . ')';
      }
    }

    $form['view_display'] = [
      '#type' => 'select',
      '#title' => $this->t('Display'),
      '#default_value' => $this->configuration['view_display'],
      '#prefix' => '<div id="edit-view-display-wrapper">',
      '#suffix' => '</div>',
      '#options' => $display_options,
      // '#validated' => TRUE,
      '#required' => TRUE,
    ];

    $form['view_arg'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Argument'),
      '#default_value' => $this->configuration['view_arg'],
      // '#field_suffix' => '',
    ];
    return $form;
  }

  /**
   * Update display option.
   */
  public function updateDisplay(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#edit-view-display-wrapper', drupal_render($form['data']['view_display'])));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->configuration['view_name'] = $form_state->getValue('view_name');
    $this->configuration['view_display'] = $form_state->getValue('view_display');
    $this->configuration['view_arg'] = $form_state->getValue('view_arg');
  }

  /**
   * {@inheritdoc}
   */
  public function getContent() {
    $tab_content = '';
    $view_name = $this->configuration['view_name'];
    $view_display = $this->configuration['view_display'];

    $view = Views::getView($view_name);
    $view->setDisplay($view_display);
    $view->execute();
    $count = count($view->result);
    $tab_view = $view->render();

    if ($count > 0) {
      $tab_content = \Drupal::service('renderer')->render($tab_view);
    }
    else {
      $tab_content = NULL;
    }
    return $tab_content;
  }

}
