<?php

namespace Drupal\taxonomy_manager_lite\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Core\Url;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * File form class.
 *
 * @ingroup taxonomy_manager_lite
 */
class TaxonomyManagerLiteAssignTaxonomyTerms extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'assign_taxonomy_terms';
  }
  
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('link_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $query = \Drupal::entityQuery('taxonomy_vocabulary');
    $all_ids = $query->execute();
    foreach (Vocabulary::loadMultiple($all_ids) as $vocabulary) {
      $vocs[$vocabulary->id()] = $this->t($vocabulary->label());
    }
    $form['taxonomy']['vocabulary'] = array(
      '#type' => 'select',
      '#title' => $this->t('Vocabulary'),
      '#name' => 'vocabulary',
      '#options' => $vocs,
      '#empty_option' => $this->t('-select-'),
      '#size' => 1,
      '#required' => TRUE,
    );
    $form['taxonomy']['validate'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#name' => 'submit',
    ];
    $form['taxonomy']['currentterm'] = [
      '#type' => 'select',
      '#name' => 'currentterm',
      '#multiple' => TRUE,
      '#title' => $this->t('Current Terms'),
      '#options' => $option,
    ];
    $form['taxonomy']['node_show_any'] = [
      '#type' => 'checkbox',
      '#name' => 'node_show_any',
      '#id' => 'node-show-any',
      '#title' => $this->t('Show nodes with any term instead of all terms'),
    ];
    $form['taxonomy']['button_preview'] = [
      '#type' => 'submit',
      '#value' => $this->t('Preview'),
      '#name' => 'preview',
    ];
    $form['taxonomy']['newterm'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#name' => 'newterm',
      '#title' => $this->t('New Terms'),
      '#options' => $option,
    ];
    $form['taxonomy']['node_term_replace'] = [
     '#type' => 'checkbox',
     '#name' => 'node_term_replace',
     '#title' => $this->t('Replace selected current terms with new terms'),
    ];
    $form['taxonomy']['button_assign'] = [
      '#type' => 'submit',
      '#value' => $this->t('Assign'),
      '#name' => 'assign',
    ];
    $form['taxonomy']['hidden'] = [
      '#type' => 'hidden',
      '#name' => 'hide',
    ];
    $form += $this->taxonomyGenerateNodeListTable($form);
    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    // Entity type.
    $vid = $values['vocabulary'];
    $ct = $values['currentterm'];
    $nt = $values['newterm'];
    $ctcond = $values['node_show_any'];
    $ntcond = $values['node_term_replace'];
    $terms = \Drupal::entityManager()->getStorage('taxonomy_term')->loadTree($vid);
    if ($terms) {
      if (!empty($terms)) {
        foreach ($terms as $key => $term) {
          $option[$term->tid] = $term->name;
        }
        $form['taxonomy']['currentterm']['#required'] = TRUE;
      }
      $form['taxonomy']['currentterm']['#options'] = $option;
      $form['taxonomy']['newterm']['#options'] = $option;
    }
    if ($ctcond) {
      $show_any = TRUE;
      $terms = $ct;
    }
    $form += $this->taxonomyGenerateNodeListTable($form, $terms, $show_any);
    if (!empty($nt)) {
      $this->taxonomyAssign($ct, $nt, $ntcond);
    }
    // Prevent submit.
    $form_state->setErrorByName('hidden','');
  }

  /**
   * {@inheritdoc}
   * 
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message($this->t('Successfully saved.'));
  }
  /**
   * Generate the node list as a table.
   */
  public function taxonomyGenerateNodeListTable(&$form, $terms = NULL, $show_any = FALSE) {
    $form['taxonomy']['nodes'] = array(
        '#type' => 'table',
        '#caption' => $this->t('Nodes List'),
        '#header' => array(
          $this->t('NID'),
          $this->t('Title'),
          $this->t('View'),
          $this->t('Created'),
        ),
      );
      $nodes = $this->taxonomyGetNodes($terms, $show_any);
      $i = 0;
      if (!empty($nodes)) {
        foreach ($nodes as $nid) {
          $i++;
          $node = \Drupal\node\Entity\Node::load($nid->nid);
          $url = Url::fromRoute('entity.node.canonical', $route_parameters = ['node' => $nid->nid]);
          $url = $this->l($this->t('View'), $url);
          $date = \Drupal::service('date.formatter')->format($node->getCreatedTime(), 'short');
          $form['taxonomy']['nodes'][$i]['nid'] = array(
            '#markup' => $nid->nid,
          );

          $form['taxonomy']['nodes'][$i]['title'] = array(
            '#markup' => $node->getTitle(),
          );
          $form['taxonomy']['nodes'][$i]['edit'] = array(
            '#markup' => $url,
          );
          $form['taxonomy']['nodes'][$i]['created'] = array(
            '#markup' => $date,
          );
        }
      }
      return $form['taxonomy']['nodes'];
  }
  
  /**
   * List the nodes with the selected terms.
   *
   * @params $terms, $show_any
   *
   * @return $output
   *   Array
   */
  public function taxonomyGetNodes($terms = NULL, $show_any = FALSE) {
    if (!$show_any) {
      $query = \Drupal::database()->select('node', 'n');
      $query->fields('n', ['nid']);
      $query->join('taxonomy_index', 'ti', 'ti.nid = n.nid');
      $result = $query->execute()->fetchAllAssoc('nid');
    }
    else {
      $query = \Drupal::database()->select('node', 'n');
      $query->fields('n', ['nid']);
      $query->join('taxonomy_index', 'ti', 'ti.nid = n.nid');
      $query->condition('ti.tid', $terms , "IN");
      $result = $query->execute()->fetchAllAssoc('nid');
    }
    return $result;
  }
  
  /**
   * Function taxonomyassign for assign taxonomy.
   *
   * @params $ct, $nt, $ntcond
   *
   */
  public function taxonomyAssign($ct, $nt, $ntcond) {
    $db = \Drupal::database();
    $result = $this->_getCurrentTermsNode($ct);
    $nids = array();
    foreach ($result as $data) {
      $nids[] = $data->nid;
    }
    foreach ($nids as $nid) {
      $node = \Drupal\node\Entity\Node::load($nid);
      foreach ($nt as $ntid) {
        $ctindex = $this->_getNewTermsNode($nt, $nid);
        $ctindex = array_values($ctindex);
        if (!in_array($ntid, $ctindex)) {
          $db->insert('taxonomy_index')->fields(
            array(
              'nid' => $nid,
              'tid' => $ntid,
              'status' => $node->isPublished(),
              'sticky' => $node->isSticky() ? 1 : 0,
              'created' => $node->getCreatedTime(),
            )
          )->execute();
        }
      }
    }
    if ($ntcond) {
      foreach ($nids as $nid) {
        $db->delete('taxonomy_index')->condition('tid', $ct , "IN")->condition('nid', $nid, "=")->execute();
      }
    }
  }
 
  /**
   * Function to get the current terms node list.
   *
   * @params $ct
   *
   * @return $result
   *   Array
   */
  public function _getCurrentTermsNode($ct) {
    $query = \Drupal::database()->select('taxonomy_index', 'ti');
    $query->fields('ti', ['nid']);
    $query->condition('ti.tid', $ct , "IN");
    $result = $query->execute()->fetchAllAssoc('nid');
    return $result;
  }
  
  /**
   * Function to get the new terms node list.
   *
   * @params $nt, $nid
   *
   * @return $result
   *   Array
   */
  public function _getNewTermsNode($nt, $nid) {
    $query = \Drupal::database()->select('taxonomy_index', 'ti');
    $query->fields('ti', ['tid']);
    $query->condition('ti.tid', $nt , "IN");
    $query->condition('ti.nid', $nid , "=");
    $result = $query->execute()->fetchCol();
    return $result;
  }
  
}
