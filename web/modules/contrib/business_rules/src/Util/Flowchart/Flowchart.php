<?php

namespace Drupal\business_rules\Util\Flowchart;

use Drupal\business_rules\BusinessRulesItemObject;
use Drupal\business_rules\Entity\Action;
use Drupal\business_rules\Entity\BusinessRule;
use Drupal\business_rules\Entity\Condition;
use Drupal\business_rules\Entity\Variable;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Class Flowchart.
 *
 * Draws the Business Rules flowchart.
 *
 * @package Drupal\business_rules\Util
 */
class Flowchart {

  use StringTranslationTrait;

  /**
   * The matrix.
   *
   * @var \Drupal\business_rules\Util\Flowchart\Matrix
   */
  private $matrix;

  /**
   * Flowchart constructor.
   */
  public function __construct() {
    $this->matrix = new Matrix();
  }

  /**
   * Show the Business Rule workflow for one item.
   *
   * @param \Drupal\Core\Entity\EntityInterface $item
   *   The item.
   *
   * @return array
   *   The render array.
   */
  public function getGraph(EntityInterface $item) {

    // Variables and non saved items does not have graph.
    // If item has no children, there is no sense in show the flowchart as well.
    if ($item instanceof Variable || $item->isNew() || !$this->itemHasChildren($item)) {
      $form['graph_definition'] = [
        '#type'       => 'textarea',
        '#attributes' => [
          'id'    => 'graph_definition',
          'style' => ['display:none'],
        ],
      ];

      return $form;
    }

    $this->mountMatrix($item);

    $graph_definition   = [];
    $graph_definition[] = '<mxGraphModel dx="1426" dy="847" grid="1" gridSize="10" guides="1" tooltips="1" connect="1" arrows="1" fold="1" page="1" pageScale="1" pageWidth="850" pageHeight="1100" background="#ffffff"><root><mxCell id="0"/><mxCell id="1" parent="0"/>';

    // Prepare the graph.
    $graph_definition = array_merge($graph_definition, $this->processMatrix());

    $graph_definition[] = '</root></mxGraphModel>';

    $form['graph_definition'] = [
      '#type'       => 'textarea',
      '#value'      => implode('', $graph_definition),
      '#attributes' => [
        'id'    => 'graph_definition',
        'style' => ['display:none'],
      ],
      '#rows'       => 0,
      '#cols'       => 0,
    ];

    $form['mxGraph'] = [
      '#type'   => 'markup',
      '#prefix' => '<div id="business_rules_workflow_graph">',
      '#suffix' => '</div>',
    ];

    return $form;
  }

