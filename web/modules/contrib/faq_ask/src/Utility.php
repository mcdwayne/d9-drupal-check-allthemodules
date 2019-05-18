<?php

namespace Drupal\faq_ask;

/**
 * @file
 * Contains \Drupal\faq_ask\Utility.
 */

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Url;
use Drupal\faq\FaqViewer;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Contains static helper functions for FAQ module.
 */
class Utility {

  /**
   * Identical to drupal_get_token() but without session variable and the salt.
   */
  public function faqAskGetToken($value = '') {
    return Crypt::hmacBase64($value, \Drupal::service('private_key')->get());
  }

  /**
   * Helper function to validate token.
   */
  public function faqAskValidToken($token, $value = '', $skip_anonymous = FALSE) {
    $user = \Drupal::currentUser();
    return (($skip_anonymous && $user->id() == 0) || ($token == Utility::faqAskGetToken($value)));
  }

  /**
   * Determines whether the current user has one of the given permissions.
   */
  public function faqAskUserAccessOr($string1, $string2) {
    $user = \Drupal::currentUser();
    return $user->hasPermission($string1) || $user->hasPermission($string2);
  }

  /**
   * Reassign the node to the current user and display a message.
   */
  public function faqAskReassign(&$node) {
    $user = \Drupal::currentUser();
    $node_uid = $node->get('uid')->getValue();
    $node_uid[0]['target_id'] = $user->id();
    $usetrObj = user_load($user->id());
    drupal_set_message(t('This question is being assigned to !user.', array('!user' => $usetrObj->get('name')->value)));
  }

  /**
   * This is the code to select the Unanswered Questions for the block.
   *
   * It is also used by the "unanswered" page by setting a very high limit.
   */
  public function faqAskListUnanswered($limit) {
    $user = \Drupal::currentUser();

    // Bounce anonymous users.
    if ($user->id() == 0) {
      // If this is a block.
      if ($limit < 1000) {
        // Return empty content.
        return NULL;
      }
      else {
        // Snached from http://drupal.org/node/60148.
        drupal_set_message(t("Access Denied: Please Login"));
        // This remembers where the user is coming from.
        return new RedirectResponse(URL::fromUserInput('/user/login', array('query' => drupal_get_destination()))->toString());
      }
    }

    // What permissions does this user have.
    $can_edit = $user->hasPermission('administer faq') || $user->hasPermission('administer nodes');
    $is_expert = $user->hasPermission('answer question');

    $faq_ask_settings = \Drupal::config('faq_ask.settings');

    // Join the term_data table to select based on tid.
    $query = \Drupal::database()->select('node_field_data', 'n');
    $query->leftJoin('faq_ask_term_index', 'ti', 'n.nid = ti.nid OR ti.tid IS NULL');
    $query->addField('n', 'nid');
    $query->addField('ti', 'tid');
    $query->condition('n.status', 0);
    $query->condition('n.type', 'faq');

    $mode = 'edit';

    // Note: If the admin is also an expert, the expert-ness prevails.
    if ($is_expert) {
      $mode = 'answer';

      // Get all the expert's terms into a keyed array of terms
      // indexes keyed by the term index: $terms[tid] = tid.
      $terms = \Drupal::database()->select('faq_expert', 'fe')
        ->condition('uid', $user->id())
        ->fields('fe')
        ->execute()
        ->fetchAllKeyed(1, 1);

      // Check if this expert has any categories.
      if (count($terms) == 0) {
        if ($limit > 1000) {
          return '<p>' . t("For some strange reason, I couldn't find any categories for you.") . '</p>';
        }
        else {
          return NULL;
        }
      }

      // Find the nodes that are in our terms or does not have a term.
      $query->condition(db_or()->condition('tid', $terms, 'IN')->isNull('tid'));
    }
    elseif (!$can_edit) {
      // If not expert and cannot edit the node by permission - edit own.
      // AND n.uid = $user->uid (the user and the node owner are the same).
      $query->condition('n.uid', $user->id());
    }
    // A high limit means we are doing the "unanswered" page.
    if ($limit < 1000) {
      // Find the total number of items w/o limit.
      $totalcount = $query->countQuery()->execute()->fetchField();
      // We are only displaying a block.
      $query->range(0, $limit);
      $query->orderBy('n.created');
      // Get nids.
      $nids = $query->execute()->fetchCol();
      if ($totalcount) {
        // Output via theme each block of nodes.
        $markup = array(
          '#theme' => 'faq_ask_unanswered_block',
          '#data' => $nids,
          '#more_link' => $totalcount > $limit,
          '#mode' => $mode,
        );
        return drupal_render($markup);
      }
      else {
        return '';
      }
    }

    $query->orderBy('tid');

    // Only need the nid column.
    $result = $query->execute()->fetchAllKeyed();

    $data = array();

    // Rearrange so that we have an array indexed by tid => array(nids).
    foreach ($result as $nid => $tid) {
      if (empty($data[$tid])) {
        $data[$tid] = array();
      }
      $data[$tid][] = $nid;
    }
    foreach ($data as $tid => $nodes) {
      // Output via theme each block of nodes.
      $markup = array(
        '#theme' => 'faq_ask_unanswered',
        '#data' => $nodes,
        '#term' => $tid,
        '#mode' => $mode,
      );
    }
    return drupal_render($markup);
  }

