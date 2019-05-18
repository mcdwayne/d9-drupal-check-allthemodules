
function getTestDataForUser() {
  'use strict';

  var returnValue = "";
  var abCookieStart = '{{ cookie_prefix }}';
  var abCookieRegExp = new RegExp(abCookieStart+'[^;]*(;|$)','g');
  if (abCookieRegExp.test(document.cookie)) {
    var abCookieMatches = document.cookie.match(abCookieRegExp);
    for (var i = 0; i < abCookieMatches.length; i++){
      var testAndExp = abCookieMatches[i].replace(';','');
      testAndExp = testAndExp.replace(abCookieStart,'');

      testAndExp = testAndExp.replace('t_', 'Test ');
      testAndExp = testAndExp.replace('e_', 'Case ');
      testAndExp = testAndExp.replace('=', ' with ');

      returnValue += testAndExp + '|';
    }
  }
  return returnValue;
}
