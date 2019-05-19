<?php


namespace Drupal\views_search_snippet\Plugin\views\field;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\views\Annotation\ViewsField;
use Drupal\views\Plugin\views\field\NumericField;
use Drupal\views\ResultRow;


/**
 * Field handler for search snippet.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("views_search_snippet")
 */
class Snippet extends NumericField {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Check to see if the search filter is present, which we can tell by
    // the 'search_score' property being present. We also need to check its
    // relationship to make sure that we're using the same one or obviously
    // this won't work.
    foreach ($this->view->filter as $handler) {
      if (isset($handler->search_score) && ($handler->relationship == $this->relationship)) {
        $this->searchFilterPresent = TRUE;
        $this->ensureMyTable();

        // We need the langcode field from the search index table.
        $this->tableAlias = $handler->tableAlias;
        $this->field_alias = $this->query->addField($this->tableAlias, 'langcode', NULL, array());
        return;
      }
    }
    // Hide this field if no search filter is in place.
    $this->options['exclude'] = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    // Only render if we exist.
    if (isset($this->searchFilterPresent)) {
      $node_renderer = \Drupal::entityManager()->getViewBuilder('node');
      $node = $this->getEntity($values);
      //Todo:langcode
      //$langcode = $this->getValue($values);
      //Todo:langcode
      // $node = $node->getTranslation($langcode);
      $build = $node_renderer->view($node, 'search_result');
      unset($build['#theme']);
      $node_rendered = drupal_render($build);
      // Fetch comment count for snippet.
      //Todo:langcode
      $keys = $this->view->getExposedInput()['keys'];
      $output = $node_rendered->__toString();
      $snippet = search_excerpt($keys, $output);
      return $snippet;
    }
  }


}