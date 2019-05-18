(function ($, Drupal) {
    Drupal.behaviors.liveChatSlackBehavior = {

        lastMsg: '',
        liveChatWindowIsOpened: false,
        numberOfMessages: 0,

        attach: function (context, settings) {

            // make chat window minimizable
            $('#live-chat header').once('minimizable').on('click', function() {
                $('.chat').slideToggle(300, 'swing');
                $('.chat-message-counter').fadeToggle(300, 'swing');
                this.liveChatWindowIsOpened = !this.liveChatWindowIsOpened;
                if(this.liveChatWindowIsOpened) {
                    $('.chat-message-counter').addClass('empty');
                }
            });

            // add close button functionality
            $('.chat-close').once('closable').on('click', function(e) {
                e.preventDefault();
                $('#live-chat').fadeOut(300);
            });

            // initially load conversation history
            this.updateHistory();

            $('#live-chat #msg').keydown(function(event){
                if(event.keyCode == 13) {
                    event.preventDefault();
                    return false;
                }
            });

            // ENTER key sends text to Slack
            $('#live-chat #msg').once('enter-active').keyup(function(e) {
                e.preventDefault();
                if(e.which == 13) {
                    // ENTER pressed
                    var txt = $(this).val();
                    if(txt == this.lastMsg) {
                        return;
                    }
                    if(txt.length > 0){
                        $.post("live_chat_slack/api/send_message", txt , function (data) {
                            $('#live-chat #msg').val('');
                            $.each(data, function(index, msg){
                                $('#chat-history').append(msg);
                                $('#chat-history').append('<hr>');
                            });
                            $('#chat-history').scrollTop($('#chat-history').prop("scrollHeight"));
                        });
                    }
                    this.lastMsg = txt;
                }
            });
        },

        updateHistory: function() {
            $.get("live_chat_slack/api/get_history", function(data) {
                $('#chat-history').html('');
                $.each(data, function(index, msg){
                    $('#chat-history').prepend(msg);
                    $('#chat-history').prepend('<hr>');
                });
                $('#chat-history').scrollTop($('#chat-history').prop("scrollHeight"));
                Drupal.behaviors.liveChatSlackBehavior.updateBadge(data.length);
                setTimeout(Drupal.behaviors.liveChatSlackBehavior.updateHistory, 10000);
            })
        },

        updateBadge: function(newAmount) {
            if(Drupal.behaviors.liveChatSlackBehavior.liveChatWindowIsOpened == false) {
                var flagCount = newAmount - Drupal.behaviors.liveChatSlackBehavior.numberOfMessages;
                if(flagCount > 0) {
                    $('.chat-message-counter').text(flagCount);
                    $('.chat-message-counter').removeClass('empty');
                }
            }
            Drupal.behaviors.liveChatSlackBehavior.numberOfMessages = newAmount;
        }
    };
})(jQuery, Drupal);