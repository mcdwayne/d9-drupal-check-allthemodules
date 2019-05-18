<?php
namespace Drupal\maestro\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\maestro\Utility\MaestroStatus;
use Drupal\maestro\Engine\MaestroEngine;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a Maestro block that shows the linear status output for processes this user belongs to or in general.
 *
 * @Block(
 *   id = "maestro_process_status_block",
 *   admin_label = @Translation("Maestro Process Status Block"),
 *   category = @Translation("Maestro"),
 * )
 */
class MaestroProcessStatusBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();
  
    $form['maestro_skip_execute_check'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Skip checking if the user is part of the process status bars shown.'),
      '#description' => $this->t('When checked, any open process can show its status. Unchecked means only those processes the user is a part of will be shown.'),
      '#default_value' => isset($this->configuration['maestro_skip_execute_check']) ? $this->configuration['maestro_skip_execute_check'] : 0,
      '#required' => FALSE,
    );
    
    $form['maestro_filter_process_names'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Simple filter to filter on process name.'),
      '#description' => $this->t('Specify the filter you wish to limit on. Filter uses %{your-filter}% database filtering. Available current user tokens are [uid] and [username].'),
      '#default_value' =>  isset($this->configuration['maestro_filter_process_names']) ? $this->configuration['maestro_filter_process_names'] : '',
      '#required' => FALSE,
    );
    
    $form['maestro_provide_link'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Make the Process Name a link to the following URL.'),
      '#description' => $this->t('No link when left blank. Available current user tokens are [uid] and [username].'),
      '#default_value' =>  isset($this->configuration['maestro_provide_link']) ? $this->configuration['maestro_provide_link'] : '',
      '#required' => FALSE,
    );
    
    $form['maestro_link_tooltip'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('The hover-over tooltip for the link.'),
      '#description' => $this->t('If you enter a link above, the tooltip entered here will be shown. Defaults to nothing.'),
      '#default_value' =>  isset($this->configuration['maestro_link_tooltip']) ? $this->configuration['maestro_link_tooltip'] : '',
      '#required' => FALSE,
    );
    
    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['maestro_skip_execute_check'] = $form_state->getValue('maestro_skip_execute_check');
    $this->configuration['maestro_filter_process_names'] = $form_state->getValue('maestro_filter_process_names');
    $this->configuration['maestro_provide_link'] = $form_state->getValue('maestro_provide_link');
    $this->configuration['maestro_link_tooltip'] = $form_state->getValue('maestro_link_tooltip');
  }
  
  
  /**
   * {@inheritdoc}
   */
  public function build() {
    $conf = $this->getConfiguration();
    $processID = intval(\Drupal::request()->query->get('process_id', 0));
    $usr = \Drupal::currentUser();
    if($usr->id()) {
      if($conf['maestro_skip_execute_check'] == 0) {
        $query = \Drupal::database()->select('maestro_production_assignments' , 'a');
        $query->join('maestro_queue', 'b', 'a.queue_id = b.id');
        $query->join('maestro_process', 'c', 'b.process_id = c.process_id');
        $query->leftJoin('maestro_process_variables', 'd', 'c.process_id = d.process_id');
        $andConditionGroup1 = $query->andConditionGroup();
        $andConditionGroup1->condition('a.assign_id' , $usr->getAccountName());
        $andConditionGroup1->condition('b.status' , '0');
        if(isset($conf['maestro_filter_process_names']) && $conf['maestro_filter_process_names'] != '') {
          $filter = str_replace('[uid]', $usr->id(), $conf['maestro_filter_process_names']);
          $filter = str_replace('[username]', $usr->getAccountName(), $filter);
          $andConditionGroup1->condition('c.process_name', '%' . $query->escapeLike($filter) . '%', 'LIKE');
        }
        if($processID >0 ) {
          $andConditionGroup1->condition('c.process_id', intval($processID));
        }
        $andConditionGroup2 = $query->andConditionGroup();
        $andConditionGroup2->condition('d.variable_name' , 'initiator');
        $andConditionGroup2->condition('d.variable_value' , $usr->getAccountName());
        
        $orConditionGroup = $query->orConditionGroup();
        $orConditionGroup->condition($andConditionGroup1);
        $orConditionGroup->condition($andConditionGroup2);
        
        $query->condition($orConditionGroup);
        $query->addField('b', 'process_id');
        $query->addField('c', 'process_name');
        $query->groupBy('b.process_id');
        $query->groupBy('c.process_name');
      }
      else {
        $query = \Drupal::database()->select('maestro_queue' , 'b');
        $query->join('maestro_process', 'c', 'b.process_id = c.process_id');
        $query->leftJoin('maestro_process_variables', 'd', 'c.process_id = d.process_id');
        $andConditionGroup1 = $query->andConditionGroup();
        $andConditionGroup1->condition('b.status' , '0');
        if(isset($conf['maestro_filter_process_names']) && $conf['maestro_filter_process_names'] != '') {
          $filter = str_replace('[uid]', $usr->id(), $conf['maestro_filter_process_names']);
          $filter = str_replace('[username]', $usr->getAccountName(), $filter);
          $andConditionGroup1->condition('c.process_name', '%' . $query->escapeLike($filter) . '%', 'LIKE');
        }
        if($processID >0 ) {
          $andConditionGroup1->condition('c.process_id', intval($processID));
        }
        $andConditionGroup2 = $query->andConditionGroup();
        $andConditionGroup2->condition('d.variable_name' , 'initiator');
        $andConditionGroup2->condition('d.variable_value' , $usr->getAccountName());
        
        $orConditionGroup = $query->orConditionGroup();
        $orConditionGroup->condition($andConditionGroup1);
        $orConditionGroup->condition($andConditionGroup2);
        
        $query->condition($orConditionGroup);
        $query->addField('b', 'process_id');
        $query->addField('c', 'process_name');
        $query->groupBy('b.process_id');
        $query->groupBy('c.process_name');
      }
           
      $result = $query->execute();
      $output = [];
      
      foreach($result as $row) {
        $status_bar = MaestroStatus::getMaestroStatusBar($row->process_id, 0, TRUE);
        $templateName = MaestroEngine::getTemplateIdFromProcessId($row->process_id);
        $process_name = $row->process_name;
        $link_start = '';
        $link_end = '';
        if(isset($conf['maestro_provide_link']) && $conf['maestro_provide_link'] != '') {
          $interim_link = str_replace('[uid]', $usr->id(), $conf['maestro_provide_link']);
          $interim_link = str_replace('[username]', $usr->getAccountName(), $interim_link);
          $link_start = '<a href="' . $interim_link . '" title="' . $this->t($this->configuration['maestro_link_tooltip']) . '">';
          $link_end = '</a>';
        }
        $output['status'][] = [
          '#prefix' =>  $link_start. '<div id="processid-' . $row->process_id . '" class="maestro-block-process ' . $templateName . '">
            <div class="maestro-block-process-title">' . $process_name . '</div>',
          '#suffix' => '</div>' . $link_end,
          '#markup' => $status_bar['status_bar']['#children'],
          ];
      }
      
      $output['#attached']['library'][] = 'maestro/maestro-engine-css';
      
      //Keeping the cache lines commented for now to ensure caching for this block is indeed disabled
      //$output['#cache'] = ['contexts' => ['url.query_args:process_id']];  //don't cache the process_id url parameter
      //$output['#cache']['max-age'] = 0;  //just disable the cache completely for this module
      return $output;
    }
  }
  
  /**
   * {@inheritDoc}
   * @see \Drupal\Core\Plugin\ContextAwarePluginBase::getCacheMaxAge()
   */
  public function getCacheMaxAge() {
    return 0;  //caching turned off for this block.
  }
}