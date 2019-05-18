# Plugin Decorator

To decorate plugins of type Foo, simply
* Acivate FooDecorator plugin type via a magick entry in your services.yml file.
* Implement FooDecorator plugin as usual.

For an example implementation see the media_imagick module.

# Notes

V1 decorated plugins by altering the plugin definition array. It turned out that
the decorated plugin's constructor needs some method ->createInstanceFromDefinitionArray()),
but there is only ->createInstancefromId().
So V2 did some container-fu, letting a magick service definition trigger decorating the
plugin manager, which then can decorate the plugin itself on the fly, without altering
the plugin definition in the first place.
It turned out that this summoned dragons: In some places like MediaTypeForm::__construct
the MediaSourceManager class is typehinted, and it is likely to be like this in other places.
Adding and using a MediaSourceManagerInterface would not work well with the generic and
type agnostic plugin decoration. Better might be to add and use a PluginManagerBaseInterface.
