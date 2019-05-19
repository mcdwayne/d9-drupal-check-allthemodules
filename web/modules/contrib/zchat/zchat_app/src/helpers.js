window.helpers = (function () {
  function newMessage(message_text = '') {
    const message_server = {
      _links: {
        type: {
          href: drupalSettings.path.getHost + '/rest/type/zchatmessage/zchatmessage'
        }
      },
      message_text: {
        value: message_text
      },
      name: {
        value: message_text.substring(0, 50)
      }
    };
    const message_local = {
      message_id: 'new_message',
      message_text: message_text,
      author_uid: drupalSettings.user.uid,
      author_name: Drupal.t('you'),
      message_created: Drupal.t('just now'),
    };

    return {
      message_server: message_server,
      message_local: message_local,
    };
  }

  function getNewestMessageFromList (serverMessages) {
    if (serverMessages.length > 0) {
      return serverMessages[0].message_created_time;
    }
    else {
      return 0;
    }
  }

  function getOldestMessageFromList (serverMessages) {
    if (serverMessages.length > 0) {
      return serverMessages[serverMessages.length - 1].message_created_time;
    }
    else {
      return 0;
    }
  };

  return {
    newMessage,
    getNewestMessageFromList,
    getOldestMessageFromList,
  };
}());
