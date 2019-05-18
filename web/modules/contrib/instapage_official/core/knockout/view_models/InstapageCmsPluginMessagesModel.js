/* globals  ko, instapageKO, iAjax, INSTAPAGE_AJAXURL */

var InstapageCmsPluginMessagesModel = function InstapageCmsPluginMessagesModel() {
  var self = this;

  self.options = {
    autoRemove: false,
    messageDuration: 5000
  };
  self.messages = instapageKO.observableArray();
  self.addMessage = function addMessage(text, type) {
    self.messages.push(new Message(text, type));

    if (self.options.autoRemove) {
      setTimeout(self.removeOldestMessage, self.options.messageDuration);
    }
  };
  self.removeOldestMessage = function removeOldestMessage() {self.messages.shift();};
  self.removeMessage = function removeMessage(message) {self.messages.remove(message);};
  self.clear = function clear() {self.messages([]);};
};

var Message = function Message(text, type) {
  var self = this;

  self.text = text;
  self.type = type;
};
