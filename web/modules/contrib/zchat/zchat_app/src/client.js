window.client = (function () {
  function getNewMessages(newest_message_time, success) {
    return fetch('/zchat_messages/' + newest_message_time, {
      headers: {
        Accept: 'application/json',
      },
    }).then(checkStatus)
      .then(parseJSON)
      .then(success);
  }

  function getMoreMessages(oldest_message_time, success) {
    return fetch('/zchat_messages/0/' + oldest_message_time, {
      headers: {
        Accept: 'application/json',
      },
    }).then(checkStatus)
      .then(parseJSON)
      .then(success);
  }

  function createMessage(data, success) {
    getCsrfToken(function (csrfToken) {
      return fetch('/entity/zchatmessage?_format=hal_json', {
        method: 'post',
        body: JSON.stringify(data),
        headers: {
          'Content-Type': 'application/hal+json',
          'X-CSRF-Token': csrfToken
        },
      }).then(checkStatus)
        .then(parseJSON)
        .then(success);
    });
  }

  function checkStatus(response) {
    if (response.status >= 200 && response.status < 300) {
      return response;
    } else {
      const error = new Error(`HTTP Error ${response.statusText}`);
      error.status = response.statusText;
      error.response = response;
      console.log(error);
      throw error;
    }
  }

  function parseJSON(response) {
    return response.json();
  }

  function parseText(response) {
    return response.text();
  }

  function reverseArray(response) {
    return response.reverse();
  }

  function getCsrfToken(callback) {
    return fetch('/rest/session/token', {
      method: 'get',
      headers: {
        'Accept': 'application/json',
      },
    }).then(checkStatus)
      .then(parseText)
      .then(callback);
  }

  return {
    getNewMessages,
    getMoreMessages,
    createMessage,
  };
}());
