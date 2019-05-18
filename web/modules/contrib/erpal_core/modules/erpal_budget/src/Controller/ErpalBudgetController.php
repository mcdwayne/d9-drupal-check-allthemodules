<?php

/**
 * @file
 * Contains \Drupal\erpal_budget\Controller\ErpalBudgetController.
 */

namespace Drupal\erpal_budget\Controller;

use Drupal\Component\Utility\String;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\erpal_budget\ErpalBudgetTypeInterface;
use Drupal\erpal_budget\ErpalBudgetInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for erpal_budget routes.
 */
class ErpalBudgetController extends ControllerBase implements ContainerInjectionInterface {


  /**
   * Displays add content links for available content types.
   *
   * Redirects to erpal_budget/add/[type] if only one content type is available.
   *
   * @return array
   *   A render array for a list of the erpal_budget types that can be added; however,
   *   if there is only one erpal_budget type defined for the site, the function
   *   redirects to the erpal_budget add page for that one erpal_budget type and does not return
   *   at all.
   *
   * @see node_menu()
   */
  public function addPage() {
    $content = array();

    // Only use erpal_budget types the user has access to.
    foreach ($this->entityManager()->getStorage('erpal_budget_type')->loadMultiple() as $type) {
      if ($this->entityManager()->getAccessControlHandler('erpal_budget')->createAccess($type->id)) {
        $content[$type->id] = $type;
      }
    }

    // Bypass the erpal_budget/add listing if only one content type is available.
    if (count($content) == 1) {
      $type = array_shift($content);
      return $this->redirect('erpal_budget.add', array('erpal_budget_type' => $type->id));
    }
    return array(
      '#theme' => 'erpal_budget_add_list',
      '#content' => $content,
    );
  }

  /**
   * Provides the Erpal budget submission form.
   *
   * @param \Drupal\erpal_budget\ErpalBudgetTypeInterface $erpal_budget_type
   *   The node type entity for the node.
   *
   * @return array
   *   A ERPAL budget submission form.
   */
  public function add(ErpalBudgetTypeInterface $erpal_budget_type) {
    $erpal_budget = $this->entityManager()->getStorage('erpal_budget')->create(array(
      'type' => $erpal_budget_type->id,
    ));

    $form = $this->entityFormBuilder()->getForm($erpal_budget);

    return $form;
  }

  /**
   * The _title_callback for the erpal_budget.add route.
   *
   * @param \Drupal\erpal_budget\ErpalBudgetTypeInterface $erpal_budget_type
   *   The current node.
   *
   * @return string
   *   The page title.
   */
  public function addPageTitle(ErpalBudgetTypeInterface $erpal_budget_type) {
    return $this->t('Create @name', array('@name' => $erpal_budget_type->name));
  }
}
