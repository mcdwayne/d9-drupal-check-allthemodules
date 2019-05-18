<?php

namespace Drupal\faq_ask\Controller;

/**
 * @file
 * Contains \Drupal\faq\Controller\FaqAskController.
 */

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\faq_ask\Utility;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Controller routines for FAQ Ask routes.
 */
class FaqAskController extends ControllerBase {

  /**
   * Renders the form for the FAQ ASK Settings page - Experts tab.
   */
  public function expertsSettings() {
    $build = array();
    $build['faq_experts_settings_form'] = $this->formBuilder()->getForm('Drupal\faq_ask\Form\ExpertsForm');

    return $build;
  }

  /**
   * Get the FAQ ask question form.
   */
  public function askPageSettings() {
    return new RedirectResponse(URL::fromUserInput('/node/add/faq?ask=TRUE')->toString());
  }

  /**
   * This function is called when an expert selects a question to answer.
   *
   * It changes the status option to "published" then goes
   * to the regular FAQ edit function.
   */
  public function askAnswerViewSettings($nid) {
    $faq_ask_settings = \Drupal::config('faq_ask.settings');
    $user = \Drupal::currentUser();
    // If user is not logged in.
    if ($user->id() == '0') {
      // Log in first.
      $path = URL::fromUserInput('/user', array('query' => drupal_get_destination()))->toString();
      return new RedirectResponse($path);
    }
    // Validate the request.
    if (!isset($_REQUEST['token']) || !(Utility::faqAskValidToken($_REQUEST['token'], "faq_ask/answer/" . $nid))) {
      \Drupal::logger('Faq_Ask')->error("Received an invalid answer request (@query_string) from @user_ip.", array(
        '@query_string' => $_SERVER['QUERY_STRING'],
        '@user_ip' => ip_address(),
      ));
      throw new AccessDeniedHttpException();
    }
    $reassign_opt = $faq_ask_settings->get('expert_own');
    // Check if we need to reassign to the expert.
    switch ($reassign_opt) {
      case 0:
        break;

      case 1:
        if ($node->uid == 0) {
          Utility::faqAskReassign($node);
        }
        break;

      case 2:
        Utility::faqAskReassign($node);
        break;
    }

    // Change the status to published.
    $node = node_load($nid);
    $node->status->value = 1;
    $node->save();

    // Need to invoke node/#/edit.
    return new RedirectResponse(URL::fromUserInput('/node/' . $nid . '/edit')->toString());
  }

  /**
   * This function is called when an expert selects a question to answer.
   *
   * It changes the status option to "published" then goes to
   * the regular FAQ edit function.
   */
  public function askAnswerEditSettings($nid) {
    // Node object.
    $node = node_load($nid);
    if ($node->get('status')->value == 1) {
      drupal_set_message($this->t('That question has already been answered.'), 'status');
    }
    else {
      if (node_access('update', $node)) {
        // Log in first.
        $path = URL::fromUserInput('/node/' . $node->get('nid')->value . '/edit', array('query' => array('ask' => 'TRUE')))->toString();
        return new RedirectResponse($path);
      }
      else {
        drupal_set_message($this->t('You are not allowed to edit that question.'), 'error');
      }
    }
    return new RedirectResponse(URL::fromUserInput('/node')->toString());
  }

  /**
   * This function lists all the unanswered questions.
   *
   * It is used by the "more..." link from the block,
   * but can also be called independently,
   * hence user is allowed to see..
   */
  public function askUnanswerSettings() {
    $build['#markup'] = Utility::faqAskListUnanswered(9999999);
    return $build;
  }

}
