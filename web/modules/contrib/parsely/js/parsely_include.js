(function(d) {
    var site = drupalSettings.parsely['parsely-include'].apikey, // replace with the domain of your site (e.g. parsely.com)
        b = d.body,
        e = d.createElement("div");
    e.innerHTML = '<span id="parsely-cfg" data-parsely-site="'+site+'"></span>';
    e.id = "parsely-root";
    e.style.display = "none";
    b.appendChild(e);
})(document);
(function(s, p, d) {
    var h=d.location.protocol, i=p+"-"+s,
        e=d.getElementById(i), r=d.getElementById(p+"-root"),
        u=h==="https:"?"d1z2jf7jlzjs58.cloudfront.net"
            :"static."+p+".com";
    if (e) return;
    e = d.createElement(s); e.id = i; e.async = true;
    e.src = h+"//"+u+"/p.js"; r.appendChild(e);
})("script", "parsely", document);