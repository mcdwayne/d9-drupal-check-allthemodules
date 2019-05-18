<?php

/**
 * @file
 *
 * Find all nodes not associated with an MM page and optionally assign them to a
 * page in /-system for possible recovery.
 */

namespace Drupal\monster_menus;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\node\Entity\NodeType;
use Drush\Drush;
use Drush\Log\LogLevel;

class CheckOrphanNodes {

  use StringTranslationTrait;

  /**
   * Send output to the current web page (do not use in a batch script). This
   * is the default method, and is used by the admin/mm/orphan-nodes menu entry.
   */
  const OUTPUT_MODE_TABLE = 'table';
  /**
   * The \Drupal::logger() function (suitable for cron)
   */
  const OUTPUT_MODE_WATCHDOG = 'watchdog';
  /**
   * Print the messages to standard i/o.
   */
  const OUTPUT_MODE_PRINT = 'print';
  /**
   * Use when called by drush.
   */
  const OUTPUT_MODE_DRUSH = 'drush';

  /**
   * The current output mode.
   *
   * @var string
   */
  private $outputMode = self::OUTPUT_MODE_TABLE;

  /**
   * The database connection.
   *
   * @var Connection
   */
  protected $database;

  /**
   * Constructs a CheckOrphanNodes object.
   *
   * @param Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * Set the output mode.
   *
   * @param string $mode
   *   One of the OUTPUT_MODE_* constants.
   * @return CheckOrphanNodes
   *   The object, for chaining.
   */
  public function setOutputMode($mode = self::OUTPUT_MODE_TABLE) {
    $this->outputMode = $mode;
    return $this;
  }

  /**
   * Returns the table column header.
   *
   * @return array
   */
  function header() {
    return array(
      array('data' => $this->t('Title'),   'field' => 'fd.title'),
      array('data' => $this->t('Type'),    'field' => 'fd.type'),
      array('data' => $this->t('Owner'),   'field' => 'fd.uid'),
      array('data' => $this->t('Created'), 'field' => 'fd.created'),
      array('data' => $this->t('Changed'), 'field' => 'fd.changed', 'sort' => 'desc'),
    );
  }

