(function($) {
  var conn = new WebSocket('ws://localhost:3000');
  conn.onopen = function(e) {
    console.log('Connection established!');
  };

  conn.onmessage = function(e) {
    $('.chat-messages').append('<p>' + e.data + '</p>');
  };

  var $form = $('#chat-form');
  $('body').on('submit', $form, function(e) {
    e.preventDefault();
    var textarea = $('#edit-chat-message');
    var message = textarea.val();
    conn.send(message);
    textarea.val('');
  });

})(jQuery);
