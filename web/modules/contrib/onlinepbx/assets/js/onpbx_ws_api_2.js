/**
 * @file
 * Source: api.onlinepbx.ru/lib/onpbx_ws_api_2.js.
 */

function Onpbx() {
  this.connected = false;
  this.transport = false;
  this.default_settings = {"port": 8093};

  var socket = false;
  var callbacks_events = {};
  var socket_events = {};
  var commands_buffer = [];
  var charList = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
  var charListLen = charList.length;
  var that = this;

  this.command = function (e, data, f) {
    if (typeof f == 'undefined' && typeof data == 'function') {
      f = data;
      data = {};
    }
    else if (typeof data == 'undefined') {
      data = {};
    }
    if (that.connected) {
      sendCommand(e, data, f);
    }
    else {
      commands_buffer.push({'event': e, 'data': data, 'function': f});
    }
  }

  this.on = function (e, f) {
    if (typeof e == 'string' && typeof f == 'function') {
      socket_events[e] = f;
    }
  }

  var sendCommand = function (e, data, f) {
    data['callback_hash'] = addCallback(f);
    socket.emit(e, data);
  }

  var genStr = function (l) {
    if (typeof l != 'number') {
      l = 16;
    }
    var s = '';
    for (var i = 0; i < l; i++) {
      s += charList[(Math.floor(charListLen * Math.random(charListLen)) - 1)];
    }
    return s;
  }

  var addCallback = function (f) {
    if (typeof f == 'function') {
      var hash = genStr();
      callbacks_events[hash] = f;
      return hash;
    }
    return false;
  }

  var socketEventHandler = function (e, data) {
    if (e == 'connect') {
      that.connected = true;
      while (commands_buffer.length > 0) {
        sendCommand(commands_buffer[0]['event'], commands_buffer[0]['data'], commands_buffer[0]['function']);
        commands_buffer.splice(0, 1);
      }
    }
    else if (e == 'connecting') {
      that.transport = data;
    }
    else if (e == 'disconnect') {
      that.connected = false;
      that.transport = false;
    }
    else if (e == 'callback') {
      if (data && data['hash'] && typeof callbacks_events[data['hash']] == 'function') {
        var f = callbacks_events[data['hash']];
        delete callbacks_events[data['hash']];
        f(data);
      }
      return true;
    }
    if (typeof socket_events[e] == 'function') {
      socket_events[e](data);
    }
  }

  var socketConnect = function (data) {
    socket = io.connect(socketUrl(data), {"query": "key=" + data['key'] + "&domain=" + data['domain'], "force new connection":true});
    socket.$emit = socketEventHandler;
  }

  var socketUrl = function (data) {
    return "https://" + data['domain'] + ":" + data['port'];
  }

  this.connect = function (data) {
    if (!data || typeof data['domain'] != 'string' || typeof data['key'] != 'string') {
      return false;
    }
    if (!data['port']) {
      data['port'] = that.default_settings['port'];
    }

    if (typeof io != "undefined") {
      socketConnect(data);
    }
    else {
      var newScript = document.createElement('script');
      newScript.type = 'text/javascript';
      newScript.src = socketUrl(data);
      newScript.onload = function () {
        socketConnect(data);
      }
      document.getElementsByTagName("head")[0].appendChild(newScript);
    }
  }
}
var onpbx = new Onpbx();