  /**
   * Helper function to set a notification associated with a node.
   */
  public function faqAskSetFaqNotification($nid, $email) {

    if (!$nid) {
      drupal_set_message(t('Attempt to insert notification to @email for no node ID. Insert failed.', array('@email' => $email)), 'error');
      return;
    }

    \Drupal::database()->insert('faq_ask_notify')
      ->fields(array(
        'nid' => $nid,
        'email' => $email,
      ))
      ->execute();
  }

  /**
   * Helper function to remove a notification from a question.
   */
  public function faqAskDeleteFaqNotification($nid) {
    $deleted = \Drupal::database()->delete('faq_ask_notify')->condition('nid', $nid)->execute();
    if ($deleted == 0) {
      drupal_set_message(t('Attempt to delete email notification failed.'), 'error');
    }
  }

  /**
   * Helper function to fetch an email for notification assigned to an faq node.
   */
  public function faqAskGetFaqNotificationEmail($nid) {

    return \Drupal::database()->select('faq_ask_notify', 'fan')
        ->fields('fan', array('email'))
        ->condition('nid', $nid)
        ->execute()
        ->fetchField();
  }

  /**
   * Helper function to get FAQ_Ask term.
   *
   * Get the term id's related to a node or a form posting
   * Returns an array of all term ids of a node if the terms
   * are part of the vocabularies selected for FAQ-Ask.
   * If no terms then an array with a single 0 as term id is returned.
   */
  public function faqAskGetTerms($data) {
    $category = array();

    // Get fields relevant for the faq node.
    $fields = \Drupal::entityManager()->getFieldDefinitions('node', 'faq');
    foreach ($fields as $name => $properties) {
      if (!empty($properties->getTargetBundle())) {
        $fieldSettings = $properties->getSettings();
        if ($fieldSettings['handler'] == 'default:taxonomy_term') {
          $fields_new[$name] = $properties;
        }
      }
    }

    // Parse through all tagging fields in use.
    foreach ($fields_new as $field_name => $field_details) {
      if (!empty($properties->getTargetBundle())) {
        // If we have terms defined.
        $faq_term = $data->get('field_faq_category')->getValue();

        if (is_array($faq_term)) {
          // Cycle through terms.
          foreach ($faq_term as $term) {
            // If there is a term tid defined and it is an int.
            if (isset($term['target_id']) && is_int((int) $term['target_id'])) {
              $category[$term['target_id']] = taxonomy_term_load($term['target_id']);
            }
          }
        }
      }

      if (empty($category)) {
        $category[] = '0';
      }
      return $category;
    }
  }

