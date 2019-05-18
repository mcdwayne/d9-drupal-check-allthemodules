<?php

namespace Drupal\rocketship\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\rocketship\Entity\Feed;
use GuzzleHttp\Client;

/**
 * Form to launch immediate processing of feeds.
 */
class ProcessForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rocketship_process_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['launch'] = array(
      '#type' => 'submit',
      '#value' => t('Process rocketship feeds now'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $client = new Client();
    $feeds = Feed::loadMultiple();
    foreach ($feeds as $feed) {
      $url = $feed->getFeedUrl();

      $response = $client->get($url);
      if ($response->getStatusCode() == 200 && ($body = (string) $response->getBody())) {
        $data = json_decode($body);
        if (!empty($data->list)) {
          foreach ($data->list as $remote_node) {
            $remote_nid = $remote_node->nid;

            if ($result = \Drupal::entityQuery('node')->condition('type', 'rocketship_issue')->condition('field_rocketship_drupal_org_nid', $remote_nid)->execute()) {
              $local_nid = current($result);
              $node = Node::load($local_nid);

              // Update the existing node.
              $node->setTitle($remote_node->title);
              $node->field_rocketship_drupal_org_nid = $remote_nid;
              $node->save();
            }
            else {
              // Create new node for this remote node.
              $node = Node::create([
                'type' => 'rocketship_issue',
                'title' => $remote_node->title,
                'field_rocketship_drupal_org_nid' => $remote_nid,
              ]);
              $node->save();
            }
          }
        }
      }
    }

    drupal_set_message($this->t('Rocketship processing complete.'));
  }

}
