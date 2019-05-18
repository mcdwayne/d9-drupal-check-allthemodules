<?php

/**
 * @file
 * Contains \Drupal\monster_menus\Form\FixNodeUrlsForm.
 */

namespace Drupal\monster_menus\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Condition;
use Drupal\monster_menus\Constants;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FixNodeUrlsForm extends FormBase {

  /**
   * The database connection.
   *
   * @var Connection
   */
  protected $database;

  /**
   * Constructs a DefaultController object.
   *
   * @param Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mm_admin_fix_node_urls';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['old'] = array(
      '#type' => 'textfield',
      '#required' => TRUE,
      '#size' => 120,
      '#maxlength' => 1024,
      '#title' => $this->t('Old path'),
      '#default_value' => isset($_SESSION['mm_fix_node_urls']['old']) ? $_SESSION['mm_fix_node_urls']['old'] : base_path() . 'foo/bar',
      '#description' => $this->t('URLs starting at this location (with or without the host name, http:, or https:) will be rewritten'),
    );
    $form['new'] = array(
      '#type' => 'mm_catlist',
      '#required' => TRUE,
      '#mm_list_min' => 1,
      '#mm_list_max' => 1,
      '#mm_list_selectable' => '',
      '#title' => $this->t('New location of the above path:'),
      '#default_value' => isset($_SESSION['mm_fix_node_urls']['new']) ? $_SESSION['mm_fix_node_urls']['new'] : array(),
      '#description' => $this->t('Rewrite starting at this location.'),
    );
    $state = \Drupal::state();
    $form['advanced'] = array(
      '#title' => $this->t('Advanced options'),
      '#type' => 'details',
      '#description' => $this->t('These settings are saved as the default for all future uses.'),
      'hostname_regex' => array(
        '#type' => 'textfield',
        '#size' => 120,
        '#maxlength' => 1024,
        '#title' => $this->t('Regular expression to match host name'),
        '#description' => $this->t('When searching for URLs to change, this regular expression is used to match host names. If your web server might be referred to by multiple host names in absolute URLs, this can be used to rewrite all of them to a single value. If left blank, the current host name will be used. Any dots must be escaped as: <code>\\.</code>'),
        '#default_value' => $state->get('monster_menus.fix_node_urls_hostname_regex', preg_quote(\Drupal::request()->server->get('HTTP_HOST', ''))),
      ),
      'chunksize' => array(
        '#type' => 'number',
        '#required' => TRUE,
        '#size' => 5,
        '#title' => $this->t('Number of nodes to scan per run'),
        '#description' => $this->t('The number of nodes to search per AJAX call. Increasing this number can improve efficiency, but setting it too high can result in timeouts, especially when the <em>Fix URLs</em> button is used.'),
        '#default_value' => $state->get('monster_menus.fix_node_urls_chunksize', 50),
        '#min' => 1,
      ),
    );

    $form['actions'] = array(
      '#type' => 'actions',
      'test' => array(
        '#type' => 'submit',
        '#value' => $this->t('Test Fixing of URLs'),
      ),
      'go' => array(
        '#type' => 'submit',
        '#value' => $this->t('Fix URLs'),
        '#button_type' => 'danger',
      ),
    );

    if (isset($_SESSION['mm_fix_node_urls']['result'])) {
      $form['result'] = array(
        '#type' => 'details',
        '#open' => TRUE,
        '#title' => $this->t('Result'),
        $_SESSION['mm_fix_node_urls']['result'],
      );
      unset($_SESSION['mm_fix_node_urls']['result']);
    }

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('hostname_regex')) {
      $had_exception = FALSE;
      set_error_handler(function() use (&$had_exception) {
        $had_exception = TRUE;
      });
      try {
        preg_match('{' . $form_state->getValue('hostname_regex') . '}', 'x');
      }
      catch (\Exception $e) {
        $had_exception = TRUE;
      }
      restore_error_handler();
      if (!empty($had_exception) || preg_last_error() != PREG_NO_ERROR) {
        $form_state->setErrorByName('hostname_regex', $this->t('There was an error in the regular expression you entered.'));
      }
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $vals = $form_state->getValues();
    $_SESSION['mm_fix_node_urls']['result'] = [];

    $_SESSION['mm_fix_node_urls']['old'] = $vals['old'];
    $old = preg_quote(trim($vals['old'], '/'));
    $_SESSION['mm_fix_node_urls']['new'] = $vals['new'];

    $state = \Drupal::state();
    $state->set('monster_menus.fix_node_urls_hostname_regex', $vals['hostname_regex']);
    $state->set('monster_menus.fix_node_urls_chunksize', $vals['chunksize']);

    $host = $vals['hostname_regex'];
    if (empty($host)) {
      $host = preg_quote(\Drupal::request()->server->get('HTTP_HOST', ''));
    }
    // Look for: "URL" or 'URL' with optional hostname. For unquoted URL, hostname is required.
    $re = "{([\"'])(?:https?://{$host})?/{$old}(?:/.*?)?\\1|https?://{$host}/{$old}(?:/[-.\\w]*)*}";

    reset($vals['new']);
    $new = '/mm/' . key($vals['new']);
    $write = $vals['op'] == $vals['go'];

    $batch = [
      'title' => $write ? $this->t('Updating') : $this->t('Searching'),
      'progress_message' => '@elapsed elapsed ... @estimate remaining',
      'operations' => [[[$this, 'batch'], [$re, $vals['chunksize'], $old, $new, $write]]],
      'finished' => [$this, 'finished'],
    ];
    batch_set($batch);
  }

  public function batch($re, $chunksize, $old, $new, $write, &$context) {
    // Default getter which works with most simple fields created in the usual
    // Drupal manner
    $get_default = function($node, $field) {
      return $node->{$field}->getValue();
    };

    // Default setter which works with most simple fields created in the usual
    // Drupal manner
    $set_default = function($value, NodeInterface $node, $field) {
      $node->{$field}->setValue($value);
    };

    // Body-specific getter
    $get_body = function(NodeInterface $node, $field) {
      return empty($node->body[0]->getValue()) ? NULL : $node->body[0]->getValue()[$field];
    };

    // Body-specific setter
    $set_body = function($value, NodeInterface $node, $field) {
      $all_values = $node->body[0]->getValue();
      $all_values[$field] = $value;
      $node->body[0]->setValue($all_values);
    };

    // The minimum length of the source text must be strlen($old + two quotes + initial slash)
    $sql_params = array(':length' => strlen($old) + 3);
    if (empty($context['sandbox'])) {
      $context['sandbox']['fields'] = array_merge(array(
        'value' => array(
          'table' => 'node__body',
          'join on' => '%alias.revision_id = node_field_data.vid',
          'table field' => 'body_value',
          'get' => $get_body,
          'set' => $set_body,
        ),
        'summary' => array(
          'table' => 'node__body',
          'join on' => '%alias.revision_id = node_field_data.vid',
          'table field' => 'body_summary',
          'get' => $get_body,
          'set' => $set_body,
        ),
      ), mm_module_invoke_all_array('mm_fix_node_urls_info', array()));
      $context['sandbox']['query'] = $this->database->select('node_field_data')
        ->fields('node_field_data', array('nid', 'title'));
      $joined = array('node_field_data' => 'node_field_data');
      $or = new Condition('OR');
      foreach ($context['sandbox']['fields'] as $field_name => $field_def) {
        if (!empty($field_def['table'])) {
          $join_key = $field_def['table'] . ':' . $field_def['join on'];
          if (empty($joined[$join_key])) {
            $alias = $joined[$join_key] = 't' . count($joined);
            $context['sandbox']['query']->leftJoin($field_def['table'], $alias, $field_def['join on']);
          }
          else {
            $alias = $joined[$join_key];
          }
          $aliased_field = $alias . '.' . $field_def['table field'];
          $context['sandbox']['query']->addExpression($aliased_field, $field_name);
          $or->where("LENGTH($aliased_field) >= :length", $sql_params);
          if (empty($field_def['get'])) {
            $context['sandbox']['fields'][$field_name]['get'] = $get_default;
          }
          if (empty($field_def['set'])) {
            $context['sandbox']['fields'][$field_name]['set'] = $set_default;
          }
        }
      }
      $context['sandbox']['query']->condition($or);
      $context['sandbox']['chunkpage'] = 0;
      $context['sandbox']['max'] = $context['sandbox']['query']
        ->countQuery()
        ->execute()
        ->fetchField();
      $context['results']['title'] = array();
      $context['results']['out'] = array();
      $context['results']['matched_nodes'] = 0;
      $context['results']['total_matches'] = 0;
    }
    $thischunk = 0;
    // While a REGEXP could be used here, it turns out to be much faster to read
    // all nodes with a non-empty body and do the matching in PHP.
    $query = $context['sandbox']['query']
      ->range($chunksize * $context['sandbox']['chunkpage'], $chunksize)
      ->execute();
    foreach ($query as $result) {
      $thischunk++;

      $matches = array_combine(array_keys($context['sandbox']['fields']), array_fill(0, count($context['sandbox']['fields']), array()));
      $have_match = FALSE;
      foreach (array_keys($context['sandbox']['fields']) as $field) {
        if (!empty($result->$field)) {
          $have_match |= preg_match_all($re, $result->$field, $matches[$field], PREG_SET_ORDER);
        }
      }

      if ($have_match) {
        $context['results']['matched_nodes']++;
        foreach (array_keys($context['sandbox']['fields']) as $field) {
          foreach ($matches[$field] as $n => $match) {
            if ($context['results']['total_matches'] + $n < Constants::MM_ADMIN_NODE_URL_PREVIEW_COUNT) {
              $from = !empty($match[1]) ? trim($match[0], $match[1]) : $match[0];
              $is_abs = empty($match[1]) && preg_match('{^https?:}', $from);
              $key = "$from => " . Url::fromUserInput(preg_replace("{^.*?$old}", $new, $from), ['absolute' => $is_abs])->toString();
              $context['results']['out'][$result->nid][$key] = isset($context['results']['out'][$result->nid][$key]) ? $context['results']['out'][$result->nid][$key] + 1 : 1;
              if (!isset($context['results']['title'][$result->nid])) {
                $context['results']['title'][$result->nid] = empty($result->title) ? $this->t('(untitled)') : strip_tags($result->title);
              }
            }
            else {
              break;
            }
          }
          $context['results']['total_matches'] += count($matches[$field]);
        }

        if ($write) {
          if ($node = Node::load($result->nid)) {
            $changed = FALSE;
            foreach ($context['sandbox']['fields'] as $name => $field_def) {
              if ($old_value = $field_def['get']($node, $name)) {
                $new_value = preg_replace_callback($re, function ($match) use ($new, $old) {
                  $from = !empty($match[1]) ? trim($match[0], $match[1]) : $match[0];
                  $is_abs = empty($match[1]) && preg_match('{^https?:}', $from);
                  $url = Url::fromUserInput(preg_replace("{^.*?$old}", $new, $from), ['absolute' => $is_abs])->toString();
                  if (!empty($match[1])) {
                    return $match[1] . $url . $match[1];
                  }
                  return $url;
                }, $old_value);

                if ($new_value != $old_value) {
                  $field_def['set']($new_value, $node, $name);
                  $changed = TRUE;
                }
              }
            }

            if ($changed) {
              $subst = [
                '%title' => $node->label(),
                '@old' => $old,
                '@new' => Url::fromUserInput($new)->toString(),
              ];
              $node->setNewRevision(TRUE);
              $node->setRevisionLogMessage($this->t('admin/mm/fix-nodes: Updated URLs from /@old to @new.', $subst));
              $node->keep_changed_date = TRUE;
              \Drupal::logger('content')->notice('Updated URLs in %title from /@old to @new.', $subst);
              $node->save();
            }
          }
        }
      }
    }

    $node_count = $context['sandbox']['chunkpage'] * $chunksize + $thischunk;
    $context['finished'] = $thischunk != $chunksize ? 1.0 : $node_count / $context['sandbox']['max'];
    $context['message'] = \Drupal::translation()->formatPlural($context['results']['total_matches'], 'Found 1 match in @nodes of @total nodes', 'Found @count matches in @nodes of @total nodes', array('@nodes' => $node_count, '@total' => $context['sandbox']['max']));
    $context['sandbox']['chunkpage']++;
  }

  public function finished($success, $results) {
    if ($success) {
      $over = '';
      $counter = \Drupal::translation()->formatPlural($results['matched_nodes'], ':matches in 1 node.', ':matches in @count nodes.', array(':matches' => \Drupal::translation()->formatPlural($results['total_matches'], '1 match', '@count matches')->render()))->render();
      if ($results['total_matches'] > Constants::MM_ADMIN_NODE_URL_PREVIEW_COUNT) {
        $over = $this->t('Only the first @count matches are shown.', array('@count' => Constants::MM_ADMIN_NODE_URL_PREVIEW_COUNT));
        $counter .= " $over";
      }
      $items = array();
      foreach ($results['out'] as $nid => $replaced) {
        $items[$nid] = [
          'data' => Link::fromTextAndUrl($results['title'][$nid], Url::fromRoute('entity.node.canonical', ['node' => $nid]))->toRenderable(),
          'children' => [],
        ];
        foreach ($replaced as $item => $count) {
          if ($count > 1) {
            $item .= " (x$count)";
          }
          $items[$nid]['children'][] = $item;
        }
      }
      $_SESSION['mm_fix_node_urls']['result'] = array(
        array('#markup' => $counter),
        array(
          '#theme' => 'item_list',
          '#items' => $items,
          '#title' => '',
        ),
        array('#markup' => $over),
      );
    }
  }
}
