/**
 * @file
 * Overrides the behavior of the Minesweeper gameboard.
 */
(function ($, Drupal, drupalSettings) {
  var gameOver = false;
  Drupal.minesweeper.checkState = function(field) {
    var state = true;
    // Check if there are tiles left to click
    total = field.length;
    clickedTiles = [];
    mines = [];
    $.each(field, function(index, element) {
      if (element.flipped && !element.mine) {
        clickedTiles.push(element.id);
      }
      if (element.mine) {
        mines.push(element.id);
      }
      if (element.mine && element.flipped) {
        // Do nothing.
      }
    });

    goal = total - mines.length;
    if (goal == clickedTiles.length) {
      if (!gameOver) {
        $('table.minesweeper').once().before('<div class="game-over"><span>' + Drupal.t('Game Over!') + '</span></div>');
      }

      state = false;
      gameOver = true;
    }

    return state;
  };

})(jQuery, Drupal, drupalSettings);
