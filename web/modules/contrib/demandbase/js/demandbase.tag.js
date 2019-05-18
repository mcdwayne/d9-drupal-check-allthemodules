if (typeof(drupalSettings.demandbase) !== 'undefined' && drupalSettings.demandbase.tag_id) {
  (function(d,b,a,s,e){ var t = b.createElement(a),
    fs = b.getElementsByTagName(a)[0]; t.async=1; t.id=e; t.src=s;
    fs.parentNode.insertBefore(t, fs); })
  (window,document,'script','https://tag.demandbase.com/' + drupalSettings.demandbase.tag_id + '.min.js','demandbase_js_lib');
}