  /**
   * Check if item has children.
   *
   * @param \Drupal\Core\Entity\EntityInterface $item
   *   The item.
   *
   * @return bool
   *   True|False.
   */
  private function itemHasChildren(EntityInterface $item) {
    if ($item instanceof BusinessRule) {
      $children = $item->getItems();
      if (!is_null($children) && count($children)) {
        return TRUE;
      }
    }
    elseif ($item instanceof Action) {
      $children = $item->getSettings('items');
      if (!is_null($children) && count($children)) {
        return TRUE;
      }
    }
    elseif ($item instanceof Condition) {
      $success_items = $item->getSuccessItems();
      $fail_items    = $item->getFailItems();

      if (count($success_items) || count($fail_items)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Mount the graph matrix.
   *
   * @param \Drupal\Core\Entity\EntityInterface $item
   *   The Business Rule item.
   * @param null|\Drupal\business_rules\Util\Flowchart\Element $parent
   *   The parent Element.
   * @param string $info
   *   Additional info for the method.
   * @param string $arrowLabel
   *   The label for the arrow.
   */
  private function mountMatrix(EntityInterface $item, Element $parent = NULL, $info = '', $arrowLabel = '') {
    if (empty($parent)) {
      $root = new Element($item);
      $this->matrix->putRootElement($root);
    }
    else {

      $off_x     = 0;
      $off_y     = 0;
      $direction = 'bottom';
      if ($parent->getItem() instanceof BusinessRule) {
        $direction = 'bottom';
      }
      elseif ($parent->getItem() instanceof Condition) {
        if ($info == 'success') {
          $direction = 'bottom-right';
        }
        elseif ($info == 'fail') {
          $direction = 'bottom-left';
        }
      }
      elseif ($parent->getItem() instanceof Action) {
        // If root's children: right.
        // If at right of the root: right.
        // If at left of the root: left.
        $root_cell   = $this->matrix->getCellByElementUuid($this->matrix->getRootElement()
          ->getUuid());
        $parent_cell = $this->matrix->getCellByElementUuid($parent->getUuid());
        if (empty($parent->getParent())) {
          $direction = 'bottom';
        }
        elseif ($parent_cell['index_x'] >= $root_cell['index_x'] && $this->matrix->rightCellIsEmpty($parent)) {
          $direction = 'right';
        }
        else {
          $direction = 'left';
        }
        // If item is condition and grandparent is the root, then change X off.
        if ($item instanceof Condition) {
          if (!empty($parent->getParent()) && $parent->getParent()
            ->getItem() instanceof BusinessRule) {
            if ($direction == 'right') {
              if (count($item->getFailItems())) {
                $off_x = 1;
              }
            }
            if ($direction == 'left') {
              if (count($item->getSuccessItems())) {
                $off_x = -1;
              }
            }
          }
        }
      }

      $element = new Element($item, $parent, '', $arrowLabel);

      $this->matrix->putElement($element, $parent, $direction, $off_x, $off_y);
    }

    if ($this->itemHasChildren($item)) {

      // Check if parent is the root element.
      if ($this->matrix->getRootElement()->getItem() === $item) {
        $parent_element = $this->matrix->getRootElement();
      }
      else {
        $parent_element = $this->matrix->getElementByItem($item);
      }

      if ($item instanceof BusinessRule) {
        $children = $item->getItems();
        /** @var \Drupal\business_rules\BusinessRulesItemObject $child */
        foreach ($children as $child) {
          $child = $child->loadEntity();
          if (!empty($child)) {
            $this->mountMatrix($child, $parent_element);
          }
        }
      }
      elseif ($item instanceof Condition) {
        $success_items = $item->getSuccessItems();
        /** @var \Drupal\business_rules\BusinessRulesItemObject $success_item */
        foreach ($success_items as $success_item) {
          $success_item = $success_item->loadEntity();
          $yes          = $this->t('Yes');
          if (!empty($success_item)) {
            $this->mountMatrix($success_item, $parent_element, 'success', $yes->render());
          }
        }
        $fail_items = $item->getFailItems();
        foreach ($fail_items as $fail_item) {
          $fail_item = $fail_item->loadEntity();
          $no        = $this->t('No');
          if (!empty($fail_item)) {
            $this->mountMatrix($fail_item, $parent_element, 'fail', $no->render());
          }
        }
      }
      elseif ($item instanceof Action) {
        $children = $item->getSettings('items');
        if (count($children)) {
          $children = BusinessRulesItemObject::itemsArrayToItemsObject($children);
          foreach ($children as $child) {
            $child = $child->loadEntity();
            if (!empty($child)) {
              $this->mountMatrix($child, $parent_element);
            }
          }
        }
      }
    }
  }

  /**
   * Process the matrix.
   *
   * @return array
   *   The graph items array definition.
   */
  private function processMatrix() {
    $root_element = $this->matrix->getRootElement();
    $root_cell    = $this->matrix->getCellByElementUuid($root_element->getUuid());

    $graph = $this->getRootGraph($root_cell);

    $cells = $this->matrix->getAllCellWithElements();
    $this->processConnections($cells);

    foreach ($cells as $cell) {
      /** @var \Drupal\business_rules\Util\Flowchart\Element $element */
      $element = $cell['element'];
      if ($element->getParent()) {
        $graph[] = $this->getConnection($cell);
      }
    }

    foreach ($cells as $cell) {
      /** @var \Drupal\business_rules\Util\Flowchart\Element $element */
      $element = $cell['element'];
      if ($element->getParent()) {
        $graph = array_merge($graph, $this->getItemGraph($element));
      }
    }

    return $graph;
  }

  /**
   * Get the root graph.
   *
   * @param array $root
   *   The Business Rule item.
   *
   * @return array
   *   The graph array.
   */
  private function getRootGraph(array $root) {

    $item = $root['element']->getItem();

    $meta_data = self::getMetaData($item);
    $x         = $root['x'];
    $y         = $root['y'];
    $label     = str_replace('""', '', $item->label());
    $output    = [];
    $output[]  = '<UserObject label="' . $label . '" link="' . $meta_data['link'] . '" id="' . $root['element']->getUuid() . '">';
    $output[]  = '<mxCell style="' . $meta_data['style'] . '" parent="1" vertex="1">';
    $output[]  = '<mxGeometry x="' . $x . '" y="' . $y . '" width="120" height="60" as="geometry"/>';
    $output[]  = '</mxCell>';
    $output[]  = '</UserObject>';

    return $output;
  }

  /**
   * Process the graph connections.
   *
   * @param array $cells
   *   The matrix cells with elements.
   */
  private function processConnections(array $cells) {
    foreach ($cells as $cell) {
      /** @var \Drupal\business_rules\Util\Flowchart\Element $element */
      $element    = $cell['element'];
      $originUUid = $this->getOriginUuid($cell);
      $element->setOriginUuid($originUUid);
    }
  }

  /**
   * Get the connection's graph definition.
   *
   * @param array $cell
   *   The matrix cell.
   *
   * @return string
   *   The connection graph.
   */
  private function getConnection(array $cell) {
    $child_id  = $cell['element']->getUuid();
    $parent_id = $cell['element']->getOriginUuid();
    $label     = str_replace('"', '', $cell['element']->getArrowLabel());

    $connection = '<mxCell id="arrow-' . $child_id . '" value="' . $label . '" style="endArrow=classic;html=1;strokeWidth=3;strokeColor=#000000;fontSize=13;fontColor=#000000;fontStyle=1;labelBackgroundColor=#FFFFFF;" parent="1" source="' . $parent_id . '" target="' . $child_id . '" edge="1"><mxGeometry as="geometry"/></mxCell>';

    return $connection;
  }

  /**
   * Get the item's graph.
   *
   * @param Element $element
   *   The element.
   *
   * @return array
   *   The graph array.
   */
  private function getItemGraph(Element $element) {
    $item      = $element->getItem();
    $cell      = $this->matrix->getCellByElementUuid($element->getUuid());
    $x         = $cell['x'];
    $y         = $cell['y'];
    $meta_data = self::getMetaData($item);

    $graph   = [];
    $graph[] = '<UserObject id="' . $element->getUuid() . '" label="' . str_replace('"', '', $item->label()) . '" link="' . $meta_data['link'] . '">';
    $graph[] = '<mxCell style="' . $meta_data['style'] . '" parent="1" vertex="1">';
    $graph[] = '<mxGeometry x="' . $x . '" y="' . $y . '" width="120" height="60" as="geometry"/>';
    $graph[] = '</mxCell>';
    $graph[] = '</UserObject>';

    return $graph;
  }

  /**
   * Get the item meta data.
   *
   * @param \Drupal\Core\Entity\EntityInterface $item
   *   The item.
   *
   * @return array
   *   The meta data.
   */
  private function getMetaData(EntityInterface $item) {
    $meta_data = [];
    if ($item instanceof BusinessRule) {
      $meta_data['style'] = 'shape=ellipse;whiteSpace=wrap;fillColor=#FFFFFF;strokeColor=#000000;strokeWidth=2;shadow=0;gradientColor=none;fontColor=#000000;';
      $meta_data['link'] = Url::fromRoute('entity.business_rule.edit_form', ['business_rule' => $item->id()])
        ->toString();
    }
    elseif ($item instanceof Condition) {
      $meta_data['style'] = 'shape=rhombus;html=1;whiteSpace=wrap;aspect=fixed;strokeWidth=2;fillColor=#3399FF;strokeColor=#000000;fontColor=#000000;';
      $meta_data['link'] = Url::fromRoute('entity.business_rules_condition.edit_form', ['business_rules_condition' => $item->id()])
        ->toString();
    }
    elseif ($item instanceof Action) {
      $meta_data['style'] = 'rounded=1;whiteSpace=wrap;html=1;fillColor=#66CC00;gradientColor=none;strokeWidth=2;strokeColor=#000000;fontColor=#000000;';
      $meta_data['link'] = Url::fromRoute('entity.business_rules_action.edit_form', ['business_rules_action' => $item->id()])
        ->toString();
    }

    return $meta_data;
  }

  /**
   * Get the uuid to the arrow origin.
   *
   * @param array $cell
   *   The cell with the element.
   *
   * @return string
   *   The origin uuid.
   */
  private function getOriginUuid(array $cell) {
    $root_element = $this->matrix->getRootElement();
    if (empty($cell['element']->getParent()) || ($root_element->getItem() == $cell['element']->getParent()
      ->getItem()) && $root_element->getItem() instanceof BusinessRule
    ) {
      return $root_element->getUuid();
    }
    else {
      $parent = $cell['element']->getParent();
      /** @var \Drupal\business_rules\Util\Flowchart\Element $element */
      $element       = $cell['element'];
      $element_above = $this->matrix->getElementAbove($element);

      if ($element_above instanceof Element && !empty($element_above->getParent()) &&
        $element_above->getParent()->getItem() === $parent->getItem()
      ) {
        // This connection doesn't have label.
        $cell['element']->setArrowLabel('');

        // Connect to the immediate brother above.
        return $element_above->getUuid();
      }
      else {
        // Connect to the parent.
        return $parent->getUuid();
      }

    }

  }

  /**
   * Get the workflow graph definition.
   *
   * @param \Drupal\Core\Entity\EntityInterface $item
   *   The item.
   *
   * @return array|string
   *   The graph definition.
   */
  public function getGraphDefinition(EntityInterface $item) {
    // Variables does not have graph.
    if ($item instanceof Variable) {
      return '';
    }

    $this->mountMatrix($item);

    $graph_definition   = [];
    $graph_definition[] = '<mxGraphModel dx="1426" dy="847" grid="1" gridSize="10" guides="1" tooltips="1" connect="1" arrows="1" fold="1" page="1" pageScale="1" pageWidth="850" pageHeight="1100" background="#ffffff"><root><mxCell id="0"/><mxCell id="1" parent="0"/>';

    // Prepare the graph.
    $graph_definition = array_merge($graph_definition, $this->processMatrix());

    $graph_definition[] = '</root></mxGraphModel>';

    return implode('', $graph_definition);
  }

}