  /**
   * Create a categorized list of nodes that are not answered.
   */
  public function faqAskUnansweredBuild(&$variables) {
    $data = $variables['data'];
    // Fetch the term from term id.
    $tid = $variables['term'];
    $term = taxonomy_term_load($tid);
    $class = $variables['class'];
    $mode = $variables['mode'];

    // Get number of questions, and account for hidden sub-categories.
    $count = count($data);

    // Module Handler.
    $moduleHandler = \Drupal::moduleHandler();

    // Get taxonomy image.
    $variables['term_image'] = '';
    if ($moduleHandler->moduleExists('taxonomy_image')) {
      $variables['term_image'] = taxonomy_image_display($term->id(), array('class' => 'faq-tax-image'));
    }

    // Configure header.
    if (is_object($term)) {

      $variables['category_depth'] = (!empty($term->depth) ? $term->depth : 1);
      $variables['category_name'] = SafeMarkup::checkPlain($term->getName());
      $variables['header_title'] = SafeMarkup::checkPlain($term->getName());

      // Configure category description.
      $variables['description'] = SafeMarkup::checkPlain($term->get('description')->value);
    }
    else {
      $variables['category_depth'] = 1;
      $variables['category_name'] = t('Uncategorized');
      $variables['header_title'] = t('Uncategorized');

      // Configure category description.
      $variables['description'] = t('Nodes that are not assigned to any category yet. This must be done when answering the question.');
    }

    // Configure class (faq-qa or faq-qa-hide).
    $variables['container_class'] = 'faq-qa';

    if (!count($data)) {
      $variables['question_count'] = $count;
      $variables['nodes'] = array();
      return;
    }
    $language = \Drupal::languageManager()->getCurrentLanguage();
    $nodes = array();
    foreach ($data as $nid) {
      $node = node_load($nid);
      $node_var = array();
      $anchor = 't' . $tid . 'n' . $node->id();
      FaqViewer::viewQuestion($node_var, $node, '1', $anchor);
      entity_view($node, 'teaser', $language);

      // Add "edit answer" link if they have the correct permissions.
      if (faq_ask_node_access($node, 'update')) {
        $node->content['links']['node'][] = array(
          'title' => 'Edit answer',
          'href' => Url::fromUserInput("/node/" . $node->id() . "/edit", array("query" => drupal_get_destination()))->toString(),
        );
      }

      // Add "answer question" link if they have the correct permissions.
      if (\Drupal::currentUser()->hasPermission('answer question')) {
        $token = Utility::faqAskGetToken('faq_ask/answer/' . $nid);
        $node->content['links']['node'][] = array(
          'title' => 'Answer question',
          'href' => Url::fromUserInput('/faq_ask/' . $mode . '/' . $node->id(), array("query" => array('token' => $token)))->toString(),
        );
      }

      $build = $node->content;
      // We don't need duplicate rendering info in node->content.
      unset($node->content);

      $build += array(
        '#theme' => 'node',
        '#node' => $node,
        '#view_mode' => 'teaser',
        '#language' => $language,
      );

      // Add contextual links for this node.
      $build['#contextual_links']['node'] = array('node', array($node->id()));

      $node_links = $build['links']['node'];
      unset($build['links']);
      // We don't want node title displayed.
      unset($build['#theme']);
      $node_var['body'] = drupal_render($build);

      $node_var['links'] = $node_links;

      $nodes[] = $node_var;
    }

    $variables['nodes'] = $nodes;
    $variables['question_count'] = $count;
  }

  /**
   * Helper function to get Build block.
   *
   * This function construct block data for unanswered questions.
   */
  public function faqAskUnansweredBlockBuild(&$variables) {
    $data = $variables['data'];
    $mode = $variables['mode'];
    $more_link = $variables['more_link'];

    $items = array();
    foreach ($data as $nid) {
      $items[] = Utility::faqAskAnswerlink($nid, $mode);
    }

    $variables['items'] = $items;
    if ($more_link) {
      $variables['links']['title'] = 'more...';
      $variables['links']['href'] = '/faq_ask/unanswered';
    }
  }

  /**
   * Helper function to create a link to unanswered nodes.
   *
   * It generates by using the token verification.
   */
  public function faqAskAnswerlink($node, $mode) {
    if (!is_object($node)) {
      $node = node_load($node);
    }
    $nid = $node->id();

    // Create token to enable instant answering of unanswered questions.
    $token = Utility::faqAskGetToken('faq_ask/answer/' . $nid);
    $item_return = array();
    // Allow for edit mode in link to the unanswered questions if in edit mode.
    if ($mode == 'edit') {
      $item_return['title'] = $node->get('title')->value;
      $item_return['href'] = Url::fromUserInput("/faq_ask/edit/" . $node->id(), array("query" => array('ask' => TRUE)))->toString();
    }
    elseif ($mode == 'answer') {
      $item_return['title'] = $node->get('title')->value;
      $item_return['href'] = Url::fromUserInput("/faq_ask/answer/" . $node->id(), array("query" => array('token' => $token)))->toString();
    }
    return $item_return;
  }

}
