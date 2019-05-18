<?php

/**
 * @file
 * Contains \Drupal\icecast_importer\Form\ImporterForm.
 */

namespace Drupal\icecast_importer\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Exception\RequestException;
use Drupal\node\Entity\Node;

class ImporterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'icecast_importer_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['url_xspf'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('The xspf url'),
      '#description' => $this->t('Paste the xspf location here.'),
      '#required' => TRUE,
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $url = $form_state->getValue('url_xspf');
    $client = \Drupal::httpClient();
    $method = 'GET';
    try {
      $response = $client->request($method, $url);
      $code = $response->getStatusCode();
      if ($code == 200) {
        $body = $response->getBody()->getContents();
        $xml = preg_replace('/&(?!#?[a-z0-9]+;)/', '&amp;', $body);
        $obj = simplexml_load_string($xml);
        $data = json_decode(json_encode($obj), TRUE);
        $title = $data['title'];

        $node_type = $this->config('icecast_importer.settings')->get('node_type');

        $query = \Drupal::entityQuery('node')
            ->condition('title', $title, '=')
            ->condition('type', $node_type)
        //->condition('field_tags.entity.name', 'cats')
        ;

        $nids = $query->execute();
        if (count($nids) === 0) {
          $form_state->setValue('data', $data);
        }
        else {
          $form_state->setErrorByName('url_xspf', $this->t('Title already exists, maybe duplicate.'));
        }
      }
    } catch (RequestException $e) {
      $form_state->setErrorByName('url_xspf', $e->getMessage());
      //watchdog_exception('custom_modulename', $e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $data = $form_state->getValue('data');
    $locations = array();
    foreach ($data['trackList']['track'] as $track) {
      if (is_array($track)) {
        $locations[] = $track['location'];
      } else {
        $locations[] = $track;
      }
    }

    $node_type = $this->config('icecast_importer.settings')->get('node_type');

    $node = \Drupal::entityTypeManager()->getStorage('node')->create([
          'type' => $node_type,
          'title' => $data['title'],
          'field_info' => $data['info'],
          'field_track' => $locations,
    ]);
    $node->save();

    drupal_set_message($this->t('The content %title has been added.', array('%title' => $data['title'])));
  }

}
