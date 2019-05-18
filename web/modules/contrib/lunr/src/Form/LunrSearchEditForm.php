<?php

namespace Drupal\lunr\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\lunr\Plugin\views\row\LunrSearchIndexRow;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base form for Lunr search add/edit forms.
 */
class LunrSearchEditForm extends EntityForm {

  /**
   * The Lunr search being edited/created.
   *
   * @var \Drupal\lunr\LunrSearchInterface
   */
  protected $entity;

  /**
   * The route builder.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routeBuilder;

  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * LunrSearchEditForm constructor.
   *
   * @param \Drupal\Core\Routing\RouteBuilderInterface $route_builder
   *   The route builder.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator.
   */
  public function __construct(RouteBuilderInterface $route_builder, PathValidatorInterface $path_validator) {
    $this->routeBuilder = $route_builder;
    $this->pathValidator = $path_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('router.builder'),
      $container->get('path.validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->label(),
      '#description' => $this->t('The label for this search, which is used as the title for its page.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\lunr\Entity\LunrSearch::load',
      ],
      '#disabled' => !$this->entity->isNew(),
      '#required' => TRUE,
    ];

    $form['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path'),
      '#default_value' => $this->entity->getPath(),
      '#description' => $this->t('Where this form will appear.'),
      '#required' => TRUE,
    ];

    // Get only those enabled Views that have entity_browser displays.
    $displays = Views::getApplicableViews('lunr_search_display');
    $options = [];
    foreach ($displays as $display) {
      list($view_id, $display_id) = $display;
      $view = $this->entityTypeManager->getStorage('view')->load($view_id);
      $options[$view_id . '.' . $display_id] = $this->t('@view : @display', ['@view' => $view->label(), '@display' => $view->get('display')[$display_id]['display_title']]);
    }

    $form['view'] = [
      '#type' => 'select',
      '#title' => $this->t('View : Display'),
      '#description' => $this->t('The view and display to build the index from. Only "Lunr search index" view displays can be used.'),
      '#default_value' => $this->entity->getViewId() . '.' . $this->entity->getViewDisplayId(),
      '#options' => $options,
      '#empty_option' => $this->t('- Select a view -'),
      '#required' => TRUE,
      '#ajax' => [
        'wrapper' => 'lunr-search-view-fields',
        'callback' => '::refreshViewsFields',
      ],
    ];

    $form['view_fields'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'lunr-search-view-fields',
      ],
      'views_fields' => [
        '#markup' => $this->getViewsFieldsText(),
      ],
    ];

    $text = '';
    foreach ($this->entity->getIndexFields() as $field => $attributes) {
      $text .= "$field|${attributes['boost']}\n";
    }

    $form['index_fields_textarea'] = [
      '#type' => 'textarea',
      '#cols' => 10,
      '#title' => $this->t('Index fields'),
      '#description' => $this->t('Fields to index in the format field_name|boost. The lowest boost value is 1.'),
      '#default_value' => $text,
      '#required' => TRUE,
    ];

    $form['display_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Display field'),
      '#description' => $this->t('A field to display. Normally this is a rendered entity or custom text/Twig field.'),
      '#default_value' => $this->entity->getDisplayField(),
      '#required' => TRUE,
    ];

    $form['results_per_page'] = [
      '#type' => 'number',
      '#title' => $this->t('Results per page'),
      '#description' => $this->t('How many search results to show per page.'),
      '#default_value' => $this->entity->getResultsPerPage() ?: 10,
      '#min' => 1,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    if ($form_state->getValue('view')) {
      list($view_id, $display_id) = explode('.', $form_state->getValue('view'));
      $form_state->setValue('view_id', $view_id);
      $form_state->setValue('view_display_id', $display_id);
    }
    else {
      $form_state->setValue('view_id', NULL);
      $form_state->setValue('view_display_id', NULL);
    }
    $fields = [];
    foreach (explode("\n", $form_state->getValue('index_fields_textarea')) as $line) {
      $parts = explode('|', $line);
      if (count($parts) !== 2) {
        continue;
      }
      list($field, $boost) = $parts;
      $fields[$field] = [
        'boost' => (int) $boost,
      ];
    }
    $form_state->setValue('index_fields', $fields);
    return parent::buildEntity($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $path = $form_state->getValue('path');
    $parsed_url = UrlHelper::parse($path);
    if (empty($parsed_url['path'])) {
      $form_state->setError($form['path'], $this->t('Path is empty.'));
    }
    elseif (!empty($parsed_url['query']) || !empty($parsed_url['fragment'])) {
      $form_state->setError($form['path'], $this->t('No query or fragment allowed.'));
    }
    elseif ($path[0] !== '/') {
      $form_state->setError($form['path'], $this->t('The path must begin with a slash.'));
    }
    elseif (UrlHelper::isExternal($path)) {
      $form_state->setError($form['path'], $this->t('The path cannot be external.'));
    }
    elseif ($path !== $form['path']['#default_value'] && $this->pathValidator->getUrlIfValidWithoutAccessCheck($path)) {
      $form_state->setError($form['path'], $this->t('This path is already used on the site.'));
    }
    foreach (preg_split('/\R/', $form_state->getValue('index_fields_textarea')) as $line) {
      if (empty($line)) {
        continue;
      }
      $parts = explode('|', $line);
      if (count($parts) !== 2) {
        $form_state->setError($form['index_fields_textarea'], $this->t('Invalid field format provided.'));
        break;
      }
      if (!is_numeric($parts[1])) {
        $form_state->setError($form['index_fields_textarea'], $this->t('Invalid boost value provided.'));
      }
      elseif ($parts[1] < 1) {
        $form_state->setError($form['index_fields_textarea'], $this->t('A positive boost value is required.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    $this->routeBuilder->setRebuildNeeded();
    $this->messenger()->addMessage($this->t('The Lunr search has been saved. You may need to index again if you made configuration changes that affect the index.'));
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    if (!$this->entity->isNew()) {
      $actions['index'] = [
        '#type' => 'link',
        '#title' => $this->t('Index'),
        '#url' => $this->entity->toUrl('index'),
        '#attributes' => [
          'class' => ['button'],
        ],
      ];
    }

    return $actions;
  }

  /**
   * Gets a helper string for the current view's fields.
   *
   * @return string
   *   A helper string.
   */
  protected function getViewsFieldsText() {
    $fields = [];
    $text = '';
    $view = $this->entity->getView();
    if ($view && $view->setDisplay($this->entity->getViewDisplayId())) {
      $view->initHandlers();
      if ($view->rowPlugin instanceof LunrSearchIndexRow) {
        foreach ($view->field as $id => $field) {
          if (empty($field->options['exclude'])) {
            $fields[] = $view->rowPlugin->getFieldKeyAlias($id);
          }
        }
      }
    }
    if (!empty($fields)) {
      $text = $this->t('<strong>Available fields for this view</strong>: @fields.', [
        '@fields' => implode(', ', $fields),
      ]);
    }
    return $text;
  }

  /**
   * Refreshes the views fields helper text.
   *
   * @param array $form
   *   The form array.
   *
   * @return array
   *   The views fields form element.
   */
  public function refreshViewsFields(array $form) {
    return $form['view_fields'];
  }

}
