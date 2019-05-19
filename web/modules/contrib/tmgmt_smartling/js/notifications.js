const NotificationsManager = function(firebase, data, appName) {
    this.data = data;
    this.spaceId = "tmgmt_smartling";
    this.objectId = "notifications";

    if (data.config && data.token) {
        firebase.initializeApp(data.config, appName);
        firebase.auth().onAuthStateChanged(function(user) {
            if (!user) {
                firebase.auth().signInWithCustomToken(data.token);
            }
        });
    }

    var thisContext = this;

    this.listen = function(event, callback) {
        firebase.database().ref()
            .child("accounts")
            .child(this.data.accountUid)
            .child("projects")
            .child(this.data.projectId)
            .child(this.spaceId)
            .child(this.objectId).on(event, function(snap) {
                callback(snap, thisContext);
            });

        return this;
    };

    this.deleteRecord = function(recordId) {
        var url = "/tmgmt-smartling/firebase/projects/" + this.data.projectId + "/spaces/" + this.spaceId + "/objects/" + this.objectId + "/records/" + recordId;
        jQuery.ajax({
            url: url,
            method: "DELETE"
        });
    }
};

Drupal.behaviors.smartlingInitMessageWraper = {
    attach: function (context, settings) {
        jQuery("body", context).append("<div class='tmgmt-smartling-messages-wrapper'></div>");
    }
};

(function ($) {
    var configs = drupalSettings.tmgmt_smartling.firebase.configs;

    for (var i = 0; i < configs.length; i++) {
        var notificationManager = new NotificationsManager(
            firebase,
            drupalSettings.tmgmt_smartling.firebase.configs[i],
            i == 0 ? "[DEFAULT]" : Math.random().toString()
        );

        notificationManager.listen("child_added", function(snap, notificationManager) {
            var id = snap.key;
            var messageData = snap.val().data;
            var $wrapper = $(".tmgmt-smartling-messages-wrapper");
            var $message = $(
                '<div id="' + id + '" role="contentinfo" aria-label="Status message" class="tmgmt-smartling-message messages messages--'+ messageData.type + '">' +
                '<h2 class="visually-hidden">Status message</h2>' + messageData.message +
                '</div>'
            );
            $message.on("click", function() {
                notificationManager.deleteRecord(id);
                $message.slideUp(100, function() {
                    $message.remove();
                });
            });
            $wrapper.append($message);
            $message.slideDown(100);
        }).listen("child_removed", function(snap) {
            var $target = $(".tmgmt-smartling-messages-wrapper #" + snap.key);

            $target.slideUp(100, function() {
                $target.remove();
            });
        });
    }

})(jQuery);
