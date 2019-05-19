<?php

namespace Drupal\views_entity_embed\Form;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\SetDialogTitleCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\editor\EditorInterface;
use Drupal\embed\EmbedButtonInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\views\Views;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides a form to embed URLs.
 */
class ViewsEmbedDialog extends FormBase {

  /**
   * The entity embed display manager.
   *
   * @var \Drupal\entity_embed\EntityEmbedDisplay\EntityEmbedDisplayManager
   */
  protected $entityEmbedDisplayManager;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'views_entity_embed_dialog';
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(FormBuilderInterface $form_builder, ModuleHandlerInterface $module_handler) {
    $this->formBuilder = $form_builder;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder'), $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, EditorInterface $editor = NULL, EmbedButtonInterface $embed_button = NULL) {

    $form_state->set('embed_button', $embed_button);
    $form_state->set('editor', $editor);
    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'editor/drupal.editor.dialog';
    $form['#prefix'] = '<div id="views-entity-embed-dialog-form">';
    $form['#suffix'] = '</div>';

    if (!$form_state->get('step')) {
      $this->selectStepsOfForm($form, $form_state, $embed_button);
    }
    if ($form_state->get('step') == 'select_view') {
      $form = $this->buildSelectViewStep($form, $form_state, $embed_button);
    }
    elseif ($form_state->get('step') == 'select_display') {
      $form = $this->buildSelectDisplay($form, $form_state, $embed_button);
    }
    elseif ($form_state->get('step') == 'select_arguments') {
      $form = $this->buildSelectArguments($form, $form_state);
    }
    $form['#attributes']['class'][] = 'views-entity-embed-dialog-step--' . $form_state->get('step');
    return $form;
  }

  /**
   * Skip steps with only one options.
   */
  protected function selectStepsOfForm(array &$form, FormStateInterface$form_state, EmbedButtonInterface $embed_button) {

    $view_element = $form_state->get('view_element');
    $filterByViews = $this->getViewsOptions($embed_button);
    // If embed button has only 1 view to render we skip step 1.
    if (count($filterByViews) == 1) {
      $view_element['data-view-name'] = key($filterByViews);
      $view = Views::getView($view_element['data-view-name']);
      $view->initHandlers();
      $filterByDisplays = $this->getViewDisplays($view, $embed_button);
      // If only 1 display is available for rendering we skip step 2.
      if (count($filterByDisplays) == 1) {
        $view_element['data-view-display'] = key($filterByDisplays);
        $view->setDisplay($view_element['data-view-display']);
        $form_state->set('step', 'select_arguments');
      }
      else {
        $form_state->set('step', 'select_display');
      }
      $form_state->set('view', $view);
      $form_state->set('view_element', $view_element);
    }
    else {
      // Else set the first step
      $form_state->set('step', 'select_view');
    }
  }
  /**
   * Form constructor for the entity selection step.
   */
  public function buildSelectViewStep(array &$form, FormStateInterface $form_state, $embed_button) {

    $view_element = $form_state->get('view_element');

    $form['view_name'] = [
      '#type' => 'select',
      '#options' => $this->getViewsOptions($embed_button),
      '#title' => t('Select View'),
      '#required' => TRUE,
      '#default_value' => isset($view_element['data-view-name']) ? $view_element['data-view-name'] : '',
    ];

    $form['actions']['save_modal'] = [
      '#type' => 'submit',
      '#value' => $this->t('Next'),
      '#button_type' => 'primary',
      // No regular submit-handler. This form only works via JavaScript.
      '#submit' => [],
      '#ajax' => [
        'callback' => '::submitSelectViewStep',
        'event' => 'click',
      ],
      '#attributes' => [
        'class' => [
          'js-button-next',
        ],
      ],
    ];

    return $form;
  }

  /**
   * Get all Views as options.
   */
  protected function getViewsOptions($embed_button) {
    $views = ['' => $this->t('Select View')];
    foreach (Views::getAllViews() as $view) {
      $views[$view->id()] = $view->label();
    }
    return $embed_button->getTypeSetting('filter_views') ? array_intersect_key($views, $embed_button->getTypeSetting('views_options')) : $views;
  }

