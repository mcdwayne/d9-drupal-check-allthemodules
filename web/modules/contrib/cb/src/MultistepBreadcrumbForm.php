<?php

namespace Drupal\cb;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Component\Datetime\TimeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base form handler for chained breadcrumb edit forms.
 */
class MultistepBreadcrumbForm extends ContentEntityForm {

  /**
   * The cache contexts manager.
   *
   * @var \Drupal\Core\Cache\Context\CacheContextsManager
   */
  protected $cacheContextsManager;

  /**
   * Constructs a MultistepBreadcrumbForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Cache\Context\CacheContextsManager $cache_contexts_manager
   *   The cache contexts manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(EntityManagerInterface $entity_manager, CacheContextsManager $cache_contexts_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL) {
    parent::__construct($entity_manager, $entity_type_bundle_info, $time);
    $this->cacheContextsManager = $cache_contexts_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('cache_contexts_manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if ($form_state->has('step') && $form_state->get('step') == 2) {
      return $this->secondStepForm($form, $form_state);
    }

    $form_state->set('step', 1);

    $form = parent::buildForm($form, $form_state);

    $breadcrumb = $this->entity;
    $breadcrumb_storage = $this->entityManager->getStorage('cb_breadcrumb');

    $parent_id = $breadcrumb->getParentId();
    $form_state->set(['parent'], $parent_id);

    $form['relations'] = [
      '#type' => 'details',
      '#title' => $this->t('Relations'),
      '#open' => FALSE,
      '#weight' => 9,
    ];

    $query = db_select('cb_breadcrumb', 'cb');
    $query->addField('cb', 'bid');
    $ids = $query->execute()->fetchCol();
    $breadcrumbs = $breadcrumb_storage->loadMultiple($ids);

    $parent = $breadcrumb_storage->load($parent_id);
    $children = $breadcrumb_storage->loadChildren($breadcrumb->id());

    // Exclude all children and current breadcrumb from options.
    foreach ($children as $child) {
      $exclude[] = $child->id();
    }

    $tree = $breadcrumb_storage->buildTree();
    $options[0] = '<' . $this->t('root') . '>';
    $breadcrumb_storage->buildOptions($tree, '', $options, $breadcrumb->id());
    $form['relations']['parent'] = [
      '#type' => 'select',
      '#title' => $this->t('Parent breadcrumb'),
      '#options' => $options,
      '#default_value' => $breadcrumb->isNew() ? 0 : $breadcrumb->getParentId(),
      '#multiple' => TRUE,
      ];

    $form['relations']['weight'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Weight'),
      '#size' => 6,
      '#default_value' => $breadcrumb->getWeight(),
      '#description' => $this->t('Breadcrumbs are displayed in ascending order by weight.'),
      '#required' => TRUE,
    ];

    $cache_contexts_options = [];
    foreach ($this->cacheContextsManager->getLabels(TRUE) as $cache_context => $label) {
      $cache_contexts_options[$cache_context] = $label->__toString();
    }

    $form['application'] = [
      '#type' => 'details',
      '#title' => $this->t('Application condition'),
      '#open' => FALSE,
      '#weight' => 9,
    ];

    $form['application'][] = $form['applies'];

    $form['cache_contexts'] = [
      '#type' => 'details',
      '#title' => $this->t('Cache contexts'),
      '#open' => FALSE,
      '#weight' => 10,
    ];

    $form['cache_contexts']['cache_contexts'] = [
      '#type' => 'select',
      '#title' => $this->t('List of available cache contexts'),
      '#options' => $cache_contexts_options,
      '#default_value' => $breadcrumb->getBreadcrumbCacheContexts() ?: [],
      '#description' => $this->t('The list of cache contexts for the breadcrumb. For example if you want that you breadcrumb was applied per rute (per page) than add cache context - Route. You can select several cache contexts while holding ctrl.'),
      '#multiple' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['next'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Next'),
      '#submit' => ['::firstStepSubmit'],
      '#validate' => ['::validateFirstStep'],
    ];

    return $form;
  }

  /**
   * Submission handler for the first step.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function firstStepSubmit(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    $this->entity = $this->buildEntity($form, $form_state);
    $form_state
      ->set('step', 2)
      ->setRebuild(TRUE);
  }

  /**
   * Validation of the first step form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
   public function validateFirstStep(array &$form, FormStateInterface $form_state) {
     // Ensure numeric values.
     if ($form_state->hasValue('weight') && !is_numeric($form_state->getValue('weight'))) {
       $form_state->setErrorByName('weight', $this->t('Weight value must be numeric.'));
     }
   }

  /**
   * Second step form builder.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The render array defining the elements of the form.
   */
  public function secondStepForm(array &$form, FormStateInterface $form_state) {
    $form = $this->form($form, $form_state);
    $back['back'] = [
      '#type' => 'submit',
      '#value' => $this->t('Back'),
      '#submit' => ['::stepTwoBack'],
    ];

    $breadcrumb = $this->getEntity();
    $route_provider = \Drupal::service('router.route_provider');

    $breadcrumb_routes = [];
    // Get all routes for the breadcrumb property paths.
    foreach ($breadcrumb->pathsToArray() as $pattern) {
      $routes = $route_provider->getRoutesByPattern($pattern);
      foreach ($routes->all() as $route) {
        $breadcrumb_routes[] = $route;
      }
    }

    $token_types = [];
    // Get all available token types from parameters of the routes.
    foreach ($breadcrumb_routes as $route) {
      if ($parameters = $route->getOption('parameters')) {
        foreach ($parameters as $name => $options) {
          if (isset($options['type']) && substr($options['type'], 0, 7) == 'entity:' && !in_array($name, $token_types)) {
            // Cutting taxonomy_term and taxonomy_vocabulary to term and vocabulary.
            // See token_entity_type_alter().
            if (substr($options['type'], 7, 8) == 'taxonomy') {
              $name = substr($options['type'], 16);
            }
            $token_types[] = $name;
          }
        }
      }
      // Get all token types provided by the hook_breadcrumb_token_types() for the route.
      if ($types = \Drupal::moduleHandler()->invokeAll('breadcrumb_token_types', [$route])) {
        $token_types = array_merge($token_types, $types);
      }
    }


    // Show the token help relevant to this step.
    $form['token_help'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => $token_types,
    ];

    // Retrieve and add the form actions array.
    $actions = $this->actionsElement($form, $form_state);
    $form['actions'] = $actions;
    $form['actions'] += $back;

    return $form;
  }

