<?php

namespace Drupal\node_notify;

use Drupal\Core\Url;

class NotifyService
{
  /**
   * Function for sending notification email.
   */
  public function sendEmail(){

    // Get expired nodes.
    $nodes = $this->getExpiredNode();
    $config = \Drupal::config('node_notify.settings');
    $token_service = \Drupal::token();
    $mailManager = \Drupal::service('plugin.manager.mail');
    $author_check = $config->get('send_email_to_author');

    if($nodes) {
      foreach($nodes as $node) {
        $node_data = $this->getNodeData($node['id']);
        $langcode = \Drupal::currentUser()->getPreferredLangcode();

        // Reassign string with token.
        $subject = $config->get('mail_subject');
        $body = $config->get('mail_body');

        // Replace token with string.
        $subject = $token_service->replace($subject, $node_data);
        $body = $token_service->replace($body, $node_data);

        // Check is author is set.
        if($author_check) {
          $emails = explode(',',$node['email']);

          // Send multiple emails.
          foreach($emails as $email) {
            $to = $email;
            $params['message'] = $body;
            $params['title'] = $subject;

            // Send mail with content
            $result = $mailManager->mail('node_notify', 'notify', $to, $langcode, $params, NULL, true);

            // Error handling for mail
            if ($result['result'] == true) {

              $message = $this->t('An email notification has been sent to @to', array('to'=>$to));
              \Drupal::messenger()->addMessage($message);
              \Drupal::logger('mail-log')->notice($message);

              // Update table
              $this->updateNodeStatus($node['id']);

            } else {

              // Set drupal message.
              $message = $this->t('There was a problem sending email notification to @to', array('to'=>$to));
              \Drupal::messenger()->addMessage($message, 'error');
              \Drupal::logger('mail-log')->error($message);
            }
          }

          // only single email is need to send.
        } else {
          $to = $node['email'];
          $params['message'] = $body;
          $params['title'] = $subject;

          // Send mail with content
          $result = $mailManager->mail('node_notify', 'notify', $to, $langcode, $params, NULL, true);

          // Error handling for mail
          if ($result['result'] == true) {

            $message = $this->t('An email notification has been sent to @to', array('to'=>$to));
            \Drupal::messenger()->addMessage($message);
            \Drupal::logger('mail-log')->notice($message);

            // Update table
            $this->updateNodeStatus($node['id']);

          } else {

            // Set drupal message.
            $message = $this->t('There was a problem sending email notification to @to', array('to'=>$to));
            \Drupal::messenger()->addMessage($message, 'error');
            \Drupal::logger('mail-log')->error($message);
          }
        }

      }
    }
  }

  /**
   * Function used to retrieve node expired on date.
   */
  public function getExpiredNode() {

    $db_connection = \Drupal::database();
    if ($db_connection->schema()->tableExists('node_notify')) {

      // Get current date
      $date = date("Y-m-d");
      $days = \Drupal::config('node_notify.settings')->get('days');
      if($days != '') {
        $date = strtotime('+'.$days.' days', strtotime($date));
        $date = date("Y-m-d", $date);
      }

      $query = \Drupal::database()->select('node_notify', 'nn');
      $query->addField('nn', 'id');
      $query->addField('nn', 'email');
      $query->condition('nn.date', $date, '<=');
      $query->condition('nn.status', 0);
      $data = $query->execute()->fetchAll();
      $expired_node = [];
      if(!empty($date)) {
        foreach ($data as $key => $value ) {
          $expired_node[] = ['id' => $value->id,
            'email' => $value->email];
        }
        return $expired_node;
      }
    }
    return NULL;
  }

  /**
   * Get Node data used in email.
   * @param $nid
   *  Node id.
   * @return array
   *  Data used in email.
   */
  public function getNodeData($nid){

    $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
    $data = [];
    if($node) {

      $data['title'] = $node->get('title')->getString();

      // Get url alias of node.
      $options = ['absolute' => TRUE];
      $url_object = Url::fromRoute('entity.node.canonical', ['node' => $nid], $options);
      $data['url'] = $url_object->toString();

      // Get edit of node.
      $edit_link_object = $node->toUrl("edit-form", $options);
      $data['url_edit'] = $edit_link_object->toString();

    }
    return $data;
  }

  /**
   * Update table after email was send.
   * @param $nid
   *  Node id.
   */
  public function updateNodeStatus($nid) {

    $db_connection = \Drupal::database();
    $row = ['status' => 1];
    $db_connection->update('node_notify')
      ->fields($row)
      ->condition('id', $nid)
      ->execute();
  }

}
