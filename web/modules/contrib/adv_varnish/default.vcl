/** Set backend setting here **/
backend default {
    .host = "127.0.0.1";
    .port = "80";
}

import std;

sub vcl_fetch {

    /** Enable ESI if requested on this page */
    if (beresp.http.X-DOESI) {
      set beresp.do_esi = true;
    }

    /** Set desired TTL and Grace. */
    set beresp.ttl = std.duration(beresp.http.X-TTL + "s", 0s);
    set beresp.grace = std.duration(beresp.http.X-GRACE + "s", 0s);

    /** Response contains Cookie set, so not caching this request **/
    if (beresp.http.Set-Cookie) {
        set beresp.http.X-Cacheable = "NO:Cookie in the response";
        set beresp.ttl = 0s;
    }
    elsif (beresp.ttl <= 0s) {
        /** If TTL is not set than we won't cache this request **/
        set beresp.http.X-Cacheable = "NO:Not Cacheable";
    }
    elsif ( beresp.http.Cache-Control ~ "private") {
        /** Request with Private header shouldn't be cacheble (ex Drupal BO) **/
        set beresp.http.X-Cacheable = "NO:Cache-Control=private";
        return(hit_for_pass);
    }
    else {
        /** In all other cases we will cache Drupal response **/
        set beresp.http.X-Cacheable = "YES";
    }

    /** Debug actual TTL */
    set beresp.http.X-TTL2 = beresp.ttl;

    return (deliver);
}

sub vcl_recv {

    /** This is maximum allowed graced, actual grace set in vcl_fetch. */
    set req.grace = 1h;

    /** Make backend aware of varnish. */
    set req.http.X-AVC = "1";

    /** Default routine */
    if (req.restarts == 0) {
        /** Send user IP to Drupal **/
        if (req.http.x-forwarded-for) {
            set req.http.X-Forwarded-For =
                req.http.X-Forwarded-For + ", " + client.ip;
        } else {
            set req.http.X-Forwarded-For = client.ip;
        }
    }
    if (req.request != "GET" &&
      req.request != "HEAD" &&
      req.request != "PUT" &&
      req.request != "POST" &&
      req.request != "TRACE" &&
      req.request != "OPTIONS" &&
      req.request != "DELETE") {
        /* Non-RFC2616 or CONNECT which is weird. */
        return (pipe);
    }
    if (req.request != "GET" && req.request != "HEAD") {
        /* We only deal with GET and HEAD by default */
        /* If request contains fingerprint - we might want to cache it based on that fingerprint */
        if (req.request == "POST" && req.url ~ "^.*fingerprint-.*$") {
	  return (lookup);
        }
        else {
	  return (pass);
	}
    }
    if (req.http.Authorization) {
        /* Not cacheable by default */
        return (pass);
    }
    return (lookup);
}

sub vcl_hash {

    /** Default hash */
    hash_data(req.url);
    if (req.http.host) {
        hash_data(req.http.host);
    } else {
        hash_data(server.ip);
    }

    /** Place ajax into separate bin */
    if (req.http.X-Requested-With) {
        hash_data(req.http.X-Requested-With);
    }

    /** Process authenticated users */
    if (req.http.Cookie ~ "^.*?SESS[^=]*=([^;]{5});*.*$") {

        /** Extraxt full session value */
        set req.http.X-SESS = regsub(req.http.Cookie, "^.*?SESS([^;]*);*.*$", "\1");

        /** Extract bin headers */
        if (req.http.Cookie ~ "^.*?AVCEBIN=([^;]*);*.*$") {
            /* Set default mode to per role */
            set req.http.X-BIN  = "role:" + regsub(req.http.Cookie, "^.*?AVCEBIN=([^;]*);*.*$", "\1");
        }

        /** DRUPAL_CACHE_PER_PAGE */
        if (req.url ~ "^.*cachemode=1.*$" && req.http.X-BIN) {
            /** Set bin to constant */
            set req.http.X-BIN = "page:" + regsub(req.http.X-BIN, "^.*-([^-]*)$", "\1");
        }

        /** DRUPAL_CACHE_PER_USER */
        if (req.url ~ "^.*cachemode=3.*$" || !req.http.X-BIN) {
            /** Set user session as bin */
            set req.http.X-BIN  = "user:" + req.http.X-SESS;
        }

        set req.http.X-URL = req.url;

    }
    else {
        /** Extract bin headers */
        if (req.http.Cookie ~ "^.*?AVCEBIN=([^;]*);*.*$") {
            /* Set default mode to per role */
            set req.http.X-BIN  = "role:" + regsub(req.http.Cookie, "^.*?AVCEBIN=([^;]*);*.*$", "\1");
        }
        else {
            set req.http.X-BIN = "anonymous";
        }
    }

    /** If Bin is set - add it to hash data for this page */
    if (req.http.X-BIN) {
        hash_data(req.http.X-BIN);
    }

    return (hash);
}

sub vcl_hit {

    /** Debug */
    set req.http.X-Cache-TTL = obj.ttl;
    return (deliver);
}

sub vcl_deliver {

    /** Debug */
    if (obj.hits > 0) {
        set resp.http.X-Cache = "HIT";
        set resp.http.X-Cache-Hit-Times = obj.hits;
    }
    else {
        set resp.http.X-Cache = "MISS";
    }

    if (req.http.X-Cache-TTL) {
        set resp.http.X-Cache-TTL = req.http.X-Cache-TTL;
        unset req.http.X-Cache-TTL;
    }

    if (req.http.X-BIN) {
        set resp.http.X-BIN = req.http.X-BIN;
    }

    /** Unset unused headers */
    unset resp.http.Server;
    unset resp.http.X-Powered-By;
    unset resp.http.Expires;
    unset resp.http.Last-Modified;
    unset resp.http.Content-Language;
    unset resp.http.Link;
    unset resp.http.X-Generator;
    unset resp.http.Vary;
    unset resp.http.Via;
    unset resp.http.Connection;
    unset resp.http.Date;
    unset resp.http.X-Varnish;

    /** Unset tags header if not in debug */
    if (!resp.http.X-CACHE-DEBUG) {
	    unset resp.http.X-Drupal-Cache-Contexts;
	    unset resp.http.X-Drupal-Cache-Tags;
        unset resp.http.X-BIN;
        unset resp.http.X-TAG;
        unset resp.http.X-TTL2;
        unset resp.http.X-RNDPAGE;
        unset resp.http.X-RNDGOTO;
        unset resp.http.X-Cache-TTL;
    }

    return (deliver);
}
