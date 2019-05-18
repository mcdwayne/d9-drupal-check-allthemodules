Guzzle REST Generator


Module to easily perform real-time external REST requests and converted to Drupal REST endpoints from your app.

This helps in hiding your Access Key as the external url(ex: https://api.meetup.com/2/cities?&key=xxxxxxxxxxxxxxxxxx) is internally called from your app and not from the client browser.

The module creates a content type "Guzzle Drupal Endpoint" which is as shown in the figure and currently supports GET and POST calls only using Guzzle.

To use this module:
1) Add Content for type "Guzzle Drupal Endpoint".
2) Fill in the Request values as shown in figure and choose the desired Third-Party External URL(Facebook API, Meetup API etc.), Request Method(GET/POST), fill in the Request Headers, Payload Data.
3) Hit Publish and Save.

You will be redirected to the generated link which can be used in your code as a Drupal Endpoint from your site directly
(Eg: Calling the content node in Javascript

$.ajax({
  url: "/node/2"
}).done(function() {  
});
)

I am actively looking for contributors, please feel free to reach out to me at digantjgtp@gmail.com for suggestions, improvements or anything :)
