/**
 * @file
 * civilcomments.js
 */

(function(c, o, m, e, n, t, s){
  c[n] = c[n] || function() {
    var args = [].slice.call(arguments);
    (c[n].q = c[n].q || []).push(args)
    t = o.createElement(m);
    s = o.getElementsByTagName(m)[0];
    t.async = 1;
    t.src = [e].concat(args).join("/");
    s.parentNode.insertBefore(t, s);
  };
  c["CivilCommentsObject"] = c[n];
})(window, document, "script", "https://ssr.civilcomments.com/v1", "Civil");
Civil(drupalSettings.civilcomments.content_id,drupalSettings.civilcomments.site_id,drupalSettings.civilcomments.lang);
