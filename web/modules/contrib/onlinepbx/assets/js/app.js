/**
 * @file
 * Author: Synapse-studio.
 */

Vue.filter('formatDate', function (value) {
  if (value) {
    var date = new Date(value);
    var hours = date.getHours();
    var minutes = '0' + date.getMinutes();
    var seconds = '0' + date.getSeconds();
    var formattedTime = hours + ':' + minutes.substr(-2) + ':' + seconds.substr(-2);
    return formattedTime;
  }
});
var app = new Vue ({
  el: '#app',
  delimiters: ['${', '}'],
  data: {
    phones: [],
    users: []
  }
});