  /**
   * Submission handler for the stepTwoBack action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function stepTwoBack(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    $this->entity = $this->buildEntity($form, $form_state);
    $form_state
      ->set('step', 1)
      ->setRebuild(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function processForm($element, FormStateInterface $form_state, $form) {
    $form = parent::processForm($element, $form_state, $form);
    $storage = $form_state->getStorage();
    // Unset all fields which do not belongs to the current step,
    // except those which defined in particular step.
    if ($storage['step'] == 1) {
      unset($form['applies']);
      unset($form['link_titles']);
      unset($form['link_paths']);
    }
    elseif ($storage['step'] == 2) {
      unset($form['name']);
      unset($form['paths']);
      unset($form['enabled']);
      unset($form['home_link']);
      unset($form['langcode']);
      unset($form['applies']);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Ensure numeric values.
    if ($form_state->hasValue('weight') && !is_numeric($form_state->getValue('weight'))) {
      $form_state->setErrorByName('weight', $this->t('Weight value must be numeric.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    $breadcrumb = parent::buildEntity($form, $form_state);

    // Prevent leading and trailing spaces in breadcrumb names.
    $breadcrumb->setName(trim($breadcrumb->getName()));

    $breadcrumb->setPaths(trim($breadcrumb->getPaths()) . "\r\n");

    if ($form_state->getValue('cache_contexts') && !empty($form_state->getValue('cache_contexts'))) {
      $breadcrumb->setBreadcrumbCacheContexts($form_state->getValue('cache_contexts'));
    }

    return $breadcrumb;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $breadcrumb = $this->entity;

    $result = $breadcrumb->save();

    $edit = $breadcrumb->link($this->t('Edit'), 'edit-form');
    switch ($result) {
      case SAVED_NEW:
        drupal_set_message($this->t('Breadcrumb %breadcrumb was created.', ['%breadcrumb' => $breadcrumb->getName()]));
        $this->logger('cb')->notice('Breadcrumb %breadcrumb was created.', ['%breadcrumb' => $breadcrumb->getName(), 'link' => $edit]);
        break;
      case SAVED_UPDATED:
        drupal_set_message($this->t('Breadcrumb %breadcrumb was updated.', ['%breadcrumb' => $breadcrumb->getName()]));
        $this->logger('cb')->notice('Breadcrumb %breadcrumb was updated.', ['%breadcrumb' => $breadcrumb->getName(), 'link' => $edit]);
        break;
    }

    $current_parent_count = count($form_state->getValue('parent'));
    $previous_parent_count = count($form_state->get(['cb', 'parent']));
    // Root doesn't count if it's the only parent.
    if ($current_parent_count == 1 && $form_state->hasValue(['parent', 0])) {
      $current_parent_count = 0;
      $form_state->setValue('parent', []);
    }

    $form_state->setValue('bid', $breadcrumb->id());
    $form_state->set('bid', $breadcrumb->id());

    if ($breadcrumb->getParentId() != 0) {
      \Drupal::entityManager()->getStorage('cb_breadcrumb')->resetCache([$breadcrumb->getParentId()]);
    }
  }

}
