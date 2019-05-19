UUID Forward

With this module installed, you can browse to an entity by entering its UUID in the path.For example,
[site-url]/uuid/0bb3203c-add8-4cca-8e20-ffef8c8d3618.

After installing the module, be sure to give some roles permission to "Access UUID forwarding".

The behavior is provided through a redirect, which is the why the module is called UUID "Forward".

Additionally, any URL arguments passed to the original URL will be passed along to the redirect URL. For example,
[site-url]/uuid/0bb3203c-add8-4cca-8e20-ffef8c8d3618?_format=json&other_query=yes
will redirect you to
[site-url]/node/12?_format=json&other_query=yes.
