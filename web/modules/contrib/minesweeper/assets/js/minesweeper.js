/**
 * @file
 * Defines the behavior of the Minesweeper gameboard.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.minesweeper = {};

  var $minesweeper_table = $('table.minesweeper');
  var gameStarted = false;
  Drupal.minesweeper.gameOver = false;
  var start = 0;
  var time = 0;

  // @TODO: we go over the entire board twice each click. This could be improved.
  Drupal.minesweeper.reDraw = function (board) {
    var state = Drupal.minesweeper.checkState(board);
    var $el = '';
    $.each(board, function (index, element) {
      $el = $('#' + element['id']);
      if (element.flipped) {
        var output = element['value'] ? element['value'] : '';
        output = output !== 'M' ? output : '';
        $el.text(output).addClass('flipped').addClass('output-' + element['value']);
      }
      if (element.flagged) {
        $el.addClass('flagged');
      }
      else {
        $el.removeClass('flagged');
      }

      if (!state) {
        if (element.mine && !element.flipped) {
          $el.addClass('output-' + element['value']);
        }
        if (!element.mine && element.flagged) {
          $el.addClass('falseflag');
        }
      }
    });
  };

  Drupal.minesweeper.checkState = function (field) {
    var state = true;
    // Check if there are tiles left to click
    var total = field.length;
    var clickedTiles = [];
    var mines = [];
    $.each(field, function (index, element) {
      if (element.flipped) {
        clickedTiles.push(element.id);
      }
      if (element.mine) {
        mines.push(element.id);
      }
      if (element.mine && element.flipped) {
        state = false;
        Drupal.minesweeper.gameOver = true;
        $minesweeper_table.once().before('<div class="game-over"><span>' + Drupal.t('Game Over!') + '</span></div>');
      }
    });

    var goal = total - mines.length;
    if (goal === clickedTiles.length) {
      if (state) {
        $minesweeper_table.once().before('<div class="game-over"><span>' + Drupal.t('You win!') + '</span></div>');
      }
      Drupal.minesweeper.gameOver = true;
      state = false;
    }

    return state;
  };

  Drupal.minesweeper.flagTile = function (id, board) {
    var $counter = $('.minesweeper .flag-counter');
    var count = $counter.text();
    if (!board[id].flagged && !board[id].flipped) {
      board[id].flagged = true;
      count++;
    }
    else if (board[id].flagged) {
      board[id].flagged = false;
      count--;
    }

    // Update flag-counter
    $counter.text(count);

    Drupal.minesweeper.reDraw(board);

    return board;
  };

  Drupal.minesweeper.flipTile = function (id, field) {
    // Check if it is safe to flip the tile
    var allowedToFlip = allowFlip(id, field);
    if (!allowedToFlip) {
      return field;
    }

    // If the tile is empty, queue all neighbours
    var flipQueue = [];
    if (!field[id].value) {
      $.each(field[id].neighbours, function (index, element) {
        flipQueue.push(element);
      });
    }

    // Add the tile itself to the queue
    if (!field[id].flipped) {
      flipQueue.push(id);
    }

    // If the tile was already flipped, and the tile has the same amount of flags as mines,
    // queue all neighbours that are not flagged.
    if (field[id].flipped) {
      var neighbourMines = [];
      var neighbourFlags = [];
      $.each(field[id].neighbours, function (index, element) {
        if (field[element].mine) {
          neighbourMines.push(field[element].id);
        }
        if (field[element].flagged) {
          neighbourFlags.push(field[element].id);
        }
      });
      if (neighbourMines.length === neighbourFlags.length) {
        $.each(field[id].neighbours, function (index, element) {
          if (!field[element].flagged && !field[element].flipped) {
            flipQueue.push(element);
          }
        });
      }
    }

    // Finally perform the flip
    field = flipAll(flipQueue, field);

    function allowFlip(id, field) {
      // We can't flip once it's game over
      // Flags cannot be flipped
      return !(Drupal.minesweeper.gameOver || field[id].flagged);
    }

    function flipAll(flipQueue, field) {
      $.each(flipQueue, function (index, element) {
        // Flip every tile in the queue
        if (!field[element].flipped) {
          field[element].flipped = true;
        }
        // If tile is empty, recursively add all neighbours which haven't been flipped.
        // @TODO: can this be done more efficient?
        if (!field[element].value) {
          var newFlipQueue = [];
          $.each(field[element].neighbours, function (newIndex, newElement) {
            // console.log(newElement);
            if (!field[newElement].flipped && $.inArray(newElement, flipQueue)) {
              newFlipQueue.push(newElement);
            }
          });
          field = flipAll(newFlipQueue, field);
        }
      });
      return field;
    }

    Drupal.minesweeper.reDraw(field);

    return field;
  };

  Drupal.minesweeper.timeCounter = function () {
    time += 100;

    var elapsed = Math.floor(time / 1000);
    var elapsedMinutes = Math.floor(elapsed / 60);
    var elapsedSeconds = elapsed - (elapsedMinutes * 60);
    if (elapsedSeconds < 10) {
      elapsedSeconds = '0' + elapsedSeconds;
    }

    var diff = (new Date().getTime() - start) - time;
    $('.time-counter').text(elapsedMinutes + ':' + elapsedSeconds);
    if (!Drupal.minesweeper.gameOver) {
      window.setTimeout(Drupal.minesweeper.timeCounter, (100 - diff));
    }
  };

  Drupal.behaviors.minesweeperButtonBehavior = {
    attach: function (context) {

      var $tile = $('.minesweeper .tile');

      $tile.off().on('click', function () {
        // Check what tile was clicked
        var clickedTile = $(this).attr('id');

        if (gameStarted === false) {
          // Start a new game
          start = new Date().getTime();

          $.getJSON('/minesweeper/start/' + drupalSettings.minesweeper.gametype
            + '/' + drupalSettings.minesweeper.difficulty
            + '/' + clickedTile, function (data) {
            // console.log(data); //uncomment this for debug
            Drupal.minesweeper.current_board = Drupal.minesweeper.flipTile(clickedTile, data.board);
          });

          window.setTimeout(Drupal.minesweeper.timeCounter, 100);
          gameStarted = true;
        }
        else {
          // Continue current game
          Drupal.minesweeper.current_board = Drupal.minesweeper.flipTile(clickedTile, Drupal.minesweeper.current_board);
        }
      });

      /**
       * We suppress the browsers context menu when the user clicks the right mouse button and attach our own handler
       * to flag tiles.
       */
      document.oncontextmenu = function () {return false;};
      $tile.mousedown(function (e) {
        var clickedTile = $(this).attr('id');
        if (e.button === 2) {
          if (!Drupal.minesweeper.gameOver) {
            Drupal.minesweeper.current_board = Drupal.minesweeper.flagTile(clickedTile, Drupal.minesweeper.current_board);
            return false;
          }
        }
        return true;
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
