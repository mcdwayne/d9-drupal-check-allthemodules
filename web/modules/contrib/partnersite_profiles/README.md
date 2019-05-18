Partnersite Profile Access lets an administrator manage partner sites to enable the partner site registered users able to access protected content as readers.

Though the primary use case for developing the module was for intranet environment , to enable the digital marketing team, to maintain other business units or organisation subsidiaries as profiles and grant their users access to organisation micro-sites without exclusively registering as user.  

Please Note: 

Though origin was for an intranet need, it is usable in internet world as well. Because we shouldn’t give away any real abilities above an authenticated user by default, this module ensures the same while granting read access anchored to partner site as profile. 

The module supports new access link formats developed as  @LinkGenerator type based on specific requirement.

Requirements:

Drupal 8 is required. Drupal 8.6.x or higher is suggested. Manually tested on 8.5.x.


How to install:

Please follow the same process as you would normally install a contributed Drupal module.
On installation, Partner user role is created and role is enabled required permission to use the “reader” access link based on configuration applied. Please refer to configuration steps to apply configuration details.

Configuration:

Step 1: Apply the Partner Profile settings and Save.
*    Navigate from Admin menu -> Click on ‘Extend’ and click on ‘Configure’ in the expanded ‘Partnersite Profile’ module row. (Or) Navigate to Configuration and under ‘System’ , Click on ‘Partnersite Profile Settings’.

Step 2: Create Partnersite Profiles.
* Navigate from Admin menu -> Click on ‘Structure’ and then click on ‘Partnersite Profiles’. Click on ‘Add Partnersite Profiles’ and fill in the profile details and save.
* Profile details ( Hint )
* Label : Profile Handle. Profile handle is used to auto-create a user account to enable reader access anchoring.
* Partner Email: Provide the contact email to receive the Access Link generated.
* Authenticated Division: This field holds ID  used to categorise the sub-departments or teams in business unit. In this version of release, this is retained only if required during access link generation.
* Secret key: This field is used to implement the API Key generation for custom link generation logic. 
* Secret hash: This field is used to implement the API Key generation for custom link generation logic.
* Expiry: This field is used to apply expiration timestamp.
* Hash Logic : The module comes with default two kinds of access link generator. 
* Native Link Generator : Aligned with Drupal native approach and uses user account details.
* Custom Link Generator : Aligned with Custom Algorithm implemented and above field profile details fields are utilised.

Step 3: Email User Access Link
* Navigate to ‘People’. Select the users ( Please ensure they are partner profiles ). Select “E-Mail access link to the selected user(s)” in the action drop down and click “Apply to selected items”.

Sample Access Link Formats:

   For Profiles with Native link would have following sample format:
        http://yourdomain/partner/encodedusername/{timestamp}/{native_algorithm_hash}?destination={internal path}
            
   For Profiles with Custom link would have the following sample format:
        http://yourdomain/partner/{encodedusername}/{timestamp}?destination={internalpath}&apikey={custom_algorithm_per_profile}&auth_div={Auth_Div_Inputted}

To Do:
1. Enabling "Email Access link to the selected user(s) available for Partner sites users only from the User management interface. At present, administrator is expected to ensure manually.
2. Support profile creation if external user management system is being used.
3. Prepare and Release functional test cases to enable automated testing.