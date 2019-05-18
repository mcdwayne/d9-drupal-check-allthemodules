/**
 * @file
 * Author: Synapse-studio.
 */

(function ($) {
  $(document).ready(function () {
    var $count = 0;
    var $domain = Base64.decode(drupalSettings.onlinepbx.url);
    var $key = Base64.decode(drupalSettings.onlinepbx.key);
    var $log = document.getElementById(drupalSettings.onlinepbx.log);
    onpbxconnect($domain, $key);

    // Update app.users DATA.
    function updateUsers (user) {
      var $newuser = true;
      $.each(app.users, function (key, value) {
        if (value.user === user.user) {
          $newuser = false;
          value.status = user.status;
          value.st = user.st;
          value.user = user.user;
          value.leg = user.leg;
          value.uuid = user.uuid;
          value.direction = user.direction;
        }
      });
      if ($newuser) {
        app.users.unshift(user);
      }
    }

    // Update app.phones DATA.
    function updatePhones(call) {
      var $newcall = true;
      $.each(app.phones, function (key, value) {
        if (value.uuid === call.uuid) {
          $newcall = false;
          value.uuid = call.uuid;
          value.type = call.type;
          value.client = call.client;
          value.user = call.user;
          value.date = call.date;
          value.gateway = call.gateway;
        }
      });
      if ($newcall) {
        app.phones.unshift(call);
      }
    }
    // Data parser.
    function vuedata(data, context) {
      if (context === 'channel_create') {
        var $call = false;
        if (data.direction === 'inbound') {
          if (data.destination_host === 'synapse.onpbx.ru') {
            $call = {
              uuid: data.uuid,
              type: 'out',
              client: data.destination_number,
              user: data.caller_name,
              date: data.created_stamp,
              gateway: ''
            };
          }
          else {
            $call = {
              uuid: data.uuid,
              type: 'in',
              client: data.caller_name,
              user: '',
              date: data.created_stamp,
              gateway: data.destination_number
            };
          }
        }
        if (data.status === 'answered') {
          $call = {
            uuid: data.uuid + '-answ',
            type: data.direction,
            client: data.caller_number,
            user: data.destination_number,
            date: data.timestamp,
            gateway: ''
          };
        }
        if ($call) {
          updatePhones($call);
        }
      }

      if (context === 'BLF') {
        var $status = data.status;
        var $st;
        var $message;
        switch ($status) {
          case 'unregistered':
            $st = '🔳';
            break;

          case 'hangup':
            $st = '✳️';
            break;

          case 'ringing':
            $st = '✴️';
            break;

          case 'answered':
            $st = '🔴';
            break;

          default:
            $st = '..';
        }
        var $direction = '📞';
        if (data.direction === 'in') {
          $direction = '✔️';
        }
        var $user = {
          status: $status,
          st: $st,
          user: data.uid,
          leg: data.other_leg,
          uuid: data.uuid,
          direction: $direction
        };
        updateUsers($user);
        if (data.status === 'ringing') {
          $message = 'ringing:' + data.uid + ' ' + $direction + ' ' + data.other_leg + '\n';
          $log.insertAdjacentHTML('afterbegin', $message);
        }
        else {
          if (data.status !== 'unregistered') {
            $message = 'BLF:' + data.uid + ':' + $st + ' (' + data.other_leg +
            ':' + $direction + ')\n';
            $log.insertAdjacentHTML('afterbegin', $message);
          }
        }
      }
    }

    // Events.
    function addlog(data, context) {
      // Vue.js  parser &  gateway.
      vuedata(data, context);
      var $log = false;
      // Old logs.
      if ($log) {
        $count++;
        var $cDate = new Date();
        var $message =
        $log.insertAdjacentHTML('afterbegin', $message);
        var $cTime = $cDate.getUTCHours() + ':' + $cDate.getUTCMinutes() + ':' + $cDate.getUTCSeconds();
        $log.insertAdjacentHTML('afterbegin', $count + ' | ' + $cTime + ' | ' + context + ':' +
          JSON.stringify(data, null, '\t') +
          '\n ---------------------------------- \n');
      }
    }

    // Connect.
    function onpbxconnect($domain, $key) {
      // Connection check.
      if (!onpbx.connected) {
        // Connect.
        onpbx.connect({
          domain : $domain,
          key: $key
        });

        // Event subscriber.
        onpbx.on('connect', function () {
          // Calls subscribe.
          onpbx.command('subscribe', {events: {calls: true}}, function (err, res) {
            console.dir(err)
            console.dir(res)
          });
          // Log.
          addlog('No data',  'Connected! ');
        });
        // Disconnect.
        onpbx.on('disconnect', function () { addlog('No data',  'Disconnected =( '); });

        // Channels.
        onpbx.on('blf', function (data) { addlog(data, 'BLF'); });
        onpbx.on('channel_create', function (data) { addlog(data, 'channel_create'); });
        onpbx.on('channel_answer', function (data) { addlog(data, 'channel_answer'); });
        onpbx.on('channel_destroy', function (data) { addlog(data, 'channel_destroy'); });
        onpbx.on('channel_bridge', function (data) { addlog(data, 'channel_bridge'); });
        onpbx.on('channel_application', function (data) { addlog(data, 'channel_application'); });
      }
      else {
        // Already connected.
        addlog('No data', 'We are already connected >_< ');
      }
    }
  });
})(this.jQuery);
