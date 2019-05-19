Reputation Aegis is a Customer Intelligence Platform for Brands and Companies with Advanced Customer Satisfaction Surveys & Online Reputation Management Features. More info here: https://reputationaegis.com/

Reputation CRM is the back-end access, where businesses and web agencies can setup and manage all platform features. 
https://reputationcrm.com/

Reputation CRM has an API, with many different API CALLS, that allows Businesses and SaaS Platforms to:
automate the customer review invitation process,
integrate some of Reputation CRM features into a distant CRM or SaaS Platform dashboard.

Business Review Pages, Review Form, Check-in Form are hosted on branded domains URLs that each target a language, here are a few domain used:
English
We Love Reviews (USA, Canada): welove.reviews
Our Customers reviews (UK): ourcustomersreviews.com
French – Votre Avis Compte: votreaviscompte.com
Spanish – Su Opinión Importa: suopinionimporta.es
and 13 other languages and different URLs…


URL Resources:
REPUTATION AEGIS:   https://reputationaegis.com/
REPUTATION CRM: https://reputationcrm.com/
API DOCUMENTATION:  https://reputationcrm.com/developers/documentation/v2/overview/
API SDK:        https://reputationcrm.com/developers/documentation/v2/downloads
TESTING API CALLS:  https://reputationcrm.com/developers/documentation/v2/explorer/

Support:
We are available for support via email: support@reputationcrm.com or you can use the Chat on the Reputation Aegis website.


Before you start:
You have been asked by your client to integrate Rich Snippets in Search Results using the Reputation CRM API. It doesn’t take much development time because with just 1 API call, you will get back the information needed.

Immediate Benefit for your client after the page gets crawled again by Google
Marketing studies have proved that a website with stars in search results, leads to over 30% more clicks when compared against a website without stars and no reputation.

In search results, Google will display the Overall Rating of the Business and the number of reviews, on all inner-pages of the website (not on the main URL).


What this module does?
Module adds schema markup LocalBusiness code to the <head> of the website HTML on every page, and update 2 values when loading the page by calling the Reputation CRM API: ratingValue AND reviewCount.

Example:    https://activewills.com/
In Chrome:  view-source:https://activewills.com/

<script type="application/ld+json">
    {"@context":"http://schema.org",
    "@type":
    "LocalBusiness",
        "name":"Active Wills Limited",
        "url":"https://activewills.com/",
        "sameAs":["https://ourcustomersreviews.com/Active-Wills-Limited-TF9-3DD-Market-Drayton-47492280"],
        "image":"https://ourcustomersreviews.com/upload/companies/47492280/Active-Wills-Limited-logo-47492280.jpg",
        "telephone":"08009500200",
        "email":"support@activewills.com",
        "priceRange":"£",
        "address": {
        "@type":"PostalAddress",
        "addressCountry":"England",
        "addressLocality":"Market Drayton",
        "addressRegion":"Shropshire",
        "postalCode":"TF9 3DD",
        "streetAddress":"First Floor, Poynton House, 40 Shropshire Street"
    }
    ,"aggregateRating": {
    "@type": "AggregateRating",
        "worstRating": "1",
        "bestRating": "5",
        "ratingValue": "5",
        "reviewCount": "11"
    }
    }
</script>

API Key and Company ID: 2 values are required to make the call to the API

1. If your client has given you login details to Reputation CRM, you can click the 2 links below to get the info:
API Key: https://reputationcrm.com/settings/index/Reputation-Builder#api_server
Company ID: https://reputationcrm.com/companies-locations
Make sure that you get the Company ID and not a Location ID (if any).

2. If you do not have login details to Reputation CRM, send an email to support@reputationcrm.com and we will respond with the API Key and the Company ID.


Example GET API Call:
https://reputationcrm.com/v2/getOverallRatingAndReviewCountForPlatformReviewsByCompanyId?companyid=26744808&key=db616b7d3d63964c3ebbb49a1be143e2
Response from the API:
{"OverallRating":3.93,"ReviewCount":44}

Verify the code added to the client website pages:
You can verify that the LocalBusiness schema markup you added gets validated using:
https://search.google.com/structured-data/testing-tool


Deploy globally:
Add fields to your web-based application and store values to database. Here are the fields you will need to add:

BUSINESS CONTACT INFO:
Business Name           {BUSINESS_NAME}
Address             {BUSINESS_ADRESS}
City                {BUSINESS_TOWN}
State / Province            {BUSINESS_STATE}
Postal Code         {BUSINESS_ZIP}
Country             {BUSINESS_COUNTRY}

Phone #             {BUSINESS_PHONE}
Email Address           {BUSINESS_EMAIL}

Website URL         {BUSINESS_WEBSITE_URL}
Logo URL            {BUSINESS_LOGO_URL}

REPUTATIONCRM.COM INFO:
API Key             {API_KEY}
Company / Location ID       {COMPANY_ID}
Review Page URL     {REVIEW_PAGE_URL}


Become a Reputation Aegis Partner:
If you have developed a web-based application and would like to include Advanced Customer Satisfaction Surveys & Online Reputation Management Features as a White Label service, for all of your clients, please get in touch: https://reputationaegis.com/