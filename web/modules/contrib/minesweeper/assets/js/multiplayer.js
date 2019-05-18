var TogetherJSConfig_toolName = "Minesweeper";
var TogetherJSConfig_autoStart = true;

(function ($, Drupal, drupalSettings) {

  var $minesweeper_table = $('table.minesweeper');
  var time = 0;
  var elapsed = '0.0';
  var gameStarted = false;
  var field = false;
  var gameOver = false;
  var start = false;

  //'use strict';
  Drupal.behaviors.minesweeperButtonBehavior = {
    attach: function (context) {


      var $tile = $('.minesweeper .tile');

      $tile.off().on('click', function() {
        // Check what tile was clicked
        var clickedTile = $(this).attr("id");

        if (gameStarted == false) {
          // Start a new game
          start = new Date().getTime(),
            time = 0,
            elapsed = '0.0';
          $.getJSON('/minesweeper/start/' + drupalSettings.minesweeper.gametype
            + '/' + drupalSettings.minesweeper.difficulty
            + '/' + clickedTile, function(data) {
            // console.log(data); //uncomment this for debug
            Drupal.minesweeper.current_board = data.board;
            Drupal.minesweeper.current_board = Drupal.minesweeper.flipTile(clickedTile, Drupal.minesweeper.current_board);
            var state = Drupal.minesweeper.checkState(Drupal.minesweeper.current_board);
            Drupal.minesweeper.reDraw(Drupal.minesweeper.current_board, state);
            if (TogetherJS.running) {
              TogetherJS.send({
                type: 'flipOpponent',
                game: Drupal.minesweeper.current_board,
                flipped: clickedTile
              });
            }
          });

          window.setTimeout(Drupal.minesweeper.timeCounter, 100);
          gameStarted = true;
        } else {
          // Continue current game
          Drupal.minesweeper.current_board = Drupal.minesweeper.flipTile(clickedTile, Drupal.minesweeper.current_board);
          var state = Drupal.minesweeper.checkState(Drupal.minesweeper.current_board);
          Drupal.minesweeper.reDraw(Drupal.minesweeper.current_board, state);
          if (TogetherJS.running) {
            TogetherJS.send({
              type: 'flipOpponent',
              game: Drupal.minesweeper.current_board,
              flipped: clickedTile
            });
          }
        }
      });

      document.oncontextmenu = function() {return false;};
      $tile.mousedown(function(e){
        var clickedTile = $(this).attr("id");
        if( e.button == 2 ) {
          // alert('Right mouse button!');
          if (!gameOver) {
            Drupal.minesweeper.current_board = Drupal.minesweeper.flagTile(clickedTile, Drupal.minesweeper.current_board);
            var state = Drupal.minesweeper.checkState(Drupal.minesweeper.current_board);
            Drupal.minesweeper.reDraw(Drupal.minesweeper.current_board, state);
            if (TogetherJS.running) {
              TogetherJS.send({
                type: 'flipOpponent',
                game: Drupal.minesweeper.current_board,
                flipped: clickedTile
              });
            }
            return false;
          }
        }
        return true;
      });
    }
  };

  Drupal.behaviors.minesweeperMultiplayerBehavior = {
    attach: function (context, settings) {

      TogetherJS.on("ready", function () {
        clientId = TogetherJS.require("peers").Self.id;
        //clients = TogetherJS.require("peers").getAllPeers();
        //console.log(clients);
        console.log(clientId);
        player = clientId;
      });

      // Hello is sent from every newly connected user, this way they will receive what has already been drawn:
      TogetherJS.hub.on('togetherjs.hello', function () {
        TogetherJS.send({
          type: 'init',
          current_game: Drupal.minesweeper.current_board
        });
      });

      function getFirstKey(data) {
        for (elem in data)
          return elem;
      }

      // Draw initially received drawings:
      TogetherJS.hub.on('init', function (msg) {
        Drupal.minesweeper.current_board = msg.current_game;
        if (Drupal.minesweeper.current_board) {
          var state = Drupal.minesweeper.checkState(Drupal.minesweeper.current_board);
          Drupal.minesweeper.reDraw(Drupal.minesweeper.current_board, state);
          gameStarted = true;
        }
      });

      // Listens for draw messages, sends info about the drawn lines:
      TogetherJS.hub.on('flipOpponent', function (msg) {
        if (!msg.sameUrl) {
          return;
        }
        //draw(msg.start, msg.end, msg.color, msg.size, msg.compositeOperation, true);
        Drupal.minesweeper.current_board = msg.game;
        var state = Drupal.minesweeper.checkState(Drupal.minesweeper.current_board);
        gameStarted = true;
        Drupal.minesweeper.reDraw(Drupal.minesweeper.current_board, state);
      });

    }
  }
})(jQuery, Drupal, drupalSettings);