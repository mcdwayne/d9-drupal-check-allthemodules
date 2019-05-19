## INTRODUCTION
Skyword provides a restful interface for integrating in with the skyword.com
content creation platform.

## REQUIREMENTS
- drupal:rest
- drupal:serialization
- simple_oauth:simple_oauth
- simple_oauth:simple_oauth_extras

## RECOMMENDED MODULES
Developers may wish to get and enable rest_ui.

## INSTALLATION

### I. MODULE SETUP

1. Install the Skyword module.
2. Make sure URLs are being corrected. See the section on "URL Re-Formatting"
for guidelines.
2. Optionally install the Skyword Service Account module. If you choose not to
do this step, you will need to manually create a user with the correct token
and secret, which may require manual database access or programming, and assign
that user to a role with the correct Skyword permissions.
3. If you installed the service account, assign a password to the new "skyword"
account.
4. Register a new oauth consumer using the info provided by the Skyword team.

### II. URL RE-FORMATTING GUIDELINES
Due to a technical problem inside the core of Drupal 8 (<= 8.5.3), core
developers have decided to require a URL parameter for any request that returns
anything other than HTML. As we need to return machine readable code (json) and
support other systems that do not have this requirement, a workaround has been
developed.

Please let your systems administrator know to modify the webserver to intercept
all calls matching /skyword/v1 and inject the query parameter "_format=json"
(the underscore is required). This should be an internal rewrite. Do NOT
implement this as a 3xx redirect.

The rule would take a URL like:

`/skyword/v1/taxonomy`

and internally change it to:

`/skyword/v1/taxonomy?_format=json`

**Apache Sample**. This could go in an .htaccess file or a vhost (or top level)
configuration:

`RewriteRule ^(skyword/v1/.*)$ $1?_format=json`

**Nginx Sample**. This would go in the appropriate nginx configuration file for
the virtual host in question:

`rewrite ^(/skyword/v1/.*) $1?_format=json`

**Further Reading**.
1. Original issue: https://www.drupal.org/node/2364011

### III. API SPEC

The REST API for Skyword is here:
https://github.com/skyword/skyword.github.io/blob/master/publish-api-oapi2.yaml
