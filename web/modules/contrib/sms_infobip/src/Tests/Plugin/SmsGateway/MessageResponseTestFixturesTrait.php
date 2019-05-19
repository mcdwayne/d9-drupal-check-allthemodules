<?php

namespace Drupal\sms_infobip\Tests\Plugin\SmsGateway;

/**
 * Provides sample text for use in testing Infobip gateway responses.
 */
trait MessageResponseTestFixturesTrait {

  protected $testMessageResponse1 = <<<EOF
{  
   "bulkId": "BULK-ID-123-xyz",
   "messages":[  
      {  
         "to":"41793026727",
         "status":{  
            "groupId":0,
            "groupName":"ACCEPTED",
            "id":0,
            "name":"MESSAGE_ACCEPTED",
            "description":"Message accepted"
         },
         "smsCount":1,
         "messageId":"12db39c3-7822-4e72-a3ec-c87442c0ffc5"
      },
      {  
         "to":"41793026731",
         "status":{  
            "groupId":0,
            "groupName":"ACCEPTED",
            "id":0,
            "name":"MESSAGE_ACCEPTED",
            "description":"Message accepted"
         },
         "smsCount":1,
         "messageId":"bcfb828b-7df9-4e7b-8715-f34f5c61271a"
      },
      {  
         "to":"41793026785",
         "status":{  
            "groupId":0,
            "groupName":"ACCEPTED",
            "id":0,
            "name":"MESSAGE_ACCEPTED",
            "description":"Message accepted"
         },
         "smsCount":2,
         "messageId":"5f35f87a2f19-a141-43a4-91cd81b85f8c689"
      }
   ]
}
EOF;

  protected $testDeliveryReport1 = <<<EOF
{
   "results":[
      {
         "bulkId":"08fe4407-c48f-4d4b-a2f4-9ff583c985b8",
         "messageId":"12db39c3-7822-4e72-a3ec-c87442c0ffc5",
         "to":"41793026727",
         "sentAt":"2015-02-12T09:50:22.221+0100",
         "doneAt":"2015-02-12T09:50:22.232+0100",
         "smsCount":1,
         "mccMnc": "22801",
         "price":{
            "pricePerMessage":0.01,
            "currency":"EUR"
         },
         "callbackData": "reset_password",
         "status":{
            "groupId":3,
            "groupName":"DELIVERED",
            "id":5,
            "name":"DELIVERED_TO_HANDSET",
            "description":"Message delivered to handset"
         },
         "error":{
            "groupId":0,
            "groupName":"OK",
            "id":0,
            "name":"NO_ERROR",
            "description":"No Error",
            "permanent":false
         }
      },
      {
         "bulkId":"80664c0c-e1ca-414d-806a-5caf146463df",
         "messageId":"bcfb828b-7df9-4e7b-8715-f34f5c61271a",
         "to":"41793026731",
         "sentAt":"2015-02-12T09:51:43.123+0100",
         "doneAt":"2015-02-12T09:51:43.127+0100",
         "smsCount":1,
         "mccMnc": "22801",
         "price":{
            "pricePerMessage":0.01,
            "currency":"EUR"
         },
         "callbackData": "User defined data.",
         "status":{
            "groupId":3,
            "groupName":"DELIVERED",
            "id":5,
            "name":"DELIVERED_TO_HANDSET",
            "description":"Message delivered to handset"
         },
         "error":{
            "groupId":0,
            "groupName":"OK",
            "id":0,
            "name":"NO_ERROR",
            "description":"No Error",
            "permanent":false
         }
      },
      {
         "bulkId":"80664c0c-e1ca-414d-806a-5caf146463df",
         "messageId":"5f35f87a2f19-a141-43a4-91cd81b85f8c689",
         "to":"41793026785",
         "sentAt":"2017-02-12T09:55:43.123+0100",
         "doneAt":"2017-02-12T09:56:43.127+0100",
         "smsCount":1,
         "mccMnc": "22801",
         "price":{
            "pricePerMessage":0.01,
            "currency":"EUR"
         },
         "callbackData": "User defined data.",
         "status":{
            "groupId":3,
            "groupName":"DELIVERED",
            "id":5,
            "name":"DELIVERED_TO_HANDSET",
            "description":"Message delivered to handset"
         },
         "error":{
            "groupId":0,
            "groupName":"OK",
            "id":0,
            "name":"NO_ERROR",
            "description":"No Error",
            "permanent":false
         }
      }
   ]
}
EOF;

  protected $testMessageResponse2 = <<<EOF
{
   "messages":[
      {
         "to":"41793026727",
         "status":{
            "groupId":0,
            "groupName":"ACCEPTED",
            "id":0,
            "name":"MESSAGE_ACCEPTED",
            "description":"Message accepted"
         },
         "smsCount":1,
         "messageId":"2250be2d4219-3af1-78856-aabe-1362af1edfd2"
      }
   ]
}
EOF;

  protected $testDeliveryReport2 = <<<EOF
{
   "results":[
      {
         "bulkId":"80664c0c-e1ca-414d-806a-5caf146463df",
         "messageId":"2250be2d4219-3af1-78856-aabe-1362af1edfd2",
         "to":"41793026727",
         "sentAt":"2017-08-22T09:51:43.123+0100",
         "doneAt":"2017-08-22T09:55:43.123+0100",
         "smsCount":1,
         "mccMnc": "22801",
         "price":{  
            "pricePerMessage":0.01,
            "currency":"EUR"
         },
         "callbackData": "User defined data.",
         "status":{  
            "groupId":3,
            "groupName":"DELIVERED",
            "id":5,
            "name":"DELIVERED_TO_HANDSET",
            "description":"Message delivered to handset"
         },
         "error":{  
            "groupId":0,
            "groupName":"OK",
            "id":0,
            "name":"NO_ERROR",
            "description":"No Error",
            "permanent":false
         }
       }
   ]
}
EOF;

}
