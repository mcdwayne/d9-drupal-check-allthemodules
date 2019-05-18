<?php

/**
 * @file
 * Contains \Drupal\monster_menus\Form\ListSitesForm.
 */

namespace Drupal\monster_menus\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class ListSitesForm extends FormBase {

  /**
   * Database Service Object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs an object.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('database'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mm_admin_list_sites';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $table = [
      '#type' => 'table',
      '#header' => [
        ['data' => $this->t('Name'), 'field' => 'name'],
        ['data' => $this->t('Current Homepage')],
        ['data' => $this->t('Operations')],
      ],
      '#rows' => [],
      '#tree' => FALSE,
    ];
    $result = $this->database->select('mm_tree', 't')
      ->fields('t', ['mmtid', 'name'])
      ->where("t.parent = 1 AND t.name NOT LIKE '.%'")
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender')
      ->orderByHeader($table['#header'])
      ->limit(20)
      ->execute();

    $destination = $this->getDestinationArray();
    foreach ($result as $site) {
      $row = [];
      $row['link'] = [
        '#markup' => Link::fromTextAndUrl(mm_content_get_name($site->mmtid), mm_content_get_mmtid_url($site->mmtid))->toString()
      ];
      $row['current'] = [
        '#type' => 'radio',
        '#title_display' => 'invisible',
        '#name' => 'current',
        '#return_value' => $site->mmtid,
        '#default_value' => mm_home_mmtid()
      ];

      $ops = [
        'edit' => [
          'title' => $this->t('edit'),
          'url' => Url::fromRoute('monster_menus.handle_page_settings', ['mm_tree' =>  $site->mmtid], array('query' => $destination)),
      ]];
      if ($site->mmtid != mm_home_mmtid()) {
        $ops['delete'] = [
          'title' => $this->t('delete'),
          'url' => Url::fromRoute('entity.mm_tree.delete_form', ['mm_tree' =>  $site->mmtid], array('query' => $destination)),
        ];
      }
      $row['ops'] = [
        '#type' => 'dropbutton',
        '#links' => $ops,
      ];
      $table[] = $row;
    }

    $form[] = $table;
    $form[] = ['#type' => 'pager'];
    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Set the current homepage'),
      ],
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $conf_path = DrupalKernel::findSitePath(Request::createFromGlobals());
    $settings = $this->configFactory()->getEditable('monster_menus.settings');
    $list = $settings->get('pages.home_mmtid');

    $old = isset($list[$conf_path]) ? $list[$conf_path] : NULL;
    $new = $form_state->getValue('current');
    if ($old != $new) {
      $list[$conf_path] = $new;
      $settings
        ->set('pages.home_mmtid', $list)
        ->save();
      // Reset cache in mm_home_mmtid().
      mm_home_mmtid(TRUE);
      // Flush caches for homepage, old and new.
      mm_content_clear_page_cache([$old, $new]);
      // Flush menu router cache, so that the root route will be regenerated.
      mm_content_clear_routing_cache_tagged([$old, $new]);
    }

    \Drupal::messenger()->addStatus($this->t('The current site homepage has been changed.'));
  }

}
