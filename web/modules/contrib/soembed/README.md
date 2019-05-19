Simplify adding videos and other rich media by inserting URL
using oEmbed technology.

Installation

* Download and enable as usual.
* Configure text format at /admin/config/content/formats
- Check Simple oEmbed filter
- Reorder so that Simple oEmbed filter comes before Convert URLs into links. 
Any filter that operates on URL before Simple oEmbed filter can disrupt
desired result.
- Set the Providers you want to support. By default, youtube.com, vimeo.com, hulu.com, twitter.com, instagram.com, Google Docs, and Google Maps are supported. If you need others
- Set maximum width of the embedded media (default 500).
- If you need to be able to embed a video inline (in the middle of a paragraph, for example), check the "Replace in-line URLs" box.

Now you can put oEmbed supported URL in contents on its own line and it
will automatically turn to rich media such as videos. If not recognized,
the URL will display as it is. There are no dependencies and you can 
place URLs anywhere in the content.

Default supported domains are:
- youtube.com
- vimeo.com
- hulu.com
- twitter.com
- instagram.com
- Google Docs
- Google Maps
(Google Docs and Google Maps do not supply an soembed endpoint, so we use 'LOCAL' code to parse)

For the following domains:

- blip.tv
- dailymotion.com
- flickr.com
- smugmug.com
- slideshare.net
- viddler.com 
- qik
- revision3.com
- photobucket.com
- scribd.com
- wordpress.tv
- polldaddy.com
- funnyordie.com
- wistia.com

see ALTERNATIVE PROVIDERS section below. You will need to copy the corresponding line, and add it to the "Providers" field at /admin/config/content/formats. You can also add your own provider, just copy the pattern: [Url to match] | [oEmbed endpoint] | [Use regex (true or false)] 


The module was developed to mimic what WordPress does out of the box. It is 
designed to work with minimal configuration. For feature rich and configurable
solution, check out oEmbed module, http://drupal.org/project/oembed.

This module is also included as part of Simple Editor module.



ALTERNATIVE PROVIDERS

blip.tv:

http://blip.tv/* | http://blip.tv/oembed/ | false

---

dailymotion.com:

#http://(www\.)?dailymotion\.com/.*#i | http://www.dailymotion.com/services/oembed | true

---

flickr.com:

#http://(www\.)?flickr\.com/.*#i | http://www.flickr.com/services/oembed/ | true

---

smugmug.com:

#http://(.+\.)?smugmug\.com/.*#i | http://api.smugmug.com/services/oembed/ | true

---

slideshare.net:

http://(www\.)?slideshare.net/.*#i | http://www.slideshare.net/api/oembed/2 | true

---

viddler.com:

#http://(www\.)?viddler\.com/.*#i | http://lab.viddler.com/services/oembed/ | true

---

qik.com

http://qik.com/* | http://qik.com/api/oembed.json | false

---

revision3.com:

http://revision3.com/* | http://revision3.com/api/oembed/ | false

---

photobucket pattern #1:

http://i*.photobucket.com/albums/* | http://photobucket.com/oembed | false

---

photobucket pattern #2:

http://gi*.photobucket.com/groups/* | http://photobucket.com/oembed | false

---

scribd.com:

#http://(www\.)?scribd\.com/.*#i | http://www.scribd.com/services/oembed | true

---

wordpress.tv:

http://wordpress.tv/* | http://wordpress.tv/oembed/ | false

---

polldaddy.com:

#http://(.+\.)?polldaddy\.com/.*#i | http://polldaddy.com/oembed/ | true

---

funnyordie.com:

#http://(www\.)?funnyordie\.com/videos/.*#i | http://www.funnyordie.com/oembed | true

---

wistia.com:

#https?:\/\/(.+)?(wistia.com|wi.st)\/.*# | http://fast.wistia.com/oembed | true