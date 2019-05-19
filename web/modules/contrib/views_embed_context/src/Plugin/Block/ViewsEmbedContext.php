<?php

namespace Drupal\views_embed_context\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Views Embed Context' block.
 *
 * @Block(
 *   id = "views_embed_context_block",
 *   admin_label = @Translation("Views Embed Context Block"),
 *   category = @Translation("Views Embed Context")
 * )
 */
class ViewsEmbedContext extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
        $configuration, $plugin_id, $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $views_list = \Drupal\views\Views::getEnabledViews();

    $views_options = array();
    foreach ($views_list as $view) {
      $view_id = $view->get('id');
      foreach ($view->get('display') as $display) {
        if ($display['id'] != 'default') {
          $views_options[$view_id . ':' . $display['id']] = $view->get('label') . ' : ' . $display['display_title'];
        }
      }
    }

    $form['view_detail'] = [
      '#type' => 'select',
      '#title' => $this->t('View'),
      '#options' => $views_options,
      '#ajax' => [
        'callback' => [$this, 'updateContextualFilterField'],
//        'wrapper' => 'contextual-filter-fields-group',
      ],
    ];

    $form['views_contextual_fields_group'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'contextual-filter-fields-group'],
    ];
    $form['views_contextual_fields_group_op'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'contextual-filter-fields-group-op'],
    ];
    $input = $form_state->getUserInput();
    if (!empty($input['settings']['view_detail'])) {
      $view_detail = $input['settings']['view_detail'];
      $view_details = explode(':', $view_detail);
      $view = \Drupal\views\Views::getView($view_details[0]);
      $view_display = $view->getDisplay($view_details[1]);
      $arguments = $view_display->getOption('arguments');
      foreach ($arguments as $key => $option) {
        switch ($option['default_argument_type']) {
          case 'node':
            $form['views_contextual_fields_group'][$key] = [
              '#type' => 'entity_autocomplete',
              '#title' => (!empty($option['title']) ? $option['title'] : $key),
              '#target_type' => 'node',
              '#tags' => ($option['break_phrase'] == TRUE ? TRUE : FALSE),
            ];
            if ($option['break_phrase'] == TRUE) {
              $form['views_contextual_fields_group_op'][$key] = [
                '#title' => $this->t('Operator'),
                '#type' => 'select',
                '#options' => [
                  ',' => $this->t('AND'),
                  '+' => $this->t('OR'),
                ],
                '#required' => TRUE,
              ];
            }
            break;

          case 'query_parameter':
            break;
          case 'taxonomy_tid':
            $form['views_contextual_fields_group'][$key] = [
              '#type' => 'entity_autocomplete',
              '#title' => (!empty($option['title']) ? $option['title'] : $key),
              '#target_type' => 'taxonomy_term',
              '#tags' => ($option['break_phrase'] == TRUE ? TRUE : FALSE),
            ];
            if ($option['break_phrase'] == TRUE) {
              $form['views_contextual_fields_group_op'][$key] = [
                '#title' => $this->t('Operator'),
                '#type' => 'select',
                '#options' => [
                  ',' => $this->t('AND'),
                  '+' => $this->t('OR'),
                ],
                '#required' => TRUE,
              ];
            }
            break;

          case 'current_user':
            $user = \Drupal::currentUser();
            $form['views_contextual_fields_group'][$key] = [
              '#type' => 'item',
              '#title' => $this->t('User ID from logged in user'),
              '#markup' => $user->getUsername(),
            ];
            break;

          case 'user':
            $form['views_contextual_fields_group'][$key] = [
              '#type' => 'entity_autocomplete',
              '#title' => (!empty($option['title']) ? $option['title'] : $key),
              '#target_type' => 'user',
              '#tags' => ($option['break_phrase'] == TRUE ? TRUE : FALSE),
            ];
            if ($option['break_phrase'] == TRUE) {
              $form['views_contextual_fields_group_op'][$key] = [
                '#title' => $this->t('Operator'),
                '#type' => 'select',
                '#options' => [
                  ',' => $this->t('AND'),
                  '+' => $this->t('OR'),
                ],
                '#required' => TRUE,
              ];
            }
            break;

          case 'raw':
          case 'fixed':
          default:
            $form['views_contextual_fields_group'][$key] = [
              '#type' => 'textfield',
              '#title' => (!empty($option['title']) ? $option['title'] : $key),
            ];
            break;
        }
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Hide default block form fields that are undesired in this case.
    $form['admin_label']['#access'] = FALSE;
    $form['label']['#access'] = FALSE;
    $form['label_display']['#access'] = FALSE;

    // Hide the block title by default.
    $form['label_display']['#value'] = FALSE;
//print '<pre>'; print_r($form); die;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $views_contextual_fields_group = array();
//    die('here');
    foreach ($values['views_contextual_fields_group'] as $key => $context_filter_value) {
      if (isset($values['views_contextual_fields_group_op'][$key])) {
        $context_group_values = array();
        foreach ($context_filter_value as $value) {
          $context_group_values[] = $value['target_id'];
        }

        $views_contextual_fields_group[] = implode($values['views_contextual_fields_group_op'][$key], $context_group_values);
      }
      else {
        $views_contextual_fields_group[] = isset($context_filter_value[0]['target_id']) ? $context_filter_value[0]['target_id'] : $context_filter_value;
      }
    }
    $this->configuration['view_detail'] = $values['view_detail'];
    $this->configuration['views_contextual_fields_group'] = json_encode($views_contextual_fields_group);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if ($view_detail = $this->configuration['view_detail']) {
      $view_details = explode(':', $view_detail);
      $view = \Drupal\views\Views::getView($view_details[0]);
      $arguments = $this->configuration['views_contextual_fields_group'];
      if (is_object($view)) {
        $view->setArguments(json_decode($arguments));
        $view->setDisplay($view_details[1]);
        $view->preExecute();
        $view->execute();
        return $view->buildRenderable();
      }
    }
  }

  /**
   * Function for updating contextual filter field.
   */
  public function updateContextualFilterField(&$form, FormStateInterface $form_state) {
    // Create a new AjaxResponse.
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#contextual-filter-fields-group', $form['flipper']['front']['settings']['views_contextual_fields_group']));
    $response->addCommand(new ReplaceCommand('#contextual-filter-fields-group-op', $form['flipper']['front']['settings']['views_contextual_fields_group_op']));
    return $response;
  }

}
