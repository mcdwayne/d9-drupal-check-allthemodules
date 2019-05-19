<?php

namespace Drupal\wizenoze\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\wizenoze\Helper\WizenozeAPI;
use Drupal\wizenoze\Entity\Wizenoze;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a controller to serve search pages.
 */
class WizenozePageController extends ControllerBase {

  /**
   * Protected moduleHandler variable.
   *
   * @var module_handler
   */
  protected $moduleHandler;

  /**
   * Constructs a new WizenozePageController object.
   *
   * @param Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   */
  public function __construct(ModuleHandlerInterface $moduleHandler) {
    $this->module_handler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('module_handler')
    );
  }

  /**
   * Page callback.
   *
   * @param Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param string $wizenoze_page_name
   *   The search api page name.
   * @param string $keys
   *   The search word.
   *
   * @return array
   *   The page build.
   */
  public function page(Request $request, $wizenoze_page_name, $keys = '') {
    $output = [];

    $keys = $this->sanitize($request->get('keys'));

    /* @var $wizenoze_page \Drupal\wizenoze\WizenozePageInterface */
    $wizenoze_page = Wizenoze::load($wizenoze_page_name);

    $perform_search = FALSE;
    if (!empty($keys)) {
      $perform_search = TRUE;
    }

    if ($wizenoze_page->showAllResultsWhenNoSearchIsPerformed()) {
      $perform_search = TRUE;
    }

    if ($perform_search) {

      $wizenozeAPI = WizenozeAPI::getInstance();
      $wizenozeAPI->setCustomSearchEngineId($wizenoze_page->getIndex());

      // Create the query.
      $limit = $wizenoze_page->getLimit();

      $query = $wizenozeAPI->query([
        'q' => $keys,
        'pageSize' => $limit,
        'startPage' => !is_null($request->get('page')) ? $request->get('page') + 1 : 1,
      ]);

      $queryResult = json_decode($query->execute(), TRUE);
      $items = [];
      if (!empty($queryResult) && $queryResult['status'] == 'ok') {
        $items = $queryResult['results'];
      }
      else {
        drupal_set_message($this->t('Unable to find search results'), 'error');
      }

      $results = [];
      foreach ($items as $item) {

        if (strpos($item['sourceUrl'], 'http://') === FALSE) {
          $item['sourceUrl'] = $request->getSchemeAndHttpHost() . '/' . ltrim($item['sourceUrl'], "/");
        }
        $item['summaryText'] = html_entity_decode($item['summaryText']);
        $results[] = [
          '#theme' => 'wizenoze_page_result',
          '#item' => $item,
        ];
      }

      if (!empty($results)) {

        $output['#no_of_results'] = [
          '#markup' => $this->formatPlural($queryResult['pagination']['totalResults'], '1 result found', '@count results found'),
        ];

        $output['#results'] = $results;

        // Build pager.
        $queryResult['pagination']['totalResults'] = ($queryResult['pagination']['totalResults'] > ($limit * 100)) ? ($limit * 100) : $queryResult['pagination']['totalResults'];
        pager_default_initialize($queryResult['pagination']['totalResults'], $limit);
        $output['#pager'] = [
          '#type' => 'pager',
        ];
      }
      elseif ($perform_search) {

        $output['#no_results_found'] = [
          '#markup' => $this->t('Your search yielded no results.'),
        ];

        if (!empty($queryResult['didYouMean'])) {
          $output['#search_help'] = [
            '#markup' => $this->t('<ul>
                                        <li>Did you mean <a href="/@path/@didyoumean">@didyoumean_1 ?</a></li>
                                       </ul>', [
                                         '@path' => $wizenoze_page->getPath(),
                                         '@didyoumean' => $queryResult['didYouMean'],
                                         '@didyoumean_1' => $queryResult['didYouMean'],
                                       ]),
          ];
        }
        else {
          $output['#search_help'] = [
            '#markup' => $this->t('<ul>
    <li>Check if your spelling is correct.</li>
    <li>Remove quotes around phrases to search for each word individually. <em>bike shed</em> will often show more results than <em>&quot;bike shed&quot;</em>.</li>
    <li>Consider loosening your query with <em>OR</em>. <em>bike OR shed</em> will often show more results than <em>bike shed</em>.</li>
    </ul>'),
          ];
        }
      }
    }

    // Let other modules alter the search page.
    $this->module_handler->alter('wizenoze_search_page', $output, $queryResult);

    $output['#theme'] = 'wizenoze_page';
    // TODO caching dependencies.
    return $output;
  }

  /**
   * Title callback.
   *
   * @param Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param string $wizenoze_page_name
   *   The search api page name.
   * @param string $keys
   *   The search word.
   *
   * @return string
   *   The page title.
   */
  public function title(Request $request, $wizenoze_page_name, $keys = '') {
    /* @var $wizenoze_page \Drupal\wizenoze\WizenozePageInterface */
    $wizenoze_page = Wizenoze::load($wizenoze_page_name);
    return $wizenoze_page->label() . ' Results: ' . $this->sanitize($keys);
  }

  /**
   * Sanitize function.
   *
   * @param string $key
   *   The string to sanitize.
   *
   * @return string
   *   The sanitized String.
   */
  public function sanitize($key) {
    if (strlen($key) > 0) {
      $key = trim(strip_tags($key));
      $key = str_replace(['\r\n', '\n', ':', '\\', '/', '*', '.', '"', "'"], '', $key);
    }
    return $key;
  }

}
