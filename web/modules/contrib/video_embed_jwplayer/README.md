Video Embed JW Player
=======

This module provides JW Player + JW Platform handler for Video Embed Field.
Users can add JW Platform videos to their site by entering the video's embed
url or embed code.


Acceptable url formats for entry into a video embed field:

- Iframe embed url: //content.jwplatform.com/players/MEDIAID-PLAYERID.html
- JS embed url: //content.jwplatform.com/players/MEDIAID-PLAYERID.js
- Preview url: https://content.jwplatform.com/previews/MEDIAID-PLAYERID
- Catch all: Any url with //SUBDOMAIN.jwplatform.com/SOMETHING/MEDIAID-PLAYERID


Video Embed Output:

- Iframe embed: <iframe src="//content.jwplatform.com/players/MEDIAID-PLAYERI.html" allowfullscreen="allowfullscreen" frameborder="0" width="{formatter width setting}" height="{formatter height setting}"></iframe>
- Reference: https://developer.jwplayer.com/jw-platform/docs/developer-guide/delivery-api/embedding-players/


JW Player Embedding Support:

- SUPPORTED: JW Player (Cloud) + JW Platform.
- SUPPORTED: JW Player (Cloud) + Externally-Hosted Content.
- NOT SUPPORTED: JW Player (Self-Hosted) + JW Platform.
- NOT SUPPORTED: JW Player (Self-Hosted) + Externally-Hosted Content.
