Client Connection Manager
=========================

The Client Connection Manager aims to make it easier for other modules
to interact with an external (or local) connection/API through a
reusable and uniform Plugin setup.

Once a Client Connection plugin and module for  a specific service is
created, other modules can use this module, allowing for less rework to
actually connection and use the API, and more time to add new features
to their module. Other modules can now reuse config and connection
functionality, instead of having 3 different forms for 3 different
modules that connect to the same API.

The module establishes a plugin type called Client Connection. This
packages everything needed for an external connection, taking care of
some of the repetitive tasks needed by every module that is creating a
service to connection to an API, like:
 - Retrieving, validation, and saving configuration from a form
 - Plugin that can be extended with methods to fit any API's specific
 needs (a trait to connect to Guzzle is provided out of the box)
 - An easy way to setup a new form instance merely by providing a route.
 - Allows for dependent modules to alter the form and validation that is
 saved to the config, insuring that each module that is dependent, can
 easily extend and reuse the client connection.

Finally, this allows for connections to be contextual since the only
thing that matters is the plugin being loaded for a module to use it.
The Client Connection Manager provides a way to resolve configuration,
which can allow a sub-module to determine which plugin instance should
be loaded based on passed-in criteria (IE, you want to load a
user-specific plugin versus the site-wide plugin) while not creating
more configuration and a new form and everything else.
This makes connection to a service more componentized and hot-swappable.