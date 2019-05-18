<?php

namespace Drupal\rocketship\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

/**
 * Plugin implementation of the 'rocketship_issues' formatter.
 *
 * @FieldFormatter(
 *   id = "rocketship_issues",
 *   label = @Translation("Rocketship issues"),
 *   description = @Translation("Display issues tagged with the given tag."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class IssuesFormatter extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    if (empty($items)) {
      return [];
    }

    $config = \Drupal::config('rocketship.settings');
    $master_tag = $config->get('master_tag');
    $node_type = $config->get('issue_node_type');
    $field_issue_tags = $config->get('issue_tags_field');
    $field_issue_assigned = $config->get('issue_assigned_field');
    $issue_tags_vocabulary = $config->get('tags_vocabulary');

    // Create an entity field query for issue nodes matching the tag provided.
    $query = \Drupal::entityQuery('node')->condition('type', $node_type);
    $terms = $this->getEntitiesToView($items, $langcode);
    $page_tag = current($terms);
    $query->condition($field_issue_tags, $page_tag->id());
    $nids = $query->execute();

    // Now come some tricky wrangling of the data. We want to display
    // two 3-column tables. The first one is the primary overview
    // with the second more informal about issues we are not currently
    // focusing on.
    $tables = array(
      1 => t('Currently in the works for Drupal 8'),
      2 => t('Other related issues'),
    );

    // The columns for these two tables are intermingled here to make
    // term matching simpler without complicated conditions. We achieve
    // categorization without complex conditions by prioritizing the
    // placement of issues in categories with their tags.
    $columns = [
      // Need this first so that we catch the backport patches first and then
      // the rest can sort itself based on simpler tag lookups.
      (string) $this->t('Backport') =>        [['Patch (to be ported)', '6.x-dev', '7.x-dev'], 2],
      (string) $this->t('To do') =>           [['Active', 'Needs work'], 1],
      (string) $this->t('To review') =>       [['Needs review'], 1],
      (string) $this->t('To be committed') => [['Reviewed & tested by the community'], 1],
      (string) $this->t('Postponed') =>       [['Postponed', 'Postponed (maintainer needs more info)'], 2],
      (string) $this->t('Closed') =>          [['Fixed', 'Closed (duplicate)', "Closed (won't fix)", 'Closed (works as designed)', 'Closed (cannot reproduce)', 'Closed (fixed)'], 2],
    ];

    // Some trickery to replace the term names in the $columns array
    // with term ids for easier lookup later and collect a list of
    // all those term IDs for later matching.
    $status_tids = array();
    foreach ($columns as $name => &$terms) {
      foreach ($terms[0] as &$term_name) {
        if ($result = \Drupal::entityQuery('taxonomy_term')->condition('name', $term_name)->condition('vid', $issue_tags_vocabulary)->execute()) {
          $tid = current($result);
          // Replace term name in $columns with term ID (the variable
          // is taken by reference, so we directly modify the array here).
          $term_name = $tid;
          // Collect a complete list of all status tids.
          $status_tids[] = $tid;
          break;
        }
        else {
          // This might happen if we never had issues with the given status tag,
          // so we don't have the status tag created yet. In this case, we should
          // ignore this tag for now.
          $term_name = 0;
        }
      }
    }

    // Now load the nodes we found for the page tag and categorize into
    // the groups defined by terms in $columns.
    $nodes = array();
    foreach ($nids as $nid) {
      $issue_node = Node::load($nid);
      // Tags on the issue node should be referenced in {$field_issue_tags}[].
      if (!empty($issue_node->{$field_issue_tags})) {
        foreach ($issue_node->{$field_issue_tags} as $term) {
          /** \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $term */
          if (in_array($term->getValue()['target_id'], $status_tids)) {
            // If this was a status tid, look at which group it is in,
            // in which case we found where to categorize the node.
            foreach ($columns as $title => $status_map) {
              if (in_array($term->getValue()['target_id'], $status_map[0])) {
                // Save the whole node object categorized under the $columns
                // $title for this column and move two levels up to the next
                // node.
                $nodes[$title][] = $issue_node;
                break 2;
              }
            }
          }
        }
      }
    }

    // Now sort the $nodes array into two tables and fill in the table
    // headers and cells appropriately.
    $header_data = array();
    $table_data = array();
    foreach ($columns as $header => $columns_data) {
      // Sort nodes by "priority" defined by current focus as most important and
      // then major and critical being slightly less important and then the rest.
      $cell = array(-10 => '', -7 => '', -5 => '', 0 => '');
      if (!empty($nodes[$header])) {
        foreach ($nodes[$header] as $issue_node) {
          $classes = 'rocketship-node';
          $cell_terms = '';
          $priority = 0;
          foreach ($issue_node->{$field_issue_tags} as $term) {
            $term = Term::load($term->getValue()['target_id']);
            $term_class = 'rocketship-term';
            switch ($term->label()) {
              // Style the needs tests tag specially.
              // @todo: this has some more potential, think about it.
              case 'Needs tests':
                $term_class .= ' rocketship-needs-tests';
                break;
              // Mark issues with major and critical status.
              case 'Major':
                $classes .= ' rocketship-major';
                $priority = min(-5, $priority);
                break;
              case 'Critical':
                $classes .= ' rocketship-critical';
                $priority = min(-7, $priority);
                break;
              // Use the "sprint" tag to mark issues for current focus.
              case 'sprint':
                if ($page_tag->label() != 'sprint') {
                  // Only class and move up sprint issues in priority if not
                  // already on the sprint page.
                  $classes .= ' rocketship-focus';
                  $priority = min(-10, $priority);
                }
                break;
            }
            // Remove tags that are "trivial" in this listing. The master tag
            // is always trivial, the tool focuses on 8.x issues, so those
            // not having that tag should be assumed to be 8.x, RTBC and
            // needs review issues are in their respective column, so that is
            // already represented, and the current page tag is presented on
            // the page, so again no need to repeat it on all nodes (it is not
            // a distinguishing factor).
            if (!in_array($term->label(), array($master_tag, 'Reviewed & tested by the community', 'Needs review', '8.x-dev', $page_tag->label()))) {
              // Add up all terms with their respective tags.
              $cell_terms .= '<span class="'. $term_class . '">'. Html::escape($term->label()) .'</span>';
            }
          }

          // Add rocketship-normal class for easier styling.
          if ($priority == 0) {
            $classes .= ' rocketship-normal';
          }

          // Link to drupal.org node for this issue.
          $drupalorg_nid = preg_replace('!#(\d+):(.*)$!', '\1', $issue_node->label());
          $cell[$priority] .= '<div class="' . $classes . '"><a href="https://drupal.org/node/'. $drupalorg_nid . '">' . $issue_node->label() .'</a><br />';
          $cell[$priority] .= $cell_terms;
          if (!empty($issue_node->{$field_issue_assigned}) && $issue_node->{$field_issue_assigned}->getValue()[0]['value'] != 'Unassigned') {
            $cell[$priority] .= '<div class="assigned">' . $this->t('Assigned to @assigned', array('@assigned' => $issue_node->{$field_issue_assigned}->getValue()[0]['value'])) . "</div>";
          }
          $cell[$priority] .= '</div>';
        }
      }

      // Store this cell and header in the right table. Each table will only
      // have one cell per column (which might not look nice), but it gets us
      // to the best display results. For purity's sake we might want to
      // rebuild this based on a three column layout, that is what we
      // accomplish with the tables basically.
      $table_data[$columns_data[1]][] = join('', $cell);
      $header_data[$columns_data[1]][] = $header;
    }

    $output = '<div class="rocketship-issues">';

    // Produce a short legend to be included with each table.
    $legend = '<div class="rocketship-legend">Legend: <div class="rocketship-critical rocketship-node">Critical issue</div> <div class="rocketship-major rocketship-node">Major issue</div>';
    if ($page_tag->label() != 'sprint') {
      $legend .= ' <div class="rocketship-focus rocketship-node">Current top priority</div>';
    }
    $legend .= '</div>';
    foreach($tables as $num => $title) {
      // Lead the table with a legend.
      $output .= $legend . '<h3>'. $title .'</h3>';
      // Link to drupal.org issue list for the same issue overview. Reassures
      // users that there is no special data showcased here, they can still
      // access all this on drupal.org.
      $issue_list_url = 'https://drupal.org/project/issues/search/drupal?issue_tags=' . urlencode($page_tag->label()) . ($page_tag->label() == $master_tag ? '' : '%2C+' . $master_tag . '&issue_tags_op=all+of');
      $output .= '<p style="clear: both;">See all these issues also at <a href="' . Html::escape($issue_list_url) . '">' .Html::escape($issue_list_url) . '</a>. This view of issues is automatically cached and might be out of date up to two hours at times.</p>';

      // Theme the respective "table" with responsive markup.
      $output .= '<div class="rocketship-columns">';
      foreach ($header_data[$num] as $i => $cell) {
        $output .= '<div class="rocketship-column"><h4>' . $cell . '</h4>';
        if (empty($table_data[$num][$i])) {
          $output .= t('(None)');
        }
        else {
          $output .= $table_data[$num][$i];
        }
        $output .= '</div>';
      }
      $output .= '</div>';
    }
    $output .= '</div>';

    return array(
      '#markup' => $output,
      // Place at the end of the page before book navigation (if present).
      '#weight' => 80,
      '#attached' => ['library' => ['rocketship/issues']],

      // Issues are maintained as nodes. Any node that has the tag for the page
      // may appear or disappear on this page based on changes to nodes, so we
      // need to vary this by any node changes (new/deleted/changed).
      // @todo explore introducing our own cache tag so only issue changes are
      // taken into account
      '#cache' => [
        'tags' => ['node_list'],
      ],
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $config = \Drupal::config('rocketship.settings');
    return
      $field_definition->getName() == $config->get('page_tag_field') &&
      $field_definition->getTargetEntityTypeId() == 'node' &&
      $field_definition->getTargetBundle() == $config->get('page_node_type');
  }

}
