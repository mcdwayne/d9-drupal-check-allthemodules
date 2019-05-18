<?php

namespace Drupal\entity_comparison\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\entity_comparison\Entity\EntityComparison;
use Drupal\entity_comparison\Entity\EntityComparisonInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class EntityComparisonController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Session service
   *
   * @var \Symfony\Component\HttpFoundation\Session\Session
   */
  protected $session;

  /**
   * Current user service
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $current_user;

  /**
   * @var EntityManagerInterface
   */
  protected $entity_manager;

  /**
   * Entity type manager service
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entity_type_manager;

  /**
   * Constructor.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   * @param \Symfony\Component\HttpFoundation\Session\Session $session
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(RendererInterface $renderer, Session $session, AccountProxyInterface $current_user, EntityManagerInterface $entity_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->renderer = $renderer;
    $this->session = $session;
    $this->current_user = $current_user;
    $this->entity_manager = $entity_manager;
    $this->entity_type_manager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('session'),
      $container->get('current_user'),
      $container->get('entity.manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Display the markup.
   *
   * @param $entity_comparison_id
   * @param $entity_id
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function action($entity_comparison_id, $entity_id, Request $request) {

    $entity_comparison = EntityComparison::load($entity_comparison_id);

    // Process the current request
    $this->processRequest($entity_comparison, $entity_id);

    // Get destination
    $destination = $request->query->get('destination');
    $destination = 'internal:' . $destination;
    // Get route from the uri
    $redirect_url = Url::fromUri($destination);

    // Redirect back the user
    return $this->redirect($redirect_url->getRouteName(), $redirect_url->getRouteParameters());

/*
    if ($request->get(MainContentViewSubscriber::WRAPPER_FORMAT) == 'drupal_ajax') {
      // Create a new AJAX response.
      $response = new AjaxResponse();

      // Generate the link render array.
      $link = $entity_comparison->getLink($entity_id);

      // Generate a CSS selector to use in a JQuery Replace command.
      $selector = '#entity-comparison-' . $entity_comparison->id() . '-' . $entity_id;

      // Create a new JQuery Replace command to update the link display.
      $replace = new ReplaceCommand($selector, $this->renderer->renderPlain($link));
      $response->addCommand($replace);

      return $response;
    }
    else {
      return $this->redirect($redirect_url->getRouteName(), $redirect_url->getRouteParameters());
    }
*/
  }

  /**
   * Process the request
   *
   * @param $entity_comparison
   * @param $entity_id
   */
  protected function processRequest(EntityComparisonInterface $entity_comparison, $entity_id) {

    // Get current user's id
    $uid = $this->current_user->id();

    // Get entity type and bundle type
    $entity_type = $entity_comparison->getTargetEntityType();
    $bundle_type = $entity_comparison->getTargetBundleType();

    // Get current entity comparison list
    $entity_comparison_list = $this->session->get('entity_comparison_' . $uid);

    // Get entity
    $entity = $this->entity_type_manager->getStorage($entity_type)->load($entity_id);

    if ( empty($entity_comparison_list) ) {
      $add = TRUE;
    } else {
      if ( !empty($entity_comparison_list[$entity_type][$bundle_type][$entity_comparison->id()]) && in_array($entity_id, $entity_comparison_list[$entity_type][$bundle_type][$entity_comparison->id()]) ) {
        $add = FALSE;
      } else {
        $add = TRUE;
      }
    }

    if ($add) {

      // Get the limit
      $limit = $entity_comparison->getLimit();

      // If the increased number of the list is lower or equal than the limit OR limit is 0 (no limit)

      if ( (isset($entity_comparison_list[$entity_type][$bundle_type][$entity_comparison->id()])
        && (count($entity_comparison_list[$entity_type][$bundle_type][$entity_comparison->id()]) + 1) <= $limit)
        || ($limit == 0)
        || (!isset($entity_comparison_list[$entity_type][$bundle_type][$entity_comparison->id()]) && $limit >= 1)) {

        // Add to the list
        $entity_comparison_list[$entity_type][$bundle_type][$entity_comparison->id()][] = $entity_id;
        drupal_set_message($this->t("You have successfully added %entity_name to %entity_comparison list.", array(
          '%entity_name' => $entity->label(),
          '%entity_comparison' => $entity_comparison->label(),
        )));
      } else {
        drupal_set_message($this->t("You can only add @limit items to the %entity_comparison list.", array(
          '@limit' => $limit,
          '%entity_comparison' => $entity_comparison->label(),
        )), 'error');
      }

    } else{
      $key = array_search($entity_id, $entity_comparison_list[$entity_type][$bundle_type][$entity_comparison->id()]);
      unset($entity_comparison_list[$entity_type][$bundle_type][$entity_comparison->id()][$key]);
      drupal_set_message($this->t("You have successfully removed %entity_name from %entity_comparison.", array(
        '%entity_name' => $entity->label(),
        '%entity_comparison' => $entity_comparison->label(),
      )));
    }

    $this->session->set('entity_comparison_' . $uid, $entity_comparison_list);
  }

  /**
   * Compare page
   *
   * @return array
   */
  public function compare() {

    // Get the entity comparison id from the current path
    $current_path = \Drupal::service('path.current')->getPath();
    $current_path_array = explode('/', $current_path);
    $entity_comparison_id = str_replace('-', '_', array_pop($current_path_array));

    // Declare table header and rows
    $header = array('');
    $rows = array();

    // Load the related entity comparison
    $entity_comparison = EntityComparison::load($entity_comparison_id);

    // Get current user's id
    $uid = $this->current_user->id();

    // Get entity type and bundle type
    $entity_type = $entity_comparison->getTargetEntityType();
    $bundle_type = $entity_comparison->getTargetBundleType();

    // Get the related entity view display
    $entity_view_display = EntityViewDisplay::load($entity_type . '.' . $bundle_type . '.' . $bundle_type . '_' . $entity_comparison_id);

    // Load field definitions
    $field_definitions = $this->entity_manager->getFieldDefinitions($entity_comparison->getTargetEntityType(), $entity_comparison->getTargetBundleType());

    // Get fields
    $fields = $this->getTargetFields($field_definitions, $entity_view_display, $entity_comparison_id);

    // Get current entity comparison list
    $entity_comparison_list = $this->session->get('entity_comparison_' . $uid);

    $entities = array();

    if ( isset($entity_comparison_list[$entity_type][$bundle_type][$entity_comparison_id]) ) {

      // Go through entities
      foreach ($entity_comparison_list[$entity_type][$bundle_type][$entity_comparison_id] as $entity_id) {
        // Get entity
        $entity = $this->entity_type_manager->getStorage($entity_type)->load($entity_id);

        // Get view builder
        $view_builder = $this->entity_type_manager->getViewBuilder($entity_type);

        $entities[$entity_id] = array();

        // Add entity's label to the header
        $header[] = $entity->toLink($entity->label());

        foreach ($fields as $field_name => $display_component) {
          if (isset($entity->{$field_name}) && $field = $entity->{$field_name}) {

            $field_renderable = $view_builder->viewField($field, $display_component);

            $entities[$entity_id][$field_name] = drupal_render($field_renderable);

          }
        }

      }

      // If there are at least one entity in the list
      if ( count($entities) ) {
        // Add the first row, where user can remove the selected content fro the list
        $row = array($this->t("Remove from the list"));
        foreach(Element::children($entities) as $key) {
          $row[] = $entity_comparison->getLink($key);
        }
        $rows[] = $row;

        // Go through the selected fields
        foreach ($fields as $field_name => $display_component) {

          // Set the field's label
          if ( is_a($field_definitions[$field_name], 'Drupal\field\Entity\FieldConfig') ) {
            // FieldConfig
            $row = array($field_definitions[$field_name]->label());
          } elseif ( is_a($field_definitions[$field_name], 'Drupal\Core\Field\BaseFieldDefinition') ) {
            //
            $row = array($field_definitions[$field_name]->getLabel());
          } else {
            // Do not write inse the first column
            $row = array('');
          }

          // Set the fields' values
          foreach (Element::children($entities) as $key) {
            $row[] = $entities[$key][$field_name];
          }
          $rows[] = $row;
        }
      }
    }

    return array(
      '#type' => 'table',
      '#rows' => $rows,
      '#header' => $header,
      '#empty' => t('No content available to compare.'),
      '#cache' => array(
        'max-age' => 0,
      ),
    );
  }

  /**
   * Get target fields
   *
   * @param $field_definitions
   * @param \Drupal\Core\Entity\Entity\EntityViewDisplay $entity_view_display
   * @return array
   */
  protected function getTargetFields($field_definitions, EntityViewDisplay $entity_view_display, $entity_comparison_id) {
    $content_fields = $entity_view_display->get('content');

    $filtered_fields = array();

    foreach( $content_fields as $field_name => $field_settings ) {

      if ( isset($field_definitions[$field_name]) && isset($content_fields[$field_name]) && $field_definitions[$field_name]->isDisplayConfigurable('view') ) {
        $filtered_fields[$field_name] = $content_fields[$field_name];
      }
    }

    // Sort the fields by weight
    uasort($filtered_fields, 'Drupal\Component\Utility\SortArray::sortByWeightElement');

    return $filtered_fields;
  }

  protected function createTableHeaderFromFields($fields) {
    $header = array();

    $header = $fields;

    return $header;
  }

}