  /**
   * Find all nodes not associated with an MM page and optionally assign them to
   * a page in /-system for possible recovery.
   *
   * @param bool $fix
   *   If TRUE, associate orphans with a standard page.
   * @return array|null
   */
  function check($fix = FALSE) {
    $per_page = 20;
    $table = [];
    $db = Database::getConnection();
    $query = $db->select('node', 'n')
      ->fields('n');
    $query->fields($query->join('node_field_data', 'fd', 'fd.nid = n.nid'));
    $query->leftJoin('mm_node2tree', 'n2', 'n2.nid = n.nid');
    $query->leftJoin('mm_tree', 't', 't.mmtid = n2.mmtid');
    $query = $query->isNull('t.mmtid')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender')
      ->orderByHeader($this->header());

    $clone = clone($query);
    $count = $clone->countQuery()->execute()->fetchField();
    $final_error = FALSE;
    if (!$count) {
      $final = $this->t('No nodes were found without page assignments.');
    }
    else {
      $strings = array(
        '@page' => Constants::MM_ENTRY_ALIAS_SYSTEM . '/' . Constants::MM_ENTRY_ALIAS_LOST_FOUND,
        ':link' => Url::fromUri('internal:/' . Constants::MM_ENTRY_ALIAS_SYSTEM . '/' . Constants::MM_ENTRY_ALIAS_LOST_FOUND)->toString(),
        '@title' => mm_content_expand_name(Constants::MM_ENTRY_NAME_LOST_FOUND),
      );

      if ($fix) {
        $final = \Drupal::translation()->formatPlural($count, '1 node was assigned, based on its type, to a subpage of <a href=":link">@title</a>.', '@count nodes were assigned, based on their type, to subpages of <a href=":link">@title</a>.', $strings);
      }
      else {
        $final = \Drupal::translation()->formatPlural($count, 'Found 1 node without page assignments.', 'Found @count nodes without page assignments.', $strings);
        if (!$fix) {
          $click_msgs = array(
            self::OUTPUT_MODE_TABLE => 'Click here to <a href=":url">assign the node(s) to a recovery page.</a>',
            self::OUTPUT_MODE_DRUSH => 'Use "drush mm-fix-orphan-nodes" to assign the node(s) to a recovery page.',
          );
          $click = isset($click_msgs[$this->outputMode]) ? $click_msgs[$this->outputMode] : 'Go to :url to assign the node(s) to a recovery page.';
          $final = new FormattableMarkup(':found ' . $click, array(':url' => Url::fromRoute('monster_menus.admin_orphan_nodes', [], ['query' => ['_fix' => 1]])->toString(), ':found' => $final->render()));
        }
      }

      if ($this->outputMode == self::OUTPUT_MODE_TABLE && !$fix) {
        $query = $query->extend('\Drupal\Core\Database\Query\PagerSelectExtender')->limit($per_page);
      }
      $query = $query->execute();
      foreach ($query as $row) {
        // Note: $row is the result of a direct query, so it is not a Node, but a
        // StdClass.
        if (!($type = NodeType::load($row->type))) {
          $type_name = $this->t('Missing Content Type - @type', array('@type' => $row->type));
        }
        else {
          $type_name = $type->label();
        }

        $message = 'Node @nid has no page assignments';
        if ($fix) {
          $alias = preg_replace('[^-\w]', '', $row->type);
          if (ValidateSortIndex::createLostAndFound($mmtid, $message, $strings, array('alias' => $alias, 'name' => $type_name))) {
            $final = $this->t($message, $strings);
            $final_error = TRUE;
            // Exit foreach.
            break;
          }
          // Since it's possible for an orphan to have an entry in mm_node2tree
          // which points to a nonexistent page, remove all pages first.
          $db->delete('mm_node2tree')
            ->condition('nid', $row->nid)
            ->execute();
          $rec = array('nid' => $row->nid, 'mmtid' => $mmtid);
          \Drupal::database()->insert('mm_node2tree')->fields($rec)->execute();
          $strings['@subpage'] = $alias;
          $message = 'Node @nid assigned to @page/@subpage';
        }
        $strings['@nid'] = $row->nid;
        switch ($this->outputMode) {
          case self::OUTPUT_MODE_WATCHDOG:
            \Drupal::logger('mm')->error($message, $strings);
            break;

          case self::OUTPUT_MODE_TABLE:
            if (!$fix) {
              $title = trim($row->title);
              if (empty($title)) {
                $title = $this->t('(Title not provided)');
              }
              $table[$row->nid] = [
                Link::fromTextAndUrl($title, Url::fromRoute('entity.node.canonical', ['node' => $row->nid]))->toString(),
                $type_name,
                $this->t('@user (@uid)', array('@user' => mm_content_uid2name($row->uid), '@uid' => $row->uid)),
                mm_format_date($row->created, 'short'),
                mm_format_date($row->changed, 'short'),
              ];
            }
            break;

          case self::OUTPUT_MODE_PRINT:
            print $this->t($message, $strings) . "\n";
            break;

          case self::OUTPUT_MODE_DRUSH:
            $tmsg = $this->t($message, $strings);
            if ($fix) {
              Drush::logger()->notice($tmsg);
            }
            else {
              Drush::logger()->warning($tmsg);
            }
            break;
        }
      }
    }

    switch ($this->outputMode) {
      case self::OUTPUT_MODE_WATCHDOG:
        \Drupal::logger('mm')->notice($final, array());
        break;

      case self::OUTPUT_MODE_TABLE:
        if ($final_error) {
          \Drupal::messenger()->addError($final);
        }
        else {
          \Drupal::messenger()->addStatus($final);
        }
        if ($table) {
          return [
            [
              '#type' => 'table',
              '#header' => $this->header(),
              '#rows' => $table,
            ],
            ['#type' => 'pager'],
          ];
        }
        return array();

      case self::OUTPUT_MODE_PRINT:
        print $final . "\n";
        break;

      case self::OUTPUT_MODE_DRUSH:
        if ($final_error) {
          Drush::logger()->error($final);
        }
        else {
          Drush::logger()->log(LogLevel::OK, $final);
        }
        break;
    }
  }

}
