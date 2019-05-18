/**
 * @file
 * Baidu Push auto-push script to publish the current URL to baidu.com.
 */

(function () {

  'use strict';

  var bp = document.createElement('script');
  var curProtocol = window.location.protocol.split(':')[0];
  if (curProtocol === 'https') {
    bp.src = 'https://zz.bdstatic.com/linksubmit/push.js';
  }
  else {
    bp.src = 'http://push.zhanzhang.baidu.com/push.js';
  }
  var s = document.getElementsByTagName('script')[0];
  s.parentNode.insertBefore(bp, s);

})();
