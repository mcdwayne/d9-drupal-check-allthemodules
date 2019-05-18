This is an integration module for Amazon Echo devices, allowing Drupal to
respond to Alexa Skills Kit requests. Right now the module provides only a
basic integration. Developers will need to create their own customized
handler module (see the included alexa_demo module for an example) to handle
custom Alexa skills.

The Alexa module provides an endpoint that will accept Alexa
Skills Kit requests using the [Alexa JSON Interface for Custom Skills](
https://developer.amazon.com/public/solutions/alexa/alexa-skills-kit/docs/alexa-skills-kit-interface-reference).
A Symfony event is created for each request, allowing other modules to
subscribe to that event and respond to it with an Alexa response. This response
is then forwarded back to the Alexa service.
