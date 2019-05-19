/**
 * @file
 * Vue main app.
 */

window.addEventListener('DOMContentLoaded', function () {
  vueApp();
});

function vueApp() {
  "use strict";

  new Vue({
    delimiters: ['${', '}'],
    comments: true,
    el: '.page',
    methods: {
      debug(text) {
        console.log(text);
      },
      numberFormat(number, decimals = 2, dec_point = '.', thousands_sep = ' ') {
        let s_number = Math.abs(
          parseInt(number = (+number || 0).toFixed(decimals))
        ) + "";
        let len = s_number.length;
        let tchunk = len > 3 ? len % 3 : 0;
        let ch_first = (tchunk ? s_number.substr(0, tchunk) + thousands_sep : '');
        let ch_rest = s_number.substr(tchunk).replace(/(\d\d\d)(?=\d)/g, '$1' + thousands_sep);
        let ch_last = decimals ? dec_point + (Math.abs(number) - s_number).toFixed(decimals).slice(2) : '';
        return ch_first + ch_rest + ch_last;
      },
      isEmpty(value) {
        if (!!value && value instanceof Array) {
          return value.length < 1
        }
        if (!!value && typeof value === 'object') {
          for (var key in value) {
            if (hasOwnProperty.call(value, key)) {
              return false
            }
          }
        }
        return !value
      }
    }
  });
}
