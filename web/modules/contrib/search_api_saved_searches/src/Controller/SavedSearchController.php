<?php

namespace Drupal\search_api_saved_searches\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\search_api_saved_searches\SavedSearchInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides routes related to saved searches.
 */
class SavedSearchController extends ControllerBase {

  /**
   * Redirects to the search page for the given saved search.
   *
   * @param \Drupal\search_api_saved_searches\SavedSearchInterface $search_api_saved_search
   *   The saved search.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect to the search page.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Thrown if the search didn't specify a search page path.
   */
  public function viewSearch(SavedSearchInterface $search_api_saved_search) {
    $path = $search_api_saved_search->getPath();
    if (!$path) {
      throw new NotFoundHttpException();
    }
    $url = Url::fromUserInput($path, ['absolute' => TRUE]);
    return new RedirectResponse($url->toString(), 302);
  }

  /**
   * Activates a (currently disabled) saved search.
   *
   * @param \Drupal\search_api_saved_searches\SavedSearchInterface $search_api_saved_search
   *   The saved search to activate.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect to either the search page or the site's front page.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown if saving the saved search failed.
   */
  public function activateSearch(SavedSearchInterface $search_api_saved_search) {
    // Not possible for saved searches that are already active.
    if ($search_api_saved_search->get('status')->value) {
      throw new NotFoundHttpException();
    }

    $search_api_saved_search->set('status', TRUE)->save();
    $this->messenger()->addStatus($this->t('Your saved search was successfully activated.'));

    $path = $search_api_saved_search->getPath();
    if (!$path) {
      $url = Url::fromUri('internal:/', ['absolute' => TRUE]);
    }
    else {
      $url = Url::fromUserInput($path, ['absolute' => TRUE]);
    }
    return new RedirectResponse($url->toString(), 302);
  }

}
