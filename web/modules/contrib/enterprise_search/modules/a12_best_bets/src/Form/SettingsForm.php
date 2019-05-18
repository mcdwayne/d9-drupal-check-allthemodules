<?php

namespace Drupal\a12_best_bets\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use GuzzleHttp\Client;

class SettingsForm extends FormBase {
    private $terms;
    public function getFormId(){
        return 'a12_best_bets.settings_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
        $query = \Drupal::database()->select('a12_best_bets', 'a12_bb');
        $query->fields('a12_bb', ['content_id', 'query_text', 'exclude', 'environment', 'display_environment', 'weight']);
        $query->orderBy('weight');

        $this->terms = $query->execute()->fetchAll();
        $rows = array();

        $form['terms'] = array(
            '#type' => 'table',
            '#header' => array(
                $this->t('CONTENT'),
                $this->t('SEARCH QUERY TEXT'),
                $this->t('EXCLUDED FROM RESULT SET'),
                $this->t('ENVIRONMENT'),
                $this->t('WEIGHT'),
            ),
            '#rows' => $rows,
            '#empty' => $this->t('There are no terms yet'),
            '#tabledrag' => array(
              array(
                'action' => 'order',
                'relationship' => 'sibling',
                'group' => 'terms-order-weight',
              )
            ),
        );

        foreach ($this->terms as $id => $term) {
            $form['terms'][$id]['#attributes']['class'][] = 'draggable';

            $entity_id = explode("/", $term->content_id)[1];
            $entity_id = explode(":", $entity_id)[0];


            $entity = '';
            $url = '';
            switch(true) {
                case strstr($term->content_id, 'node'):
                    $url = Url::fromRoute('a12_best_bets.node_overview', array('node' => $entity_id));
                    $entity = Link::fromTextAndUrl(Node::load($entity_id)->getTitle(), $url)->toRenderable();
                    break;

                case strstr($term->content_id, 'user'):
                    $url = Url::fromRoute('a12_best_bets.user_overview', array('user' => $entity_id));
                    $entity = Link::fromTextAndUrl(User::load($entity_id)->getUsername(), $url)->toRenderable();
                    break;

                case strstr($term->content_id, 'file'):
                    $url = Url::fromRoute('a12_best_bets.file_overview', array('file' => $entity_id));
                    $entity = Link::fromTextAndUrl(File::load($entity_id)->getFilename(), $url)->toRenderable();
                    break;

            }
            $form['terms'][$id]['content'] = $entity;

            $form['terms'][$id]['query_text'] = array(
              '#plain_text' => $term->query_text,
            );

            $form['terms'][$id]['exclude'] = array(
              '#plain_text' => $term->exclude === '0' ? 'false' : 'true',
            );

            $form['terms'][$id]['environment'] = array(
              '#plain_text' => $term->display_environment
            );

            $form['terms'][$id]['#weight'] = $term->weight;

            $form['terms'][$id]['weight'] = array(
              '#type' => 'weight',
              '#title_display' => 'invisible',
              '#default_value' => $term->weight,
                // Classify the weight element for #tabledrag.
              '#attributes' => array('class' => array('terms-order-weight')),
            );
        }

        $form['actions'] = array(
          '#type' => 'actions'
        );

        $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Save Configuration'),
            '#attributes' => array(
              'class' => array("button", "button-action", "button--primary")
            ),
            '#submit' => array('::submitForm')
        );

        $form['actions']['save_config'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Deploy Elevation to A12 Find'),
            '#submit' => array('::deployToFind')
        );

        return $form;
    }


    public function submitForm(array &$form, FormStateInterface $form_state) {
        foreach($form_state->getValues()['terms'] as $index => $value) {
            $this->terms[$index]->weight = $value['weight'];

            $query = \Drupal::database()->update('a12_best_bets');
            $query->fields([
              'weight' => $value['weight']
            ]);
            $query->condition('environment', $this->terms[$index]->environment);
            $query->condition('content_id', $this->terms[$index]->content_id);
            $query->condition('query_text', $this->terms[$index]->query_text);

            $query->execute();
        }
    }

    public function deployToFind(array &$form, FormStateInterface $form_state) {
        $temp = array();

        foreach ($this->terms as $term) {
            $temp[$term->environment][$term->query_text][] = array(
                "content_id" => $term->content_id,
                "exclude" => $term->exclude,
            );
        }
        foreach($temp as $env => $queries) {
            $client = new Client();
            $response = $client->post('http://192.168.81.2' . $env,
                ['body' => $this->jsonFind($queries)]
              );
        }
    }

    private function jsonFind(&$queries) {
        $temp = array();
        foreach($queries as $query => $docs) {
            $temp[] = array('query' => $query, 'docs' => json_encode($docs));
        }

        return json_encode($temp);
    }
}