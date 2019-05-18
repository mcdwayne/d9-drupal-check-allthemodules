## Security Site

In the .htaccess file, enter the following code if it is not present:

```
# 403 For Development confguration fle
RedirectMatch 403 ^/themes/custom/<THEME_NAME>/gulpfile.js$
RedirectMatch 403 ^/themes/custom/<THEME_NAME>/package.json$

# Disable method TRACE and TRACK method - Http methods
RewriteCond %{REQUEST_METHOD} ^(TRACE|TRACK)
RewriteRule .* - [F]
```

In the Apache configuration set `ServerTokens` to `Prod`. In the Wodby wodby/apache image simply set
the `APACHE_SERVER_TOKENS` to `Prod`. This removes the Apache version and extensions details from the
`Server` HTTP response header.
