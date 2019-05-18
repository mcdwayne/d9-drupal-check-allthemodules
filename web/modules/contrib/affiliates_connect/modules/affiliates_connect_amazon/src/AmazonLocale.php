<?php

namespace Drupal\affiliates_connect_amazon;

/**
 * Class AmazonCategories.
 */
class AmazonLocale {

  /**
   * Return the categories of particular locale.
   * @param  string $locale Location.
   * @return array
   *  array of categories.
   */
  public static function getCategories(string $locale) {

    /**
     * Store categories of different locations.
     * @var array
     */
    $categories = [];

    // Brazil Amazon Locale
    $categories['BR'] = [
      'All'                 => 'All Indexes',
      'Books'               => 'Books',
      'KindleStore'         => 'Kindle Store',
      'MobileApps'          => 'Mobile Apps',
    ];

    // Canada Amazon Locale:
    $categories['CA'] = [
      'All'                 => 'All Indexes',
      'Apparel'             => 'Apparel',
      'Automotive'          => 'Automotive',
      'Baby'                => 'Baby',
      'Beauty'              => 'Beauty',
      'Blended'             => 'Blended',
      'Books'               => 'Books',
      'DVD'                 => 'Movies & TV',
      'Electronics'         => 'Electronics',
      'GiftCards'           => 'Gift Cards',
      'Grocery'             => 'Grocery & Gourmet Food',
      'HealthPersonalCare'  => 'Health & Personal Care',
      'Industrial'          => 'Industrial & Scientific',
      'Jewelry'             => 'Jewelry',
      'KindleStore'         => 'Kindle Store',
      'Kitchen'             => 'Home & Kitchen',
      'LawnAndGarden'       => 'Patio, Lawn & Garden',
      'Luggage'             => 'Luggage & Bags',
      'Marketplace'         => 'Marketplace',
      'MobileApps'          => 'Apps & Games',
      'Music'               => 'Music',
      'MusicalInstruments'  => 'Musical Instruments, Stage & Studio',
      'OfficeProducts'      => 'Office Products',
      'PetSupplies'         => 'Pet Supplies',
      'Shoes'               => 'Shoes & Handbags',
      'Software'            => 'Software',
      'SportingGoods'       => 'Sports & Outdoors',
      'Tools'               => 'Tools & Home Improvement',
      'Toys'                => 'Toys & Games',
      'VideoGames'          => 'Video Games',
      'Watches'             => 'Watches',
    ];

    // China Amazon Locale:
    $categories['CN'] = [
      'All'                 => 'All Indexes',
      'Apparel'             => 'Apparel',
      'Appliances'          => 'Appliances',
      'Automotive'          => 'Automotive',
      'Baby'                => 'Baby',
      'Beauty'              => 'Beauty',
      'Books'               => 'Books',
      'Electronics'         => 'Electronics',
      'GiftCards'           => 'Gift Cards',
      'Grocery'             => 'Grocery',
      'HealthPersonalCare'  => 'Health & Personal Care',
      'Home'                => 'Home',
      'Home Improvement'    => 'Home Improvement',
      'Jewelry'             => 'Jewelry',
      'KindleStore'         => 'Kindle Store',
      'Kitchen'             => 'Kitchen',
      'MobileApps'          => 'MobileApps',
      'Music'               => 'Music',
      'MusicalInstruments'  => 'Musical Instruments',
      'OfficeProducts'      => 'Office',
      'PCHardware'          => 'PCHardware',
      'PetSupplies'         => 'PetSupplies',
      'Photo'               => 'Photo',
      'Shoes'               => 'Shoes',
      'Software'            => 'Software',
      'SportingGoods'       => 'Sporting Goods',
      'Toys'                => 'Toys',
      'Video'               => 'Video',
      'VideoGames'          => 'Video Games',
      'Watches'             => 'Watches',
    ];

    // Germany Amazon Locale:
    $categories['DE'] = [
      'All'                 => 'All Indexes',
      'Apparel'             => 'Apparel',
      'Appliances'          => 'Appliances',
      'Automotive'          => 'Automotive',
      'Baby'                => 'Baby',
      'Beauty'              => 'Beauty',
      'Books'               => 'Books',
      'Classical'           => 'Classical',
      'DVD'                 => 'DVD',
      'Electronics'         => 'Electronics',
      'ForeignBooks'        => 'Foreign Books',
      'GiftCards'           => 'GiftCards',
      'Grocery'             => 'Grocery',
      'Handmade'            => 'Handmade',
      'HealthPersonalCare'  => 'Health & Personal Care',
      'HomeGarden'          => 'Home & Garden',
      'Industrial'          => 'Industrial',
      'Jewelry'             => 'Jewelry',
      'KindleStore'         => 'Kindle Store',
      'Kitchen'             => 'Kichen',
      'Lighting'            => 'Lighting',
      'Luggage'             => 'Luggage',
      'Magazines'           => 'Magazines',
      'Marketplace'         => 'Marketplace',
      'MobileApps'          => 'MobileApps',
      'MP3Downloads'        => 'MP3 Downloads',
      'Music'               => 'Music',
      'MusicalInstruments'  => 'Musical Instruments',
      'MusicTracks'         => 'Music Tracks',
      'OfficeProducts'      => 'Office',
      'Pantry'              => 'Pantry',
      'PCHardware'          => 'PC Hardware',
      'PetSupplies'         => 'PetSupplies',
      'Photo'               => 'Photo',
      'Shoes'               => 'Shoes',
      'Software'            => 'Software',
      'SportingGoods'       => 'Sporting Goods',
      'Tools'               => 'Tools',
      'Toys'                => 'Toys',
      'VideoGames'          => 'Video Games',
      'Watches'             => 'Watches',
    ];

    // Spain Amazon Locale:
    $categories['ES'] = [
      'All'                 => 'All Indexes',
      'Apparel'             => 'Apparel',
      'Automotive'          => 'Automotive',
      'Baby'                => 'Baby',
      'Beauty'              => 'Beauty',
      'Books'               => 'Books',
      'DVD'                 => 'DVD',
      'Electronics'         => 'Electronics',
      'ForeignBooks'        => 'Foreign Books',
      'GiftCards'           => 'GiftCards',
      'Grocery'             => 'Grocery',
      'Handmade'            => 'Handmade',
      'HealthPersonalCare'  => 'Health & Personal Care',
      'Industrial'          => 'Industrial',
      'Jewelry'             => 'Jewelry',
      'KindleStore'         => 'Kindle Store',
      'Kitchen'             => 'Kitchen',
      'LawnAndGarden'       => 'LawnAndGarden',
      'Lighting'            => 'Lighting',
      'Luggage'             => 'Luggage',
      'MobileApps'          => 'MobileApps',
      'MP3Downloads'        => 'MP3 Downloads',
      'Music'               => 'Music',
      'MusicalInstruments'  => 'Musical Instruments',
      'OfficeProducts'      => 'Office',
      'Pantry'              => 'Pantry',
      'PCHardware'          => 'PC Hardware',
      'PetSupplies'         => 'PetSupplies',
      'Shoes'               => 'Shoes',
      'Software'            => 'Software',
      'SportingGoods'       => 'Sporting Goods',
      'Tools'               => 'Tools',
      'Toys'                => 'Toys',
      'VideoGames'          => 'Video Games',
      'Watches'             => 'Watches',
    ];

    // France Amazon Locale:
    $categories['FR'] = [
      'All'                 => 'All Indexes',
      'Apparel'             => 'Apparel',
      'Appliances'          => 'Appliances',
      'Baby'                => 'Baby',
      'Beauty'              => 'Beauty',
      'Books'               => 'Books',
      'Classical'           => 'Classical',
      'DVD'                 => 'DVD',
      'Electronics'         => 'Electronics',
      'ForeignBooks'        => 'Foreign Books',
      'GiftCards'           => 'GiftCards',
      'Grocery'             => 'Grocery',
      'Handmade'            => 'Handmade',
      'HealthPersonalCare'  => 'Health & Personal Care',
      'Home Improvement'    => 'Home Improvement',
      'Industrial'          => 'Industrial',
      'Jewelry'             => 'Jewelry',
      'KindleStore'         => 'Kindle Store',
      'Kitchen'             => 'Kitchen',
      'LawnAndGarden'       => 'LawnAndGarden',
      'Lighting'            => 'Lighting',
      'Luggage'             => 'Luggage',
      'Marketplace'         => 'Marketplace',
      'MobileApps'          => 'MobileApps',
      'MP3Downloads'        => 'MP3 Downloads',
      'Music'               => 'Music',
      'MusicalInstruments'  => 'Musical Instruments',
      'OfficeProducts'      => 'Office',
      'PCHardware'          => 'PC Hardware',
      'Shoes'               => 'Shoes',
      'Software'            => 'Software',
      'SportingGoods'       => 'Sporting Goods',
      'Tools'               => 'Tools',
      'Toys'                => 'Toys',
      'VideoGames'          => 'Video Games',
      'Watches'             => 'Watches',
    ];

    // India Amazon Locale:
    $categories['IN'] = [
      'All'                 => 'All Indexes',
      'Apparel'             => 'Apparel',
      'Appliances'          => 'Appliances',
      'Automotive'          => 'Automotive',
      'Baby'                => 'Baby',
      'Beauty'              => 'Beauty',
      'Books'               => 'Books',
      'DVD'                 => 'Movies & TV Shows',
      'Electronics'         => 'Electronics',
      'Furniture'           => 'Furniture',
      'GiftCards'           => 'GiftCards',
      'Grocery'             => 'Grocery',
      'Handmade'            => 'Handmade',
      'HealthPersonalCare'  => 'Health & Personal Care',
      'HomeGarden'          => 'Home & Garden',
      'Industrial'          => 'Industrial',
      'Jewelry'             => 'Jewelry',
      'KindleStore'         => 'Kindle Store',
      'LawnAndGarden'       => 'LawnAndGarden',
      'Luggage'             => 'Luggage',
      'LuxuryBeauty'        => 'Luxury Beauty',
      'Marketplace'         => 'Marketplace',
      'Music'               => 'Music',
      'MusicalInstruments'  => 'Musical Instruments',
      'OfficeProducts'      => 'Office',
      'Pantry'              => 'Pantry',
      'PCHardware'          => 'PC Hardware',
      'PetSupplies'         => 'PetSupplies',
      'Shoes'               => 'Shoes',
      'Software'            => 'Software',
      'SportingGoods'       => 'Sporting Goods',
      'Toys'                => 'Toys',
      'VideoGames'          => 'Video Games',
      'Watches'             => 'Watches',
    ];

    // Italy Amazon Locale:
    $categories['IT'] = [
      'All'                 => 'All Indexes',
      'Apparel'             => 'Apparel',
      'Automotive'          => 'Automotive',
      'Baby'                => 'Baby',
      'Beauty'              => 'Beauty',
      'Books'               => 'Books',
      'DVD'                 => 'Movies & TV Shows',
      'Electronics'         => 'Electronics',
      'ForeignBooks'        => 'Foreign Books',
      'Garden'              => 'Garden',
      'GiftCards'           => 'GiftCards',
      'Grocery'             => 'Grocery',
      'Handmade'            => 'Handmade',
      'HealthPersonalCare'  => 'Health & Personal Care',
      'Industrial'          => 'Industrial',
      'Jewelry'             => 'Jewelry',
      'KindleStore'         => 'Kindle Store',
      'Kitchen'             => 'Kitchen',
      'Lighting'            => 'Lighting',
      'Luggage'             => 'Luggage',
      'MobileApps'          => 'MobileApps',
      'MP3Downloads'        => 'MP3 Downloads',
      'Music'               => 'Music',
      'MusicalInstruments'  => 'Musical Instruments',
      'OfficeProducts'      => 'Office',
      'PCHardware'          => 'PC Hardware',
      'Shoes'               => 'Shoes',
      'Software'            => 'Software',
      'SportingGoods'       => 'Sporting Goods',
      'Tools'               => 'Tools',
      'Toys'                => 'Toys',
      'VideoGames'          => 'Video Games',
      'Watches'             => 'Watches',
    ];

    // Japan Amazon Locale:
    $categories['JP'] = [
      'All'                 => 'All Indexes',
      'Apparel'             => 'Apparel',
      'Appliances'          => 'Appliances',
      'Automotive'          => 'Automotive',
      'Baby'                => 'Baby',
      'Beauty'              => 'Beauty',
      'Books'               => 'Books',
      'Classical'           => 'Classical',
      'CreditCards'         => 'CreditCards',
      'DVD'                 => 'Movies & TV Shows',
      'Electronics'         => 'Electronics',
      'ForeignBooks'        => 'Foreign Books',
      'GiftCards'           => 'GiftCards',
      'Grocery'             => 'Grocery',
      'Handmade'            => 'Handmade',
      'HealthPersonalCare'  => 'Health & Personal Care',
      'Hobbies'             => 'Hobbies',
      'HomeImprovement'     => 'HomeImprovement',
      'Industrial'          => 'Industrial',
      'Jewelry'             => 'Jewelry',
      'KindleStore'         => 'Kindle Store',
      'Kitchen'             => 'Kitchen',
      'Marketplace'         => 'Marketplace',
      'MobileApps'          => 'MobileApps',
      'MP3Downloads'        => 'MP3 Downloads',
      'Music'               => 'Music',
      'MusicalInstruments'  => 'Musical Instruments',
      'OfficeProducts'      => 'Office',
      'PCHardware'          => 'PC Hardware',
      'PetSupplies'         => 'Pet Supplies',
      'Shoes'               => 'Shoes',
      'Software'            => 'Software',
      'SportingGoods'       => 'Sporting Goods',
      'Toys'                => 'Toys',
      'Video'               => 'Video',
      'VideoDownload'       => 'VideoDownload',
      'VideoGames'          => 'Video Games',
      'Watches'             => 'Watches',
    ];

    // Mexico Amazon Locale:
    $categories['MX'] = [
      'All'                 => 'All Indexes',
      'Baby'                => 'Baby',
      'Books'               => 'Books',
      'DVD'                 => 'Movies & TV Shows',
      'Electronics'         => 'Electronics',
      'HealthPersonalCare'  => 'Health & Personal Care',
      'HomeImprovement'     => 'HomeImprovement',
      'KindleStore'         => 'Kindle Store',
      'Kitchen'             => 'Kitchen',
      'Music'               => 'Music',
      'OfficeProducts'      => 'Office',
      'Software'            => 'Software',
      'SportingGoods'       => 'Sporting Goods',
      'VideoGames'          => 'Video Games',
      'Watches'             => 'Watches',
    ];

    // United Kingdom Amazon Locale:
    $categories['UK'] = [
      'All'                 => 'All Indexes',
      'Apparel'             => 'Apparel',
      'Appliances'          => 'Appliances',
      'Automotive'          => 'Automotive',
      'Baby'                => 'Baby',
      'Beauty'              => 'Beauty',
      'Books'               => 'Books',
      'Classical'           => 'Classical',
      'DVD'                 => 'DVD',
      'Electronics'         => 'Electronics',
      'GiftCards'           => 'GiftCards',
      'Grocery'             => 'Grocery',
      'Handmade'            => 'Handmade',
      'HealthPersonalCare'  => 'Health & Personal Care',
      'HomeGarden'          => 'Home & Garden',
      'Jewelry'             => 'Jewelry',
      'KindleStore'         => 'Kindle Store',
      'Kitchen'             => 'Kitchen',
      'Lighting'            => 'Lighting',
      'Luggage'             => 'Luggage',
      'Marketplace'         => 'Marketplace',
      'MobileApps'          => 'MobileApps',
      'MP3Downloads'        => 'MP3 Downloads',
      'Music'               => 'Music',
      'MusicalInstruments'  => 'Musical Instruments',
      'OfficeProducts'      => 'Office',
      'Pantry'              => 'Pantry',
      'PCHardware'          => 'PC Hardware',
      'PetSupplies'         => 'Pet Supplies',
      'Shoes'               => 'Shoes',
      'Software'            => 'Software',
      'SportingGoods'       => 'Sporting Goods',
      'Tools'               => 'Tools',
      'Toys'                => 'Toys',
      'UnboxVideo'          => 'UnboxVideo',
      'VHS'                 => 'VHS',
      'VideoGames'          => 'Video Games',
      'Watches'             => 'Watches',
    ];


    // United States Amazon Locale:
    $categories['US'] = [
      'All'                 => 'All Indexes',
      'Appliances'          => 'Appliances',
      'ArtsAndCrafts'       => 'ArtsAndCrafts',
      'Automotive'          => 'Automotive',
      'Baby'                => 'Baby',
      'Beauty'              => 'Beauty',
      'Books'               => 'Books',
      'Collectibles'        => 'Collectibles',
      'Electronics'         => 'Electronics',
      'Fashion'             => 'Clothing, Shoes & Jewelry',
      'FashionBaby'         => 'Clothing, Shoes & Jewelry - Baby',
      'FashionBoys'         => 'Clothing, Shoes & Jewelry - Boys',
      'FashionGirls'         => 'Clothing, Shoes & Jewelry - Girls',
      'FashionMen'         => 'Clothing, Shoes & Jewelry - Men',
      'FashionWomen'         => 'Clothing, Shoes & Jewelry - Women',
      'GiftCards'           => 'GiftCards',
      'Grocery'             => 'Grocery',
      'Handmade'            => 'Handmade',
      'HealthPersonalCare'  => 'Health & Personal Care',
      'HomeGarden'          => 'Home & Garden',
      'Industrial'          => 'Industrial',
      'KindleStore'         => 'Kindle Store',
      'LawnAndGarden'       => 'LawnAndGarden',
      'Luggage'             => 'Luggage',
      'Magazines'           => 'Magazines',
      'Marketplace'         => 'Marketplace',
      'Merchants'           => 'Merchants',
      'MobileApps'          => 'MobileApps',
      'Movies'              => 'Movies',
      'MP3Downloads'        => 'MP3 Downloads',
      'Music'               => 'Music',
      'MusicalInstruments'  => 'Musical Instruments',
      'OfficeProducts'      => 'Office',
      'Pantry'              => 'Pantry',
      'PCHardware'          => 'PC Hardware',
      'PetSupplies'         => 'Pet Supplies',
      'Software'            => 'Software',
      'SportingGoods'       => 'Sporting Goods',
      'Tools'               => 'Tools',
      'Toys'                => 'Toys',
      'UnboxVideo'          => 'UnboxVideo',
      'Vehicles'            => 'Vehicles',
      'VideoGames'          => 'Video Games',
      'Wine'                => 'Wine',
      'Wireless'            => 'Cell Phones & Accessories',
    ];

    return $categories[$locale];
  }

  /**
   * Return the locale array
   * @return array
   */
  public static function getLocale()
  {
    $locale = [
      'BR' => 'Brazil',
      'CA' => 'Canada',
      'CN' => 'China',
      'ES' => 'Spain',
      'FR' => 'France',
      'DE' => 'Germany',
      'IN' => 'India',
      'IT' => 'Italy',
      'JP' => 'Japan',
      'MX' => 'Mexico',
      'UK' => 'United Kingdom',
      'US' => 'United States',
    ];
    return $locale;
  }

  /**
   * Return the url extension for particular locale.
   * @param  string $locale location
   * @return string
   */
  public static function getURL(string $locale)
  {
    $url = [
      'BR' => '.com.br',
      'CA' => '.ca',
      'CN' => '.cn',
      'ES' => '.es',
      'FR' => '.fr',
      'DE' => '.de',
      'IN' => '.in',
      'IT' => '.it',
      'JP' => '.co.jp',
      'MX' => '.com.mx',
      'UK' => '.co.uk',
      'US' => '.com',
    ];

    return $url[$locale];
  }

}
