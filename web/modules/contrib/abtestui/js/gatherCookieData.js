
function getTestDataForUser() {
  'use strict';

  var map = '{{ test_replace_map_content }}';

  var returnValue = "";
  var abCookieStart = '{{ cookie_prefix }}';
  var abCookieRegExp = new RegExp(abCookieStart+'[^;]*(;|$)','g');
  if (abCookieRegExp.test(document.cookie)) {
    var abCookieMatches = document.cookie.match(abCookieRegExp);

    for (var i = 0; i < abCookieMatches.length; i++){
      var testAndExp = abCookieMatches[i].replace(';','');
      testAndExp = testAndExp.replace(abCookieStart,'');

      // Replace testAndExp data according to the map.
      var testKey = testAndExp.match(/t_\d+/gi)[0];

      if (typeof map[testKey] === 'undefined') {
        console.log('Test key not found in map: ' + testKey);
        continue;
      }

      var regexp = new RegExp(Object.keys(map[testKey]).join("|"),"gi");
      testAndExp = testAndExp.replace(regexp, (function(matched) {
        return map[testKey][matched];
      }));

      returnValue += testAndExp + ', ';
    }
  }

  // Trim ', ' from the end of the string.
  returnValue = returnValue.replace(/(, $)/g, "");

  return returnValue;
}
