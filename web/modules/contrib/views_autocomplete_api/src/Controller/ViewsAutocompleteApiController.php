<?php

namespace Drupal\views_autocomplete_api\Controller;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\views\ViewExecutableFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ViewAutocompleteController. Provides an autocomplete with views route.
 *
 * @package Drupal\views_autocomplete_api\Controller
 */
class ViewsAutocompleteApiController extends ControllerBase {

  /**
   * The entity manager.
   *
   * @var EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The object views execute.
   *
   * @var ViewExecutableFactory
   */
  protected $viewsExecute;

  /**
   * The current user.
   *
   * @var AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The renderer service.
   *
   * @var Renderer
   */
  protected $renderer;

  /**
   * The config factory.
   *
   * @var ConfigFactory
   */
  protected $configFactory;

  /**
   * The service logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * ViewsAutocompleteApiController constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_manager
   *   The entity manager.
   * @param \Drupal\views\ViewExecutableFactory $views_execute
   *   The object views execute.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer service.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger
   *   The service logger.
   */
  public function __construct(EntityTypeManager $entity_manager, ViewExecutableFactory $views_execute, AccountProxyInterface $current_user, Renderer $renderer, ConfigFactory $config_factory, LoggerChannelFactory $logger) {
    $this->entityTypeManager = $entity_manager;
    $this->viewsExecute = $views_execute;
    $this->currentUser = $current_user;
    $this->renderer = $renderer;
    $this->configFactory = $config_factory;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('views.executable'),
      $container->get('current_user'),
      $container->get('renderer'),
      $container->get('config.factory'),
      $container->get('logger.factory')
    );
  }

  /**
   * Return the views results data in JSON.
   *
   * @param string $view_name
   *   The view machine name.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The http request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The views response formated in JSON.
   */
  public function getViewsDataJson($view_name, $display_id, $views_arguments, Request $request) {
    $search = $request->query->get('q');
    if (empty($search) || empty($view_name)) {
      $this->logger->get('views_auto_complete_api')
        ->error('Calling views autocomplete API without search words or views name.');
      return new JsonResponse([]);
    }
    // Load view.
    /** @var \Drupal\views\ViewEntityInterface $view */
    $view_list = explode(',', $view_name);
    if (!empty($display_id)) {
      $display_id = explode(',', $display_id);
    }
    else {
      for ($i = 0; $i < count($view_list); $i++) {
        $display_id[$i] = 'default';
      }
    }
    if (count($display_id) != count($view_list)) {
      $this->logger->get('views_auto_complete_api')
        ->warning('Number of display are different from number of views, calling in parameters controller.');
    }

    // Prepare arguments.
    $args_views = [];
    if (!empty($views_arguments)) {
      $args = explode(',', $views_arguments);
      if (count($args) != count($view_list)) {
        $this->logger->get('views_auto_complete_api')
          ->warning('Number of views arguments are different from number of views, calling in parameters controller.');
      }
      foreach ($args as $arg) {
        $args_views[] = explode('&', $arg);
      }
    }

    // Initialise the view data (the data which we returned in the fields
    // autocomplete).
    $view_data = [];
    // Initialise the view data which we formatted to return a correct
    // key-Value.
    foreach ($view_list as $delta => $view_name) {
      if (isset($view)) {
        unset($view);
      }
      $view = $this->entityTypeManager->getStorage('view')->load($view_name);
      if (!$view) {
        $this->logger->get('views_auto_complete_api')
          ->error("Can't load views \"%view_name\"", ['%view_name' => $view_name]);
        continue;
      }
      $view = $this->viewsExecute->get($view);
      /* @var $view \Drupal\views\ViewExecutable */
      if (!$view) {
        $this->logger->get('views_auto_complete_api')
          ->error("Can't load views \"%view_name\"", ['%view_name' => $view_name]);
        continue;
      }
      if (empty($display_id[$delta])) {
        $display_id[$delta] = 'default';
      }
      // Set display.
      if ($view->setDisplay($display_id[$delta]) == FALSE) {
        $this->logger->get('views_auto_complete_api')
          ->error("No display \"%display_name\" found for the views \"%view_name\"", [
            '%display_name' => $display_id[$delta],
            '%view_name' => $view_name,
          ]);
        continue;
      }
      // Check permission to the view display default(master).
      if (!$view->access($display_id[$delta]) && !$this->currentUser->hasPermission('administer views')) {
        $this->logger->get('views_auto_complete_api')
          ->warning("Access denied for the views \"%view_name\"", [
            '%view_name' => $view_name,
          ]);
        continue;
      }

      if (!empty($args_views[$delta])) {
        $view->setArguments($args_views[$delta]);
      }
      // Loop on each exposed filter.
      $filters = [];
      // Get exposed filter and add to query.
      if (!empty($view->filter)) {
        /* @var $filter \Drupal\views\Plugin\views\filter\FilterPluginBase */
        foreach ($view->filter as $filter) {
          if ($filter->options['exposed'] == TRUE && !empty($filter->options['expose']['identifier'])) {
            $filters[$filter->options['expose']['identifier']] = $search;
          }
        }
      }
      elseif (!empty($view->display_handler->options['filters'])) {
        foreach ($view->display_handler->options['filters'] as $key => $options) {
          if ($options['exposed'] == TRUE && !empty($options['expose']['identifier'])) {
            $filters[$options['expose']['identifier']] = $search;
          }
        }
      }
      // Set request to auto-fill exposed filters.
      $query = $request->query;
      $query->add($filters);
      // Execute the view to get results.
      $view->executeDisplay();

      // Gets the current style plugin object.
      /* @var $currentStylePlugin \Drupal\views\Plugin\views\style\DefaultStyle */
      $currentStylePlugin = $view->getStyle();
      $rendered_fields = [];
      foreach ($view->result as $index => $view_result) {
        foreach ($view->field as $field_name => $field) {
          $rendered_fields[$index][$field_name] = $currentStylePlugin->getField($index, $field_name);
        }
      }
      if ($data = $this->getData($rendered_fields, $search)) {
        // @todo catch display even if no results for header and footer.
        // Add Header if exist.
        if (!empty($view->display_handler->options['header'])) {
          $header = $this->formatSpecialRow('header', $view->display_handler->renderArea('header'), $search);
          // Insert header.
          if (!empty($header)) {
            array_unshift($data, implode(PHP_EOL, $header));
          }
        }
        // Add Header if exist.
        if (!empty($view->display_handler->options['footer'])) {
          $footer = $this->formatSpecialRow('footer', $view->display_handler->renderArea('footer'), $search);
          if (!empty($footer)) {
            $data[] = implode(PHP_EOL, $footer);
          }
        }
        $view_data = array_merge($view_data, $data);
      }
      elseif (!empty($view->display_handler->options['empty'])) {
        $empty = $this->formatSpecialRow('empty', $view->display_handler->renderArea('empty'), $search);
        if (!empty($empty)) {
          $view_data = array_merge($view_data, [implode(PHP_EOL, $empty)]);
        }
      }
    }
    return new JsonResponse($view_data);
  }

  /**
   * Get header of the view if exist.
   *
   * @param string $type
   *   Type request i.e header or footer.
   * @param array $data_views
   *   The header of the view.
   * @param string $search
   *   The search text.
   *
   * @return array
   *   An array of views data header.
   */
  protected function formatSpecialRow($type, array $data_views, $search) {
    if (empty($data_views)) {
      return [];
    }
    $view_data_formatted = [];
    $row = '';
    foreach ($data_views as $area) {
      $value = $this->renderer->render($area);
      if ($value instanceof Markup) {
        $value = $value->__toString();
      }
      if (!empty($value) && strpos($value, '[autocomplete]')) {
        $value = str_replace('[autocomplete]', $search, $value);
      }
      $row .= $value;
    }
    $element = [
      '#theme' => 'views_autocomplete_api_special_row',
      '#type_group' => $type,
      '#row' => $row,
    ];
    $view_data_formatted[] = $this->renderer->render($element);

    return $view_data_formatted;
  }

  /**
   * Re-format the views data rendered.
   *
   * @param array $rendered_fields
   *   An array of rendered field.
   *
   * @return array
   *   An array wuth the data of views (the last and before last row).
   */
  protected function getData(array $rendered_fields, $search) {
    $view_data_formatted = [];
    // The String Which search for.
    foreach ($rendered_fields as $row) {
      // Content of rendered fields.
      $row_values = array_values($row);
      $count = count($row_values);

      $key = $rendered = $row_values[count($row) - 2];
      // Take the last field to allow to call more that one and
      // "Rewrite field" and call them all.
      if ($count > 1) {
        $rendered = $row_values[$count - 1];
      }
      // We doesn't allow html for key input.
      $viewData['value'] = strip_tags($key);
      // Highlight search word.
      if ($this->configFactory->get('views_autocomplete_api.settings')
          ->get('highlight') == TRUE
      ) {
        $rendered = $this->highlightStr($rendered, $search);
      }
      $viewData['label'] = $rendered;
      $view_data_formatted[] = $viewData;
    }

    return $view_data_formatted;
  }

  /**
   * Higlight string searched.
   *
   * @param $haystack
   * @param $needle
   * @return $haystack
   */
  public function highlightStr($haystack, $needle) {
    // Return $haystack if there is no highlight color or strings given,
    // nothing to do.
    if (empty($haystack) || empty($needle)) {
      return $haystack;
    }
    $patterns = $replacements = [];
    // Old regex : "/(?![^<]*>)$needle+/i".
    // First replacement.
    // $patterns[] = "'(?!((<.*?)|(<a.*?)))($needle)(?!(([^<>]*?)>)|([^>]*?</a>))'si";
    $patterns[] = "/(?![^<]*>)$needle+/i";
    $element = [
      '#theme' => 'views_autocomplete_api_highlight',
      '#search_word' => $needle,
    ];
    $replacements[] = $this->renderer->render($element);

    // Addon check translitered search query.
    $translitered_match = $this->removeAccents($needle);
    if ($needle != $translitered_match) {
      // Old regex : "/(?![^<]*>)$translitered_match+/i".
      // $patterns[] = "'(?!((<.*?)|(<a.*?)))($translitered_match)(?!(([^<>]*?)>)|([^>]*?</a>))'si";
      $patterns[] = "/(?![^<]*>)$translitered_match+/i";
      $element = [
        '#theme' => 'views_autocomplete_api_highlight',
        '#search_word' => $translitered_match,
      ];
      $replacements[] = $this->renderer->render($element);
    }
    // Replace for highlighting.
    $haystack = preg_replace($patterns, $replacements, $haystack);

    return $haystack;
  }

  /**
   * Delete accent from string.
   *
   * @todo find way to get list on all encoding et put it in config module.
   *
   * @param string $str
   *   String to convert.
   * @param string $encoding
   *   Format encoding.
   *
   * @return string
   *   The transformed string.
   */
  public function removeAccents($str, $encoding = 'utf-8') {
    // Convert all applicable characters to HTML entities.
    $str = htmlentities($str, ENT_NOQUOTES, $encoding);

    // Replace the html entiies, to get just the first letter without accent.
    // Exemple : "&ecute;" => "e", "&Ecute;" => "E", "Ã " => "a" ...
    $str = preg_replace('#&([A-za-z])(?:acute|grave|cedil|circ|orn|ring|slash|th|tilde|uml);#', '\1', $str);

    // Replace ligatures as : Œ, Æ ...
    // Example "Å“" => "oe".
    $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str);
    // Delete other special character.
    $str = preg_replace('#&[^;]+;#', '', $str);

    return $str;
  }

}
