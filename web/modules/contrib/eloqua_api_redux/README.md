## Eloqua API Resources
https://docs.oracle.com/cloud/latest/marketingcs_gs/OMCAB/Developers/api_lp.htm
https://docs.oracle.com/cloud/latest/marketingcs_gs/OMCAC/rest-endpoints.html
https://docs.oracle.com/cloud/latest/marketingcs_gs/OMCAB/Developers/BulkAPI/bulk-API.htm

V1 vs V2
https://community.oracle.com/thread/3700267

## Managing oAuth Tokens
### My token has expired!
First, that is a good thing. Tokens are like cash, if you have it you 
can use it. You don't need to prove that token belongs to you, so 
don't let anyone steal your token. In order to lower the risk tokens 
should expire fairly quickly. If your token expires in 120s then it will 
be only usable during that window.

### What do I do if my token was expired?
Along with your access token, an authentication token is created. It's 
called the refresh token . It's a longer lived token, that it's associated 
to an access token and can be used to create a replica of your expired 
access token. You can then use that new access token normally. To use your 
refresh token you will need to make use of the Refresh Token Grant. 
That will return a JSON document with the new token and a new refresh token. 
That URL can only be accessed with your refresh token, even if your access 
token is still valid.

### What do I do if my refresh token was also expired, or I don't have a 
refresh token?
Then you will need to generate a new token from scratch. You can avoid 
this by refreshing your access token before your refresh token expires. 
This way you avoid the need to require the user to prove their identity 
to Drupal to create a new token. Another way to mitigate this is to use 
longer expiration times in your tokens. This will work, but the the 
recommendation is to refresh your token in time.
