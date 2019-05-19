Social Media Login for Twitter
==============================

This module provides Twitter Login API helps easy to install the module without
having composer dependency. Twitter OAuth PHP library helps web developer to 
integrate twitter login system by the quick, easy and powerful way. Once logged
via Twitter Application, we’ll store the user information into the user table.

The Twitter OAuth PHP library will be used in our script that supports OAuth
for Twitter’s REST API.

Twitter Apps Creation
=====================

To access Twitter API you need to create a Twitter App and get the Consumer
key & Consumer secret. If you haven’t already created a Twitter App, follow
the below steps to creating and configuring a Twitter App from the 
Application Management page.

1. At first go to the Application Management page and login with your Twitter
account.
2. Create New App with the following details.
	1. Name: Your application Name. This is shown to the user while 
	authorizing.

	2. Change the apps permission to Read and Write or Read, Write and 
	Access direct messages. For changing the apps permission, you need 
	to add a mobile number to your twitter account.

	3. Description: Your application Description. This is shown to user 
	while authorizing.

	4. Website: Your application website.

	5. Callback URL(*): After authorization, this URL is called with 
	oauth_token.
3. Change the apps permission to Read and Write or Read, Write and Access 
direct messages. For changing the apps permission, you need to add a 
mobile number to your twitter account.

Once Twitter App creation is completed, click on Test OAuth for testing OAuth.
After testing you would be redirected to the OAuth Settings page. From the 
OAuth Settings page, you’ll get the Consumer key and Consumer secret. 

Note this Consumer key and Consumer secret for later use in the script.
