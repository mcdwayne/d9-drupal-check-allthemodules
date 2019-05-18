/**
 * @file
 * 
 */

(function ($) {

  "use strict";

  $(document).ready(function () {

    $( ".noughts-and-crosses-board .play-again" ).click(function(){
        cleanErrors();
        $(".noughts-and-crosses-board input[type=text]").each(function() {
          $( this ).val('');
          $( this ).attr("readonly", false);
          $( this ).removeClass( "error" ).removeClass( "success" );
        });
        $( ".noughts-and-crosses-board div.play-again" ).hide();
    });

    $( ".noughts-and-crosses-board .form-item input" ).keyup(function() {
      var message = '';
      var arrElements = [];
      var count = 0;

      var play_type = ($('input[name=play_type]').val()) ? parseInt($('input[name=play_type]').val()) : 1;
      var first_move = ($('input[name=first_move]').val()) ? parseInt($('input[name=first_move]').val()) : 1;

      if (play_type == 1) {
        // Work in progress.
      }
      // @TODO: Write a function to check valid first move.
      var _val = $( this ).val();
      var _txt = _val.charAt( 0 ).toUpperCase() + _val.slice( 1 );
      $( this ).val(_txt);

      if (_txt != 'X' && _txt != 'O' && _txt != '') {
        $( this ).val('');
        message = Drupal.t("Only 'X' or 'O' is Valid ! Please, Try again !!");
        $( this ).addClass( "error" );
        $( ".noughts-and-crosses-board div#game-messages" ).addClass( "error-messages" );
        $( ".noughts-and-crosses-board div#game-messages" ).html( message );
        $( ".noughts-and-crosses-board div#game-messages" ).show();
        return;
      } else {        
        $( this ).removeClass( "error" ).removeClass( "success" );
        $( ".noughts-and-crosses-board div#game-messages" ).removeClass( "success-messages" ).removeClass( "error-messages" );
        $( ".noughts-and-crosses-board div#game-messages" ).empty();
        $( ".noughts-and-crosses-board div#game-messages" ).hide();
      }

      if (_txt === 'X' || _txt === 'O') {
        $( this ).attr("readonly", true);
      }

      $(".noughts-and-crosses-board input[type=text]").each(function() {
        $( this ).removeClass( "error" ).removeClass( "success" );
        arrElements.push([count++, $( this ).val()]);
      });
      /*arrElements[0][1]  arrElements[1][1]  arrElements[2][1]
        arrElements[3][1]  arrElements[4][1]  arrElements[5][1]
        arrElements[6][1]  arrElements[7][1]  arrElements[8][1]*/
      if(!isRowCrossed(arrElements)) {
        if (!isColumnCrossed(arrElements)) {
          if (!isDiagonalCrossed(arrElements)) {
            isDraw(arrElements);
          }
        }
      }
    });
  });

function cleanErrors(){
  $( ".noughts-and-crosses-board div#game-messages" ).removeClass( "success-messages" ).removeClass( "error-messages" );
  $( ".noughts-and-crosses-board div#game-messages" ).empty();
  $( ".noughts-and-crosses-board div#game-messages" ).hide();
  $( "#page #messages .section .messages.error" ).removeClass( "messages" ).removeClass( "error" );
  $( "#page #messages .section" ).empty();
}

function playAgain() {
  $( ".noughts-and-crosses-board div.play-again" ).show();
}

function markSuccess(what, which) {
  // Remove all errored marks.
  for (var i = 0; i < 3; i++){
    for (var j = 0; j < 3; j++) {
      $( ".noughts-and-crosses-board .form-item input#edit-board-" + i + "-" + j + "" ).removeClass( "error" );
    }
  }

  switch (what) {
    case 'row':
                for (var i = 0; i < 3; i++){
                  $( ".noughts-and-crosses-board .form-item input#edit-board-" + which + "-" + i + "" ).addClass( "success" );
                }
                break;
    case 'column':
                for (var i = 0; i < 3; i++){
                  $( ".noughts-and-crosses-board .form-item input#edit-board-" + i + "-" + which + "" ).addClass( "success" );
                }
                break;
    case 'diagonal':
                var diagonal = which;
                for (var i = 0; i < 3; i++){
                  $( ".noughts-and-crosses-board .form-item input#edit-board-" + i + "-" + diagonal + "" ).addClass( "success" );
                  if (which == 0) {
                    diagonal++;
                  } else {
                    diagonal--;
                  }
                }
                break;
    default:    break;
  }
  cleanErrors();
  playAgain();
}

function declareWinner(winner) {
  var player = '';
  var message = '';
  switch (winner) {
    case 'X':
      player = 'Player 1';
      break;
    case 'O':
      player = 'Player 2';
      break;
    default:
      break;
  }
  message += Drupal.t("Congratulations") + " !! ";
  message += player + ", ";
  message += Drupal.t("You've Won.");
  $( ".noughts-and-crosses-board div#game-messages" ).html( message );
  $( ".noughts-and-crosses-board div#game-messages" ).addClass( "success-messages" );
  $( ".noughts-and-crosses-board div#game-messages" ).show();
}

function isDraw(arrElements) {
  var flag = false;
  var message = '';
  for (var i = 0; i < arrElements.length; i++){
    if (arrElements[i][1] != '') {
      flag = true;
    } else {
      flag = false;
      break;
    }
  }
  if (flag) {
    if (!isRowCrossed(arrElements)) {
      if (!isColumnCrossed(arrElements)) {
        if (!isDiagonalCrossed(arrElements)) {
          message += Drupal.t("Aah !! It's a Draw.");
          $( ".noughts-and-crosses-board div#game-messages" ).html( message );
          $( ".noughts-and-crosses-board div#game-messages" ).addClass( "success-messages" );
          $( ".noughts-and-crosses-board div#game-messages" ).show();
          playAgain();
          return true;
        }
      }
    }
  }
  return true;
}

function isRowCrossed(arrElements) {
  if (arrElements[0][1] == arrElements[1][1] && arrElements[1][1] == arrElements[2][1] && arrElements[0][1] != '') {
    markSuccess('row', 0);
    declareWinner(arrElements[0][1]);
    return true;
  } else if (arrElements[3][1] == arrElements[4][1] && arrElements[4][1] == arrElements[5][1] && arrElements[3][1] != '') {
    markSuccess('row', 1);
    declareWinner(arrElements[3][1]);
    return true;
  } else if (arrElements[6][1] == arrElements[7][1] && arrElements[7][1] == arrElements[8][1] && arrElements[6][1] != '') {
    markSuccess('row', 2);
    declareWinner(arrElements[6][1]);
    return true;
  }
}

function isColumnCrossed(arrElements) {
  if (arrElements[0][1] == arrElements[3][1] && arrElements[3][1] == arrElements[6][1] && arrElements[0][1] != '') {
    markSuccess('column', 0);
    declareWinner(arrElements[0][1]);
    return true;
  } else if (arrElements[1][1] == arrElements[4][1] && arrElements[4][1] == arrElements[7][1] && arrElements[1][1] != '') {
    markSuccess('column', 1);
    declareWinner(arrElements[1][1]);
    return true;
  } else if (arrElements[2][1] == arrElements[5][1] && arrElements[5][1] == arrElements[8][1] && arrElements[2][1] != '') {
    markSuccess('column', 2);
    declareWinner(arrElements[2][1]);
    return true;
  }
}

function isDiagonalCrossed(arrElements) {
  if (arrElements[0][1] == arrElements[4][1] && arrElements[4][1] == arrElements[8][1] && arrElements[0][1] != '') {
    markSuccess('diagonal', 0);
    declareWinner(arrElements[0][1]);
    return true;
  } else if (arrElements[2][1] == arrElements[4][1] && arrElements[4][1] == arrElements[6][1] && arrElements[2][1] != '') {
    markSuccess('diagonal', 2);
    declareWinner(arrElements[2][1]);
    return true;
  }
}

})(jQuery);
