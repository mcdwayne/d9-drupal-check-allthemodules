<?php

namespace Drupal\a12_best_bets\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\enterprise_search\Utility\A12Utils;

class A12BestBetsTermAddBase extends FormBase {
    private $entity_type_id;
    private $entity_id;

    public function getFormId() {
        return 'a12_best_bets.term_add.change.me';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
        $this->entity_type_id = explode("/", \Drupal::request()->getRequestUri())[1];
        if ($this->entity_type_id == 'taxonomy')
            $this->entity_type_id = 'taxonomy_term';

        $this->entity_id = \Drupal::request()->attributes->get($this->entity_type_id);
        $form['query_text'] = array(
          '#type' => 'textfield',
          '#title' => t('Search query text'),
          '#description' => t('Search queries matching this text will show the content at the top of the search result set.'),
          '#maxlength' => 64,
          '#weight' => 0
        );
        $servers = \Drupal\search_api\Entity\Server::loadMultiple();

        foreach ($servers as $server) {
            if (is_a($server->getBackend(), 'Drupal\enterprise_search\Plugin\search_api\backend\EnterpriseSearchApiPlugin')) {
                $options[$server->getBackend()->getConfiguration()['connector_config']['path']] = $server->get('name');
                if (sizeof($options) == 1) {
                    $indexes = array();
                    foreach ($server->getIndexes()  as $index) {
                        $indexes[$index->get('id')] = $index->get('name');
                    }

                    $form['index'] = array(
                      '#type' => 'select',
                      '#title' => $this->t('Search index'),
                      '#options' => $indexes,
                      '#weight' => 2
                    );
                }
            }
        }

        $form['environment'] = array(
          '#type' => 'select',
          '#title' => t('Search server'),
          '#options' => $options,
          '#description' => t('The search environment the best bet will be applied to.'),
          '#weight' => 1
        );

        $form['exclude'] = array(
          '#type' => 'select',
          '#title' => t('Exclude from results'),
          '#options' => array(
            0 => t('No'),
            1 => t('Yes'),
          ),
          '#description' => t('Exclude this content from searches queries matching the entered text.'),
          '#weight' => 3
        );

        $form['actions'] = array(
          '#weight' => 4
        );

        $form['actions']['submit'] = array(
          '#type' => 'submit',
          '#value' => $this->t('Add as best bet for search query'),

        );

        $href = Url::fromRoute(
          sprintf('a12_best_bets.%s_overview', $this->entity_type_id),
          array($this->entity_type_id => $this->entity_id)
        )->getInternalPath();
        $form['actions']['cancel'] = array(
          '#type' => 'html_tag',
          '#tag' => 'a',
          '#value' =>$this->t('Cancel'),
          '#attributes' => array(
            'title' => t('Return to the best bet overview page.'),
            'href' => '/' . $href
          )
        );

        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        $values = $form_state->getValues();

        $query = \Drupal::database()->insert('a12_best_bets')
          ->fields(
            array(
              'environment' => $values['environment'],
              'display_environment' => $form['environment']['#options'][$values['environment']],
              'content_id' => A12Utils::generateNodeSolrId($this->entity_type_id, $values['index']),
              'query_text' => $values['query_text'],
              'exclude' => $values['exclude']
            )
          );

        $query->execute();

        $route_name = sprintf('a12_best_bets.%s_overview', $this->entity_type_id);
        $form_state->setRedirect($route_name,
          array(
            $this->entity_type_id => $this->entity_id
          )
        );
    }
}