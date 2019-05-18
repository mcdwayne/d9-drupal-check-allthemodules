/**
 * @file
 * Javascript behaviors for the Christmas Lights module.
 */

(function() {
  var value = 0;
  setInterval(function() {
    document.getElementById('christmas-lights').style.backgroundPosition = '0 ' + value + 'px';
    value = value + 36;
    if (value > 108) {
      value = 0;
    }
  }, 200);
})();
