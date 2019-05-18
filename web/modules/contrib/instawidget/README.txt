It is an Social Widget To Display Instagram Feeds in an Block

Below are the Steps to Configuration:-

1.Login to Your Instagram Account in a Different tab.

1.Go to /admin/config/instagram-settings on your Drupal Website.,after enabling the instawidget Module.

2.You need to create an Client by registering it.While you are logined on Instagram,Go on this below Url:-

https://www.instagram.com/developer/clients/register/

3.You will get an Client Id ,while you are finished with registering process.

4.Fill in the Client id the Instagram settings on Your Drupal Website.

5.Get Your User Id for Instagram Account by using the link below:-
https://www.instagram.com/{username}/?__a=1
Find the Id in the json format you are getting.

6.Fill the user Id you Got above in the Instagram settings Form field in your drupal admin.

7.Copy the Redirect Uri,that you see in the Instagram Settings Form Field 'Redirect Uri'

8.Paste the copied Uri in the Client Registeration "Security Tab" in Valid redirect URIs

9.Uncheck the "Disable implicit OAuth" option on Security Tab.

10.Save Settings on Client Registeration form for Instagram.

10.Also Save the Instagram Settings on your Drupal admin Configuration form.

11.Move to Access Token TAB on Configuration form in Drupal admin.

12.You will recieve an URl with "/admin/config/instagram-settings/access-token#access_token="Token-value""

13.Fill in the Token-value in the Access Token Field.

14.Save the Access Token Form Settings.

15.Place the Instagram Block on the desired region and your Instagram Feeds will start appearing on the Page where Block is placed.
