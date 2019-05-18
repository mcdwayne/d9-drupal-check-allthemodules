<?='<?xml version="1.0" encoding="UTF-8"?>'?>
<RTRequest>
  <apiKey><?=$data['api_key']?></apiKey>
  <document>
    <language><?=strtoupper($data['lang'])?></language>
    <annotypes><?=$data['annotypes']?></annotypes>
    <content><![CDATA[<?=$data['content']?>]]></content>
    <topic><?=$data['topic']?></topic>
    <url></url>
  </document>
</RTRequest>