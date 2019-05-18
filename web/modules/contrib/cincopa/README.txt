Cincopa.module - Integrate your Cincopa Multimedia with Drupal!

------
What is Cincopa?
------
Cincopa (https://www.cincopa.com/) is a new kind of way to experience, share and post 
your personal media, built on the idea that you and your media is in the 
center surrounded by many original products and plugins for other site.

New products are being built on Cincopa by developers and released frequently. 
Those products covering many areas of things that you can do with your media like
post, share, backup, synchronize, listen, mobilize, upload, view and socialize. 

You can find the list here: https://www.cincopa.com/media-platform/services

This module will enable you to insert Galleries from Cincopa (videos, images,
sound track and other cool stuff) into the body of your nodes, or any CCK field
which uses Input Formats. It uses an Input Filter for this.

This way, you can easily display dynamic and rich content inside your page - without
needed to fuddle with an endless amount of custom display modules, such as lightboxes,
galleries, audio players, etc.


------
How?
------
To insert a gallery, simply use the "[cincopa 123456789]" (without the curly brackets)
syntax in the body of the node, where '123456789' is the Cincopa Gallery ID (as shown 
on the Cincopa galleries management page in your account). Remember to select the Input
Format you've enabled the Cincopa filter for.

This tag will be replaced by the actual photo/video/audio gallery from Cincopa!

Please note: This module disables the caching of the body of nodes using the 
configured input format. If you don't plan to use these tags often, or have 
performance concerns, you could create a new Input Format, and be more selective as 
to where this input filter is used.

------
Installation
------
See INSTALL.txt


------
Questions?
------
Contact us: http://help.cincopa.com/hc/en-us/requests/new