  /**
   * Form submission handler for the views selection step.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response.
   */
  public function submitSelectViewStep(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // Display errors in form, if any.
    if ($form_state->hasAnyErrors()) {
      unset($form['#prefix'], $form['#suffix']);
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];
      $response->addCommand(new HtmlCommand('#views-entity-embed-dialog-form', $form));
    }
    else {
      $view_element = $form_state->get('view_element');
      // Add data-view-name with selected view_name.
      $view_element['data-view-name'] = $form_state->getValue('view_name');
      $form_state->set('view_element', $view_element);
      $form_state->set('step', 'select_display');
      $form_state->set('view', Views::getView($form_state->getValue('view_name')));
      $form_state->setRebuild(TRUE);
      $rebuild_form = $this->formBuilder->rebuildForm('views_entity_embed_dialog', $form_state, $form);
      unset($rebuild_form['#prefix'], $rebuild_form['#suffix']);
      $response->addCommand(new HtmlCommand('#views-entity-embed-dialog-form', $rebuild_form));
      $response->addCommand(new SetDialogTitleCommand('', $rebuild_form['#title']));
    }

    return $response;
  }

  /**
   * Form constructor for the view Select display.
   *
   * @return array
   *   The form structure.
   */
  public function buildSelectDisplay(array &$form, FormStateInterface $form_state, $embed_button) {

    $view = $form_state->get('view');
    $view_title = !empty($view->getTitle()) ? $view->getTitle() : $view->id();
    $view_element = $form_state->get('view_element');
    $form['#title'] = $this->t('Select dispay for  @view', ['@view' => $view_title]);
    $displays_options = $this->getViewDisplays($view, $embed_button);
    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['back'] = [
      '#type' => 'submit',
      '#value' => $this->t('Replace selected view'),
      // No regular submit-handler. This form only works via JavaScript.
      '#submit' => [],
      '#attributes' => [
        // @TODO to be fix.
        'disabled' => 'disabled',
      ],
      /* '#ajax' => [
        'callback' => '::submitAndShowSelect',
        'event' => 'click',
        ],
       */
    ];

    $form['actions']['save_modal'] = [
      '#type' => 'submit',
      '#value' => $this->t('Next'),
      '#button_type' => 'primary',
      // No regular submit-handler. This form only works via JavaScript.
      '#submit' => [],
      '#ajax' => [
        'callback' => '::submitSelectDisplay',
        'event' => 'click',
      ],
      '#attributes' => [
        'class' => [
          'js-button-next',
        ],
      ],
    ];

    if (empty($displays_options)) {
      $form['select_display_msg'] = [
        '#type' => '#markup',
        '#markup' => t('There is no display available for this View.'),
        '#weight' => -10,
      ];
      // Add disabled options for this case.
      $form['actions']['save_modal']['#attributes']['disabled'] = 'disabled';
      // Unset Ajax.
      unset($form['actions']['save_modal']['#ajax']);
      unset($form['actions']['save_modal']['#attributes']['class']['js-button-next']);
    }
    else {
      $form['select_display'] = [
        '#type' => 'select',
        '#options' => $displays_options,
        '#default_value' => isset($view_element['data-view-display']) ? $view_element['data-view-display'] : 'default',
        '#required' => TRUE,
        '#weight' => -10,
      ];
    }
    return $form;
  }

  /**
   * Form submission handler for the views selection step.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response.
   */
  public function submitSelectDisplay(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $view = &$form_state->get('view');
    $display = $form_state->getValue('select_display');
    // Display errors in form, if any.
    if ($form_state->hasAnyErrors()) {
      unset($form['#prefix'], $form['#suffix']);
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];
      $response->addCommand(new HtmlCommand('#views-entity-embed-dialog-form', $form));
    }
    else {
      $view_element = $form_state->get('view_element');
      $view_element['data-view-display'] = $display;
      $form_state->set('view_element', $view_element);
      $view->setDisplay($display);
      $form_state->set('step', 'select_arguments');
      $form_state->setRebuild(TRUE);
      $rebuild_form = $this->formBuilder->rebuildForm('views_entity_embed_dialog', $form_state, $form);
      unset($rebuild_form['#prefix'], $rebuild_form['#suffix']);
      $response->addCommand(new HtmlCommand('#views-entity-embed-dialog-form', $rebuild_form));
      $response->addCommand(new SetDialogTitleCommand('', $rebuild_form['#title']));
    }

    return $response;
  }

  /**
   * Get all displays of View.
   */
  protected function getViewDisplays($view, $embed_button) {
    $display_options = $embed_button->getTypeSetting('display_options');

    $filter_displays = $embed_button->getTypeSetting('filter_displays');
    $displays = [];

    foreach ($view->displayHandlers as $id => $display) {
      if ($display->isEnabled()) {

        if (!$filter_displays) {
          $displays[$id] = $id . '-' . $display->getOption('title');
        }
        elseif (!empty($display_options[get_class($display)])) {
          $displays[$id] = $id . '-' . $display->getOption('title');
        }
      }
    }

    return $displays;
  }

  /**
   * Form constructor for the entity embedding step.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildSelectArguments(array $form, FormStateInterface $form_state) {
    $view = $form_state->get('view');

    $form['#title'] = $this->t('Select Argument for @title view', ['@title' => $view->getTitle()]);
    $select_arguments = $form_state->get('select_arguments');
    $form['build_select_arguments'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Views settings'),
      '#tree' => TRUE,
    ];

    $form['build_select_arguments']['override_title'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Override title'),
      '#default_value' => isset($select_arguments['override_title']) ?
        $select_arguments['override_title'] : '',
    ];
    $form['build_select_arguments']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => isset($select_arguments['title']) ?
        $select_arguments['title'] : '',
      '#states' => [
        'visible' => [
          [
            ':input[name="build_select_arguments[override_title]"]' =>
              ['checked' => TRUE],
          ],
        ],
      ],
    ];
    if (!empty($view->argument)) {
      $form['build_select_arguments']['filters'] = [
        '#type' => 'details',
        '#title' => t('Views contexual filters'),
      ];
      foreach ($view->argument as $id => $argument) {
        $form['build_select_arguments']['filters'][$id] = [
          '#type' => 'textfield',
          '#title' => $argument->adminLabel(),
          '#default_value' => isset($select_arguments[$id]) ? $select_arguments[$id] : '',
        ];
      }
    }
    else {
      $form['build_select_arguments']['no_contextual_filters'] = [
        '#type' => 'item',
        '#description' => t('This View does not have a contexual filters.'),
      ];
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['save_modal'] = [
      '#type' => 'submit',
      '#value' => $this->t('Embed'),
      '#button_type' => 'primary',
      // No regular submit-handler. This form only works via JavaScript.
      '#submit' => [],
      '#ajax' => [
        'callback' => '::submitSelectArguments',
        'event' => 'click',
      ],
    ];

    return $form;
  }

  /**
   * Form submission handler for the views selection step.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response.
   */
  public function submitSelectArguments(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $build_arg = $form_state->getValue('build_select_arguments');
    $embed_button = $form_state->get('embed_button');
    // Display errors in form, if any.
    if ($form_state->hasAnyErrors()) {
      unset($form['#prefix'], $form['#suffix']);
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];
      $response->addCommand(new HtmlCommand('#views-entity-embed-dialog-form', $form));
    }
    else {
      $view_element = $form_state->get('view_element');
      // Serialize entity embed settings to JSON string.
      $view_element['data-view-arguments'] = Json::encode($build_arg);
      $view_element['data-embed-button'] = $embed_button->id();
      // Filter out empty attributes.
      $view_element = array_filter($view_element, function ($value) {
        return (bool) Unicode::strlen((string) $value);
      });

      // Allow other modules to alter the values before
      // getting submitted to the WYSIWYG.
      $response->addCommand(new EditorDialogSave(['attributes' => $view_element]));
      $response->addCommand(new CloseModalDialogCommand());
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
