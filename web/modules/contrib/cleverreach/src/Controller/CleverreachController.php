<?php

namespace Drupal\cleverreach\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;

class CleverreachController extends ControllerBase {
  
  protected $database;
  
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }
  
  public function adminOverview() {
    $group_row = array();
    $results = $this->database->query('SELECT * FROM {cleverreach_groups} g');

    foreach ($results as $result) {
      $group_row[] = array(
        array(
          'data' => array(
            '#markup' => $result->crgid,
          ),
        ),
        array(
          'data' => array(
            '#markup' => $result->name,
          ),
        ),
        array(
          'data' => array(
            '#markup' => $result->active_count,
          ),
        ),
        array(
          'data' => array(
            '#markup' => $result->inactive_count,
          ),
        ),
        array(
          'data' => array(
            '#markup' => date("Y-m-d H:i:s", $result->last_mailing),
          ),
        ),
        array(
          'data' => array(
            '#markup' => date("Y-m-d H:i:s", $result->last_changed),
          ),
        ),
      );
    }

    $group_rows = $group_row;
    $group_header = array(
      t('CR GroupID'),
      t('Name'),
      t('Active Count'),
      t('Inactive Count'),
      t('Last mailing'),
      t('Last changed'),
    );
    $block_row = array();
    $results = $this->database->query('SELECT * FROM {cleverreach_block_forms} bf');

    foreach ($results as $result) {
      $attr = '';
      $name = cleverreach_get_group_name($result->listid);
      $active = ($result->active == 1) ? t('Yes') : t('No');
      $fields = unserialize($result->fields);

      if (count($fields) > 0) {
        foreach ($fields as $value) {

          if ($value["active"] == 1) {
            $attr .= $value["name"] . ", ";
          }

        }

        $attr = substr($attr, 0, -2);
      }

      else {
        $attr = '-';
      }
      
      $links = array();
      $links['edit'] = array(
        'title' => $this->t('Edit'),
        'url' => Url::fromRoute('cleverreach.block.edit', array('bid' => $result->bid)),
      );
      $links['delete'] = array(
        'title' => $this->t('Delete'),
        'url' => Url::fromRoute('cleverreach.block.delete', array('bid' => $result->bid)),
      );
      
      $block_row[] = array(
        array(
          'data' => array(
            '#markup' => $name,
          ),
        ),
        array(
          'data' => array(
            '#markup' => $attr,
          ),
        ),
        array(
          'data' => array(
            '#markup' => $active,
          ),
        ),
        array(
          'data' => array(
            '#type' => 'operations',
            '#links' => $links,
          ),
        ),
      );  
    }

    $block_rows = $block_row;
    $block_header = array(
      t('Group'),
      t('Attributes'),
      t('Active'),
      t('Options'),
    );
    
    $build = array();
    
    $build['group_fieldset'] = array(
      '#title' => $this->t('Groups'),
      '#type' => 'fieldset',
      '#children' => array(
        '#type' => 'table',
        '#header' => $group_header,
        '#rows' => $group_rows,
        '#empty' => $this->t('No groups available.'),
      ),
    );
    $build['block_fieldset'] = array(
      '#title' => $this->t('Blocks'),
      '#type' => 'fieldset',
      '#children' => array(
        '#type' => 'table',
        '#header' => $block_header,
        '#rows' => $block_rows,
        '#empty' => $this->t('No blocks available.'),
      ),
    );
    return $build;
  }
  
}
