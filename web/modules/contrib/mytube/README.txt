MyTube:
Limit the privacy risks of embedded video.
Module for Drupal v6. http://www.drupal.org/

Created by The Electronic Frontier Foundation - http://www.eff.org/
Modified and upgraded to work with Drupal 6 by Brian Swaney

Instructions:

MyTube prevents Flash content from automatically loading by replacing it with
user-activated, locally-hosted thumbnails. This protects users' privacy because
it prevents automatic remote requests and Flash cookies. Effectively, until the
user clicks "Play" no remote sites will receive statistics about the user
having visited your web page from user-embedded Flash videos. For viewers, the
experience is much like having Flashblock enabled for your entire site.
Additionally, without automatically loading embedded content (such as Flash),
content on your site will load faster, making it more accessible to users with
old computers or slow internet connections. Since many of the embeds are
modified to autoplay when finally loaded, users will not necessarily have to
"double-click" to load videos, and the process will be transparent for those
who are not technically-inclined or detail-oriented, especially when custom
thumbnails are set.

MyTube is a filter module, which means that it won't automatically work
but it provides the option for administrators to use it just about anywhere on
their website. After installing the module, administrators will need to enable
MyTube for any input formats that will allow embeds. To do so, go to
Administration > Site Configuration > Input Formats and modify the settings for
any formats you wish to use MyTube by clicking "configure" and check the box
titled "MyTube Filtering". It is strongly recommended that, unless this is
being used in a very tightly-managed environment, that administrators also
enable the HTML Filter and arrange it before MyTube in all formats using
MyTube, especially if relatively anonymous users are allowed to submit embed
code; failure to do so means it may be possible for users to inject arbitrary
scripts in using embeds. MyTube should not conflict with the HTML Filter, but
the appropriate tags should be allowed; any tags the administrator wishes to
restrict (such as <iframe> tags) can be restricted without breaking MyTube's
ability to filter other authorized tags. You do NOT have to allow all of these
tags if you do not want to.

For security reasons, MyTube will automatically implement the HTML Corrector
filter, whether that filter is enabled or not. If enabled, the HTML Corrector
will be run twice.

Beyond this initial setup, MyTube should require little if any configuration,
but offers several customizable options for appearance on its settings page.
You can access these settings under
Administration > Site Configuration > MyTube Filtering if you have the
"administer mytube" permission. If you are not logged in with userID 1, you
will need to give yourself this permission to proceed. All <embed>, <param> and
<object> tags passed through the MyTube Filtering filter will be replaced with
MyTube thumbnails. For individual videos, a custom thumbnail can be added by
adding thumb="$relative_directory" to the first affected tag. Default
thumbnails for YouTube™ and Vimeo™ videos are downloaded to
sites/default/files/mytube automatically and cannot be overridden.


Frequently asked questions:

What is MyTube?
See above.

How is this any different from NoScript or Flashblock?
NoScript and Flashblock are used by many privacy-conscious and security-
conscious users, and will work on all websites whether the administrator has
this module installed or not, but it will only work for those users. Many users
are either unaware of the privacy risks or unwilling to go through the extra
effort of clicking to allow every time they want to play a video or
individually whitelist all their favorite sites. In contrast, MyTube works for
all users, whether they have NoScript/Flashblock or not, but it only works on
your website. Additionally, MyTube makes slight modifications to embed code so
when users do click play, the video will automatically start, as if the user
had clicked play in the Flash embed itself (not supported for all websites).
Finally, MyTube displays a configurable disclaimer below each thumbnail warning
users about what will happen if they click play.

Won't all of this clicking-to-allow nonsense just annoy people and detract them
from my website?
This is a slight shortcoming of NoScript/Flashblock, and a general
useability/convenience tradeoff with privacy/security. MyTube is designed to
minimize this as much as possible - to a point that many users will not notice
it is there unless they know what to look for. It does so by automatically
fetching the corresponding thumbnails from supported video sharing websites
(and allowing custom thumbnails for others), and tweaking embed codes to
automatically play when they're finally loaded instead of waiting for the users
to click play a second time. Effectively, unless naive visitors either notice
the video is treated like a picture in their web browser before it plays, or
they see the disclaimer text below the thumbnail, they will click play, see the
video load, and never know any different. Meanwhile, privacy-conscious users
will notice and appreciate your extra effort to protect their privacy while
visiting your website.

Do you support HTML5?
Because the specifications for HTML5 are not finalized yet, and I have yet to
find any consistent documentation on how to use HTML5 to embed a video without
embed tags, it is not currently supported. HTML5 is on the road map, however.
Once HTML5 is finalized and officially supported in browsers, I will add HTML5
videos to MyTube.

Is this limited to videos?
No. A prime example of a non-video embed is Google Maps; in fact, the code
specifically dealing with Google autoplay had to be tweaked to make this work.
Any other embedded content will require explicit consent from individual users
before loading. In these sorts of cases, you should probably add a custom
thumbnail using the thumb= attribute. MyTube is not tested for sound clips, but
if you do embed audio files you should explicitly set a width and height.

What websites are currently supported?
Currently, Metacafé (metacafe.com), Vimeo (vimeo.com), and YouTube (youtube.com
and youtube-nocookie.com) are supported for automatic thumbnails AND autoplay.
This means that the correct thumbnail will automatically be found and the embed
code will be modified to automatically play once loaded (so the user only 
clicks play once).
Additionally, Google (video.google.com), EbaumsWorld (ebaumsworld.com),
MySpace Video (myspace.com) are fully supported for autoplay (again, by
tweaking the embed code slightly), while MTVN Studios (mtvnservices.com) is
partially supported. In testing, the results from mtvnservices.com were
inconsistent.
For all other videos, if you know how you should modify the embed code to
automatically load when the page loads, you should do so (MyTube will keep it
from automatically loading). If you know of such a video, let me know in a
feature request; it is very trivial to add a new site. Likewise alert me if you
know how to fetch thumbnails for an unsupported sharing website.


Learn more here:
http://www.eff.org/deeplinks/2008/02/embedded-video-and-your-privacy
http://opensource.osu.edu/mytube

For more information, contact swaneybr@opensource.osu.edu or chris@eff.org.

