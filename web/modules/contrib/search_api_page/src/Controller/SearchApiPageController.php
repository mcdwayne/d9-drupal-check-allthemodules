<?php

namespace Drupal\search_api_page\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Language\LanguageInterface;
use Drupal\search_api\Entity\Index;
use Drupal\search_api_page\Entity\SearchApiPage;
use Symfony\Component\HttpFoundation\Request;
use Drupal\search_api\SearchApiException;

/**
 * Defines a controller to serve search pages.
 */
class SearchApiPageController extends ControllerBase {

  /**
   * Page callback.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param string $search_api_page_name
   *   The search api page name.
   * @param string $keys
   *   The search word.
   *
   * @return array
   *   The page build.
   */
  public function page(Request $request, $search_api_page_name, $keys = '') {
    $build = [];

    /* @var $search_api_page \Drupal\search_api_page\SearchApiPageInterface */
    $search_api_page = SearchApiPage::load($search_api_page_name);

    // Keys can be in the query.
    if (!$search_api_page->getCleanUrl()) {
      $keys = $request->get('keys');
    }

    // Show the search form.
    if ($search_api_page->showSearchForm()) {
      $args = [
        'search_api_page' => $search_api_page->id(),
        'keys' => $keys,
      ];
      $build['#form'] = $this->formBuilder()->getForm('Drupal\search_api_page\Form\SearchApiPageBlockForm', $args);
    }

    $perform_search = TRUE;
    if (empty($keys) && !$search_api_page->showAllResultsWhenNoSearchIsPerformed()) {
      $perform_search = FALSE;
    }

    if ($perform_search) {

      /* @var $search_api_index \Drupal\search_api\IndexInterface */
      $search_api_index = Index::load($search_api_page->getIndex());

      // Create the query.
      $query = $search_api_index->query([
        'limit' => $search_api_page->getLimit(),
        'offset' => !is_null($request->get('page')) ? $request->get('page') * $search_api_page->getLimit() : 0,
      ]);

      $query->setSearchID('search_api_page:' . $search_api_page->id());

      $parse_mode = \Drupal::getContainer()
        ->get('plugin.manager.search_api.parse_mode')
        ->createInstance('direct');
      $query->setParseMode($parse_mode);

      // Search for keys.
      if (!empty($keys)) {
        $query->keys($keys);
      }

      // Add filter for current language.
      $langcode = \Drupal::service('language_manager')
        ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
        ->getId();
      $query->setLanguages([$langcode, LanguageInterface::LANGCODE_NOT_SPECIFIED]);

      // Index fields.
      $query->setFulltextFields($search_api_page->getSearchedFields());

      $result = $query->execute();
      $items = $result->getResultItems();

      /* @var $item \Drupal\search_api\Item\ItemInterface*/
      $results = [];
      foreach ($items as $item) {

        try {
          /** @var \Drupal\Core\Entity\EntityInterface $entity */
          $entity = $item->getOriginalObject()->getValue();
        }
        catch (SearchApiException $e) {
          continue;
        }
        if (!$entity) {
          continue;
        }

        // Render as view modes.
        if ($search_api_page->renderAsViewModes()) {
          $key = 'entity:' . $entity->getEntityTypeId() . '_' . $entity->bundle();
          $view_mode_configuration = $search_api_page->getViewModeConfiguration();
          $view_mode = isset($view_mode_configuration[$key]) ? $view_mode_configuration[$key] : 'default';
          $results[] = $this->entityTypeManager()->getViewBuilder($entity->getEntityTypeId())->view($entity, $view_mode);
        }

        // Render as snippets.
        if ($search_api_page->renderAsSnippets()) {
          $results[] = [
            '#theme' => 'search_api_page_result',
            '#item' => $item,
            '#entity' => $entity,
          ];
        }
      }

      if (!empty($results)) {

        $build['#search_title'] = [
          '#markup' => $this->t('Search results'),
        ];

        $build['#no_of_results'] = [
          '#markup' => $this->formatPlural($result->getResultCount(), '1 result found', '@count results found'),
        ];

        $build['#results'] = $results;

        // Build pager.
        pager_default_initialize($result->getResultCount(), $search_api_page->getLimit());
        $build['#pager'] = [
          '#type' => 'pager',
        ];
      }
      elseif ($perform_search) {
        $build['#no_results_found'] = [
          '#markup' => $this->t('Your search yielded no results.'),
        ];

        $build['#search_help'] = [
          '#markup' => $this->t('<ul>
<li>Check if your spelling is correct.</li>
<li>Remove quotes around phrases to search for each word individually. <em>bike shed</em> will often show more results than <em>&quot;bike shed&quot;</em>.</li>
<li>Consider loosening your query with <em>OR</em>. <em>bike OR shed</em> will often show more results than <em>bike shed</em>.</li>
</ul>'),
        ];
      }
    }

    $build['#theme'] = 'search_api_page';

    // Let other modules alter the search page.
    \Drupal::moduleHandler()->alter('search_api_page', $build, $result);

    // TODO caching dependencies.
    return $build;
  }

  /**
   * Title callback.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param string $search_api_page_name
   *   The search api page name.
   * @param string $keys
   *   The search word.
   *
   * @return string
   *   The page title.
   */
  public function title(Request $request, $search_api_page_name = NULL, $keys = '') {
    // Provide a default title if no search API page name is provided.
    if ($search_api_page_name === NULL) {
      return $this->t('Search');
    }

    /* @var $search_api_page \Drupal\search_api_page\SearchApiPageInterface */
    $search_api_page = SearchApiPage::load($search_api_page_name);
    return $search_api_page->label();
  }

}
