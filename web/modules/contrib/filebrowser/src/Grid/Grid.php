<?php

namespace Drupal\filebrowser\Grid;

use Drupal\Core\Template\Attribute;

// todo:
// Clean-up is required. In this Class some attributes are set. But due to a change
// of directions a lot of them are set in form GridActionForm.
// We should put parts in a preprocessor or in the template

class Grid {
  /**
   * @var array Array of unsorted grids that should converted to rows or columns
   */
  protected $variables;

  /**
   * @var array Sorted grids
   */
  protected $sortedGrids;

  /**
   * @var array Options for construction of the grids
   * 'alignment' => horizontal or vertical (vertical not yet implemented,
   * 'columns' => amount of columns,
   * 'automatic_width' = true or false
   *
   */
  protected $options;

  public function __construct($variables, $options) {
    $this->variables = $variables;
    $this->options = $options;
  }

  public function get() {
    $this->CreateGrids();
    return $this->sortedGrids;
  }

  protected function CreateGrids() {
    $options = $this->options;
    //debug($this->variables);
    $horizontal = ($options['alignment'] === 'horizontal');
    $columns = $options['columns'];
    $row = 0;
    $col = 0;
    $items = [];
    $remainders = count($this->variables) % $columns;
    $num_rows = floor(count($this->variables) / $columns);

    //Calculate the width of the grids and pass it in the $items['options']
    $this->options['column_width'] = 100 / $columns;

    // Take each grid and assign it a row or column
    // the sorted grids will be in $items
    foreach ($this->variables as $index => $item) {
      // Add the item.
      if ($horizontal) {
        $items[$row]['row'][$col]['content'] = $item;
      }
      else {
        // $items[$col]['content'][$row]['content'] = $item;
      }

      // Create attributes for rows.
      if (!$horizontal || ($horizontal && empty($items[$row]['attributes']))) {
        $row_attributes = ['class' => []];
        // Add custom row classes.
        //  $row_class = array_filter(explode(' ', $variables['view']->style_plugin->getCustomClass($result_index, 'row')));
        if (!empty($row_class)) {
          $row_attributes['class'] = array_merge($row_attributes['class'], $row_class);
        }
        // Add row attributes to the item.
        if ($horizontal) {
          $items[$row]['attributes'] = new Attribute($row_attributes);
        }
        else {
          $items[$col]['content'][$row]['attributes'] = new Attribute($row_attributes);
        }
      }

      // Create attributes for columns.
      if ($horizontal || (!$horizontal && empty($items[$col]['attributes']))) {
        $col_attributes = ['class' => []];
        // Add default views column classes.
        if (!empty($col_class)) {
          $col_attributes['class'] = array_merge($col_attributes['class'], $col_class);
        }
        // Add automatic width for columns.
        //fixme: don't need'
//        if ($auto_width) {
          $col_attributes['style'] = 'width: ' . (100 / $options['columns']) . '%;';
//        }
        //Calculate the width of the grids and pass it in the $items['options']

        // Add column attributes to the item.
        if ($horizontal) {
          $items[$row]['row'][$col]['content']['attributes'] = new Attribute($col_attributes);
        }
        else {
          $items[$col]['attributes'] = new Attribute($col_attributes);
        }
      }

      // Increase, decrease or reset appropriate integers.
      if ($horizontal) {
        if ($col == 0 && $col != ($options['columns'] - 1)) {
          $col++;
        }
        elseif ($col >= ($options['columns'] - 1)) {
          $col = 0;
          $row++;
        }
        else {
          $col++;
        }
      }
      else {
        $row++;
        if (!$remainders && $row == $num_rows) {
          $row = 0;
          $col++;
        }
        elseif ($remainders && $row == $num_rows + 1) {
          $row = 0;
          $col++;
          $remainders--;
        }
      }
    }
   // debug($items);
    $this->sortedGrids = $items;
  }

}

