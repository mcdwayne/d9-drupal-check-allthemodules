<?php

namespace Drupal\route_specific_breadcrumb\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\route_specific_breadcrumb\Controller\ListRecordsController;

/**
 * Class RouteSpecificForm.
 */
class RouteSpecificForm extends ConfigFormBase {

  protected $database;
  protected $currentUser;
  protected $route;
  protected $serialize;

  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * {@inheritdoc}
   *
   * @param Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database, $currentUser, $route, $serialize, $path_validator) {
    $this->database = $database;
    $this->currenUser = $currentUser;
    $this->route = $route;
    $this->serialize = $serialize;
    $this->pathValidator = $path_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('database'),
        $container->get('current_user'),
        $container->get('router.route_provider'),
        $container->get('serialization.phpserialize'),
        $container->get('path.validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'route_specific_breadcrumb.routespecific',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'route_specific_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $route = NULL;
    $description = NULL;
    if (!empty($this->getRequest()->query)) {
      $rid = $this->getRequest()->query->get('rid');
      if (isset($rid)) {
        $result = ListRecordsController::routeData($this->database, $rid, TRUE);
        if (is_object($result)) {
          $route = $result->route;
          $description = $this->serialize->decode($result->description);
        }
        $form['rid'] = [
          '#type' => 'hidden',
          '#default_value' => $rid,
        ];
        $form['delete'] = [
          '#type' => 'link',
          '#title' => $this->t('Delete'),
          '#url' => Url::fromRoute('route_specific_breadcrumb.route_specific_breadcrumb_delete_form', [
            'rid' => $rid,
          ]
          ),
          '#weight' => 10,
        ];
      }
    }
    $form['route_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Route Name'),
      '#description' => $this->t('Enter Route Name'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $route,
      '#required' => TRUE,
    ];
    if ($description !== NULL) {
      $num_names = $form_state->get('num_names');
      if (empty($num_names)) {
        $num_names = count($description);
      }
      $form_state->set('num_names', $num_names);

    }
    else {
      // Gather the number of names in the form already.
      $num_names = $form_state->get('num_names');
    }
    // We have to ensure that there is at least one name field.
    if ($num_names === NULL) {
      $form_state->set('num_names', 1);
      $num_names = 1;
    }
    $form['#tree'] = TRUE;
    $form['breadcrumb_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Breadcrumb Level'),
      '#prefix' => '<div id="breadcrumb-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];

    for ($i = 0; $i < $num_names; $i++) {
      $form['breadcrumb_fieldset'][$i]['name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Name'),
        '#default_value' => $description[$i]['name'],
      ];
      $form['breadcrumb_fieldset'][$i]['link'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Link'),
        '#default_value' => $description[$i]['link'],
      ];
    }

    $form['breadcrumb_fieldset']['actions'] = [
      '#type' => 'actions',
    ];
    $form['breadcrumb_fieldset']['actions']['add_name'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add one more'),
      '#submit' => ['::addOne'],
      '#ajax' => [
        'callback' => '::addmoreCallback',
        'wrapper' => 'breadcrumb-fieldset-wrapper',
      ],
    ];
    // If there is more than one name, add the remove button.
    if ($num_names > 1) {
      $form['breadcrumb_fieldset']['actions']['remove_name'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove one'),
        '#submit' => ['::removeCallback'],
        '#ajax' => [
          'callback' => '::addmoreCallback',
          'wrapper' => 'breadcrumb-fieldset-wrapper',
        ],
      ];
    }
    $form_state->setCached(FALSE);
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $breadCrumb = $form_state->getValue('breadcrumb_fieldset');
    $route = $form_state->getValue('route_name');
    if (count($this->route->getRoutesByNames([$route])) !== 1) {
      $form_state->setErrorByName('route_name', 'The route you entered does not exists.');
    }
    foreach ($breadCrumb as $key => $value) {
      if (is_numeric($key) && !empty($value['link'])) {
        $url = $this->pathValidator->getUrlIfValid($value['link']);
        if ($url === FALSE && $value['link'] !== '<front>') {
          $form_state->setErrorByName('breadcrumb_fieldset][' . $key . '][link', 'The link is not valid.');
        }
      }
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public function addmoreCallback(array &$form, FormStateInterface $form_state) {
    $name_field = $form_state->get('num_names');
    return $form['breadcrumb_fieldset'];
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public function addOne(array &$form, FormStateInterface $form_state) {
    $name_field = $form_state->get('num_names');
    $add_button = $name_field + 1;
    $form_state->set('num_names', $add_button);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
  public function removeCallback(array &$form, FormStateInterface $form_state) {
    $name_field = $form_state->get('num_names');
    if ($name_field > 1) {
      $remove_button = $name_field - 1;
      $form_state->set('num_names', $remove_button);
    }
    $form_state->setRebuild();
  }

  /**
   * Final submit handler.
   *
   * Reports what values were finally set.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $data = [];
    foreach ($form_state->getValue('breadcrumb_fieldset') as $key => $value) {
      if (is_numeric($key)) {
        $data[$key] = $value;
      }
    }
    $arr = [
      'uid' => $this->currenUser->id(),
      'route' => $form_state->getValue('route_name'),
      'description' => $this->serialize->encode($data),
      'created' => REQUEST_TIME,
      'updated' => REQUEST_TIME,
    ];
    $insert = $this->routeInsert($this->database, $arr);
    if ($insert) {
      drupal_set_message(t('Submitted Successfully.'));
    }
    parent::submitForm($form, $form_state);

  }

  /**
   * Metalinkcheck.
   *
   * @return bool
   *   Return TRUE or FALSE on data operations.
   */
  public function routeInsert($obj, $fields) {
    return $obj->insert('route_specific_breadcrumb')->fields($fields)->execute() === NULL ? FALSE : TRUE;
  }

}
