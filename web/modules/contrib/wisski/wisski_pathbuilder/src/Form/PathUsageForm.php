<?php
/**
 * @file
 *
 * Contains Drupal\wisski_pathbuilder\Form\PathUsageForm
 */
 
namespace Drupal\wisski_pathbuilder\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class PathUsageForm extends FormBase {

  public function getFormId() {
    return 'wisski_pathbuilder_path_usage';
  }
  
  
  public function buildForm(array $form, FormStateInterface $form_state) {
    
    $usage = \Drupal::service('wisski_pathbuilder.manager')->getOrphanedPaths();

    $table = array(
      '#type' => 'tableselect',
      '#header' => array(
        'name' => $this->t('Name'),
        'pid' => $this->t('ID'),
        'pb' => $this->t('Pathbuider'),
        'usage' => $this->t('Usage'),
      ),
      '#empty' => $this->t('No paths available'),
      '#options' => array(), // filled below
    );

    if (!empty($usage['orphaned']) || !empty($usage['semiorphaned'])) {
      
    
      $paths = entity_load_multiple('wisski_path');
      $pbs = entity_load_multiple('wisski_pathbuilder');
        
      foreach ($usage['orphaned'] as $pid) {
        $path = $paths[$pid];
        $rowid = "orphaned.none.$pid";
        $table['#options'][$rowid] = array(
          '#attributes' => array('class' => 'orphaned'),
          'name' => $path->getName(),
          'pid' => $pid,
          'pb' => '',
          'usage' => $this->t('In no Pathbuilder'),
        );
      }

      foreach ($usage['semiorphaned'] as $pid => $pbids) {
        $path = $paths[$pid];
        foreach ($pbids as $pbid) {
          $rowid = "semiorphaned.$pbid.$pid";
          $pb_info = $pbs[$pbid]->getName();
          if (isset($usage['home'][$pid])) {
            $pb_info .= '; Regular in ' . join(", ", $usage['home'][$pid]);
          }
          $table['#options'][$rowid] = array(
            '#attributes' => array('class' => 'semi-orphaned'),
            'name' => $path->getName(),
            'pid' => $pid,
            'pb' => $pb_info,
            'usage' => $this->t('In list but not in tree'),
          );
        }
      }

    }

    $form['statistics'] = array(
      '#type' => 'markup',
      '#value' => $this->t(
        'There are @o orphaned and @s semi-orphaned paths.', 
        array(
          '@o' => count($usage['orphaned']),
          '@s' => count($usage['semiorphaned']),
        )
      ),
    );
    $form['table'] = $table;
    $form['actions'] = array(
      'delete' => array(
        '#type' => 'submit',
        '#value' => $this->t('Delete checked'),
        '#submit' => ['::deleteChecked'],
      ),
    );

    return $form;
    
  }


  public function deleteChecked($form, FormStateInterface $form_state) {

    $values = array_filter($form_state->getValue('table'));
    
    $pbs = entity_load_multiple('wisski_pathbuilder');
    $pbpaths = array();
    
    $cnt_semi = 0;
    $cnt_orph = 0;
    foreach ($values as $rowid) {
      list($usage, $pbid, $pid) = explode('.', $rowid);

      if ($usage == 'semiorphaned') {
        if (!isset($pbpaths[$pbid])) {
          $pbpaths[$pbid] = $pbs[$pbid]->getPbPaths();
        }
        unset($pbpaths[$pbid][$pid]);
        $cnt_semi++;
      }
      elseif ($usage == 'orphaned') {
        $path = entity_load('wisski_path', $pid);
        if (!empty($path)) {
          $path->delete();
        }
        $cnt_orph++;
      }
    }

    // $pbpaths only has a pbid key if the pbpaths array for that pb was 
    // changed. We nee to update and save these pbs.
    foreach ($pbpaths as $pbid => $pbpath_array) {
      $pb = $pbs[$pbid];
      $pb->setPbPaths($pbpath_array);
      $pb->save();
    }
    
    drupal_set_message($this->t('@c paths have been deleted.', array('@c' => $cnt_orph)));
    drupal_set_message($this->t('@c paths have been removed from their pathbuilders.', array('@c' => $cnt_semi)));
    if ($cnt_semi) drupal_set_message($this->t('There may be paths that have become orphaned as they have been removed from their pathbuilders.'));

  }
  

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // does not occur
  }
  
}
