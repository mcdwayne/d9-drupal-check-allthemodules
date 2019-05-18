#Regex Redirect 8.x-1.x

##CONTENTS OF THIS FILE

 * Description
 * Requirements
 * Installation
 * Configuration and use
 * Todo's

### DESCRIPTION

The Regex Redirect module is a path management module for Drupal 8.
It is an extension of the redirection API provided by the redirect module.

The primary use case for this module is to:

- **Create** redirects which will redirect all URLs matching a specific pattern
- **View** existing regex redirects in an admin view

The module itself may be found at [regex_redirect](https://www.drupal.org/project/regex_redirect)
Please report bugs in the [issue queue](https://www.drupal.org/project/issues/regex_redirect).

### REQUIREMENTS

This module requires the [redirect](https://www.drupal.org/project/redirect) module.


### INSTALLATION

The module is installed is the [regular manner](https://www.drupal.org/node/1897420).


### CONFIGURATION AND USE

    1. Enable to module at Administration > Extend
    2. View the regex redirects at Administration > Configuration >
       Search and Metadata > Regex redirects
    3. Create regex redirects by clicking "Add regex redirect". Set a title
       to recognize the redirect pattern by. The redirect source field should
       be a regular expression with named captures. The redirect path should
       contain the named capture variables. Also select the redirect status and
       language.
       
Examples:

| Source | Path |
| --- | --- |
| node&#92;/(?P&lt;id&gt;[0-9&#92;.]+) | news/&lt;id&gt; |
| page&#92;/(?P&lt;id&gt;[0-9&#92;.]+)&#92;/(?P&lt;month&gt;[a-z&#92;-]+) | event/&lt;id&gt;/date/&lt;month&gt; |


### TODO'S
- Make non-redirectable paths configurable.
- Add a unit test for the RegexRedirectRequestSubscriber
- Create kernel tests for RegexRedirect
- Create WebTestBase tests for RegexRedirectForm
- Handle module uninstall
