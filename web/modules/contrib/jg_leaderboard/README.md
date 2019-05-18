While there are a lot of different unique cases for JustGiving integration with Drupal, this module does not cover you for all JustGiving integration needs instead it caters for two main functionalities such as:
- Event Leaderboard
- Event Creation

## Requirements
For this to work you would need a JustGivng API key, this can be obtained from JustGiving site by following this link https://developer.justgiving.com.
You will also need and an **event id** and event set up and running and one that has people raising money for this event, this is so you can be able to see this working. You may be able to do this on test JustGiving test environment i.e. by setting up an event and adding pages/teams - for this in turn you would need to find test card details as in this link https://justgivingdeveloper.zendesk.com/hc/en-us/articles/203606302-Test-cards-details-for-making-donations.

The easiest and recommended way is to get a live JustGiving **event id**. You may be able to search the internet for this and find an **event id** to test against.

## Installation
Using the UI go to `manage->extend then enable` **JustGiving Event Leaderboard** or using drush `drush en jg_leaderboard`.

## JustGiving Event Leaderboard
Once you have installed the module you can then be able to add JustGiving API key and event id on the custom block form and then place the block wherever you like in the site and then you will have a leaderboard for your event that displays top 10 teams/pages for that event. This assumes that event is already created and you have an event id.
To add this block:
- go to `stucture->blcok layout then find Just Giving Leaderboard and click on place block` 

## JustGiving Event Creation
This offers you to create an event from your site and at the end of the process you will be given the event id which can be very hand in many cases. To do this simply go to `admin/config/jg` and enter the api details.

## Environments
**Production**
https://api.justgiving.com/

**Sandbox**
https://api-sandbox.justgiving.com/

More information can be found on [JustGiving developer's guide](https://developer.justgiving.com/).

## Theme
Currently there is no theming for this block as wanted to leave this up to the user as site have different designs. To style this block you can do in a standard way under `theme->templates` or if you want complete custom theming then just uncomment line 135 in Leaderboard.php file which is located at `src/Plugin/Block/`, once done that you will need to make sure that line 133 is commented out. under this mmodule's `templates` there is a file called `event-leaderboard.html.twig` here you can change things as you like and get very creative.

### Event Creation Sample Code
Taken from this link https://github.com/JustGiving/JustGiving.Api.Sdk/wiki/RegisterEvent
`
$event = new Event();
$event->name = "Playing Mario for 48 hours for charity";
$event->description = "This is an event description";
$event->completionDate = "/Date(1437060163998+0100)/";
$event->expiryDate = "/Date(1437060163998+0100)/";
$event->startDate = "/Date(1405696963998+0100)/";
$event->eventType = "OtherCelebration";
$event->location = "Test Location";
$response = $client->Event->Create($event);
echo $response->next->rel;
`

### Sponsors
[Oxwebs](https://oxwebs.com "Digital Web Development Journal")

#### Helpful Links
- [Drush commands](https://drupalcommands.com "Drush Commands")
- [Drupal Console Commands](https://drupalcommands.com "Drupal Console Commands")
- [Tutorialslib](https://tutorialslib.com "Tutorialslib")




