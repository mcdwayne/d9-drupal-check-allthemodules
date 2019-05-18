Whitelisting IPs in settings.php

Settings can be whitelisted in settings.php by creating the following array
containing each IP addresses to be whitelisted.

$config['restrict_ip.settings']['ip_whitelist'] = [
  '111.111.111.1',
  '111.111.111.2',
];

######################################
#
#  Questions and Answers
#
######################################

Question: I locked myself out of my site, what do I do?

Answer: Open add the following line to sites/default/settings.php

$config['restrict_ip.settings']['enable'] = FALSE;

You will now be able to access the site (as will anyone else). Go to the configuration page,
and fix your settings. Remove this code when you are finished.

--------------------------------------------

Question: I want to redirect users to a different site when they do not have access.
How can I do this?

Answer: You will need to write some code for this. The easiest way is in your theme
(though it can also be done in a custom module).

First, you'll need the theme key of your theme. This will be the name of the folder
that your theme lies in. My examples below will be for a fictional theme, jaypanify.

Next, open up the file named template.php in your theme. If this file does not exist,
you can create it (though most themes will already have it). At the bottom of this file,
add the hook below, changing 'hook' to your actual theme key, and changing the link
from google.com to the URL to which you want to redirect your users:

function hook_restrict_ip_access_denied_page_alter(&$page)
{
	$response = new \Symfony\Component\HttpFoundation\RedirectResponse('https://www.google.com/');
	$response->send();
}

Clear your Drupal cache, and the redirect should work.

--------------------------------------------

Question: I want to alter the access denied page. How can I do this?

Answer: It depends on whether you want to add to this page, remove from it, or alter it. However,
whichever it is, all methods work under the same principle.

First, you'll need the key of your theme (see the previous question for directions on how to get this).
Next you'll need to open template.php, and add one of the following snippets to this file. Note that you
will need to change 'hook' to the acutal name of your theme.

***

ADDING to the page:
The following example shows how to add a new element to the page

function hook_restrict_ip_access_denied_page_alter(&$page)
{
  // note that 'some_key' is arbitrary, and you should use something descriptive instead
  $page['some_key'] = [
    '#markup' => t('This is some markup which I would like to add'),
    '#prefix' => '<p>', // You can use any tag you want here,
    '#suffix' => '</p>', // the closing tag needs to match the #prefix tag
  ];
}

***

REMOVING from the page:
The following example shows how to remove the logout link for logged in users who are denied access

function hook_restrict_ip_access_denied_page_alter(&$page)
{
  if(isset($page['logout_link']))
  {
    unset($page['logout_link']);
  }
}

***

ALTERING the page:
As of the time of writing, this module provides the following keys that can be altered:
* access_denied
* contact_us (may not exist, depending on the module configuration)
* logout_link (may not exist, depending on the module configuration)
* login_link (may not exist, depending on the module configuration)

The following example shows how to change the text of the 'access denied' message to your own custom message

function jaypanify_restrict_ip_access_denied_page_alter(&$page)
{
  if(isset($page['access_denied']))
  {
  	$page['access_denied'] = t('My custom access denied message');
  }
}

--------------------------------------------------------

If you are having troubles with any of the above recipes, please open a support ticket in the
module issue queue: https://drupal.org/project/issues/search/restrict_ip

Please list the following:

1) What you are trying to achieve
2) The code you have tried that isn't working
3) A description of how it is not working
