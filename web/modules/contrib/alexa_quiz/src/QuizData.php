<?php

namespace Drupal\alexa_quiz;

/**
 * Defines a Data class for Alexa quiz.
 */
class QuizData {

  /**
   * Quiz data.
   *
   * @var array
   */
  protected static $questions = [
    [
      'question' => "In what country is Barcelona located?",
      'answer' => [
        "Spain",
        "France",
        "Italy",
        "Mexico",
      ],
      'explanation' => "Barcelona is the second city in Spain, although it's a little known fact that there's another city with the same name in Venezuela",
    ],
    [
      'question' =>
      "What's the population of Barcelona?", 'answer' => [
        "1.6 million people",
        "3 million people",
        "500000 people",
        "2.5 million people",
      ],
      'explanation' => "The city's growth is limited by two rivers, the sea and the mountain, so it's not huge",
    ],
    [
      'question' =>
      "Which ancient civilization founded the city?", 'answer' => [
        "The Carthaginians",
        "The Greeks",
        "The Phoenicians",
        "The Romans",
      ],
      'explanation' => "Everyone thinks it was the Romans, when in fact the Roman name for Barcelona means New Barca, the Carthaginian general that founded the city",
    ],
    [
      'question' =>
      "Which sea laps the shores of city?", 'answer' => [
        "The Mediterranean sea",
        "The Aegean sea",
        "The Red sea",
        "The Caribbean sea",
      ],
      'explanation' => "Mediterranean means the center of Earth in Latin, that's how important this sea was to the Romans",
    ],
    [
      'question' =>
      "When was the city of Barcelona founded?", 'answer' => [
        "in the 3rd century BC",
        "in 1590",
        "in the 9th century BC",
        "It is unknown",
      ],
      'explanation' => "Indeed, the city is over 2000 years old",

    ],
    [
      'question' =>
      "How many soccer clubs from Barcelona play in Spain's top division La Liga ?", 'answer' => [
        "2 teams",
        "1 team",
        "3 teams",
        "No teams",
      ],
      'explanation' => "Valencia has as many teams. Both are beaten by Madrid, which in 2016 boasts 4 teams in top division",
    ],
    [
      'question' =>
      "How often does it snow in Barcelona, on average?", 'answer' => [
        "Once every 5 years",
        "Never",
        "Every winter",
        "Once every 20 years",
      ],
      'explanation' => "It's a rare event and not so much to witness, as the city grinds to a halt",
    ],
    [
      'question' =>
      "What kind of climate does Barcelona have?", 'answer' => [
        "Mediterranean",
        "Atlantic",
        "Continental",
        "Tropical",
      ],
      'explanation' => "With hot, sticky summers and mild but humid winters, spring and fall are the seasons you want to aim for",
    ],
    [
      'question' =>
      "What's the average annual temperature during the day?", 'answer' => [
        "21 degrees Celsius",
        "32 degrees Celsius",
        "15 degrees Celsius ",
        "27 degrees Celsius",
      ],
      'explanation' => "Check the weather forecast before you visit the city and pack accordingly. You may find it colder than expected if not visiting in summer",
    ],
    [
      'question' =>
      "What can you find in the Gothic quarter?", 'answer' => [
        "All options are true",
        "Fast food shops",
        "Medieval buildings",
        "Street performers",
      ],
      'explanation' => "The Gothic quarter is the true gem of the city as not so many places have over 2000 years of history and such a lively atmosphere",
    ],
    [
      'question' =>
      "What is Modernista architecture?", 'answer' => [
        "The same as Art Nouveau",
        "The distinct architecture of the two towers by the sea",
        "Only buildings designed by Gaudi can be called Modernista",
        "A commercial brand for souvenirs",
      ],
      'explanation' => "Gaudi is the most famous architect of this style, but you may also want to check out other buildings by Mila i Fontanals",
    ],
    [
      'question' =>
      "Which is Barcelona's main tourist attraction, according to number of visitors?", 'answer' => [
        "Sagrada Familia",
        "Soccer stadium",
        "Olympic village",
        "The cathedral",
      ],
      'explanation' => "There are always long queues there, so you better book your tickets online",
    ],
    [
      'question' =>
      "Barcelona has plenty of museums, but which one is the most visited?", 'answer' => [
        "Football Club Barcelona museum",
        "Picasso Museum",
        "Maritime Museum",
        "National Museum of Art",
      ],
      'explanation' => "The city lacks first class museums such as The Louvre in Paris or The British Museum in London, so this is hardly a surprise",
    ],
    [
      'question' =>
      "Which city in Spain has the most important art museum?", 'answer' => [
        "Madrid",
        "Barcelona",
        "Malaga",
        "Seville",
      ],
      'explanation' => "El Prado museum is on par with London's National Gallery. You shouldn't miss it while in Spain",
    ],
    [
      'question' =>
      "Which one is not a beach in Barcelona?", 'answer' => [
        "Copacabana",
        "Sant Sebastia",
        "Barceloneta",
        "Mar Bella",
      ],
      'explanation' => "Copacabana is in Brazil. All the others can be reached by Barcelona's public transport",
    ],
    [
      'question' =>
      "How many parks are in the city?", 'answer' => [
        "68",
        "34",
        "5",
        "12",
      ],
      'explanation' => "The biggest park is Ciutadella and it's small if compared with other European cities. In return, there are many smaller parks",
    ],
    [
      'question' =>
      "17.4 per cent of the city's inhabitants are not Spanish. Which is the most representative foreign nationality in Barcelona?", 'answer' => [
        "Pakistan",
        "Italy",
        "China",
        "Ecuador",
      ],
      'explanation' => "Our Pakistani neighbours are very entrepreneurial and they have opened many shops in the last decade",
    ],
    [
      'question' =>
      "What's the most popular religion in Barcelona?", 'answer' => [
        "Roman Catholic",
        "Jedi",
        "Atheism",
        "Islam",
      ],
      'explanation' => "Even though Barcelona is one of the least religious cities in Spain, and mass attendance is quite low, many locals still see themselves as Catholics",
    ],
    [
      'question' =>
      "How many international visitors did the city have in 2011?", 'answer' => [
        "5.5 millions",
        "2 millions",
        "7.5 millions",
        "1 million",
      ],
      'explanation' => "For each inhabitant, the city receives over three visitors in a year",
    ],
    [
      'question' =>
      "How many districts does the city have?", 'answer' => [
        "10",
        "20",
        "5",
        "15",
      ],
      'explanation' => "During your visit you will spend most of your time in the Ciutat Vella and Eixample districts, but there are eight more",
    ],
    [
      'question' =>
      "How many official languages are spoken in Barcelona?", 'answer' => [
        "2 languages",
        "1 language",
        "3 languages",
        "There's not such a thing as an official language",
      ],
      'explanation' => "Spanish and Catalan are both official languages and you will see most signs written in both languages, sometimes in English too",
    ],
    [
      'question' =>
      "How many times has Barcelona hosted the Olympic Games?", 'answer' => [
        "1 time",
        "None",
        "Twice",
        "3 times",
      ],
      'explanation' => "It was in 1992",
    ],
    [
      'question' =>
      "When did the Barcelona Olympic Games take place?", 'answer' => [
        "1992",
        "1996",
        "2000",
        "1988",
      ],
      'explanation' => "The Barcelona games came after Seoul in Korea and before Atlanta in the United States",
    ],
    [
      'question' =>
      "The Barcelona Marathon has become popular of late. How many participants ran last year?", 'answer' => [
        "10000 people",
        "5000 people",
        "60000 people",
        "The city is too hilly for a marathon",
      ],
      'explanation' => "It has proven to be one of the most popular events in the athletics calendar in Europe",
    ],
    [
      'question' =>
      "Which is the most famous street in Barcelona?", 'answer' => [
        "Ramblas",
        "Diagonal",
        "Balmes",
        "Aribau",
      ],
      'explanation' => "It's a beautiful promenade with live performers and flower shops. Enjoy your walk but mind your wallet there as pickpockets operate there",
    ],
    [
      'question' =>
      "What's the full name of Barcelona's Airport?", 'answer' => [
        "Barcelona El Prat",
        "Barcelona Leo Messi",
        "Barcelona",
        "Barcelona Costa Brava",
      ],
      'explanation' => "In fact, the airport is not in the city but in a village nearby called El Prat",
    ],
    [
      'question' =>
      "What distinctive color scheme do Barcelona taxis have?", 'answer' => [
        "Black and yellow",
        "White with a red stripe",
        "Black",
        "All taxis have been replaced by Uber vehicles, so the answer is any color",
      ],
      'explanation' => "You may not think they're pretty but they are super easy to spot",
    ],
    [
      'question' =>
      "Which fashion brand stems from Barcelona?", 'answer' => [
        "Desigual",
        "Zara",
        "Mango",
        "Uniqlo",
      ],
      'explanation' => "If you stroll by the sea side, you will see its headquarters near the double u Hotel",
    ],
    [
      'question' =>
      "Which river is not in the region of Barcelona?", 'answer' => [
        "Duero",
        "Llobregat",
        "Besos",
        "Ter",
      ],
      'explanation' => "The Duero is in North-West Spain",
    ],
    [
      'question' =>
      "There's an important Picasso museum in Barcelona, but where was he from?", 'answer' => [
        "Malaga",
        "Madrid",
        "Seville",
        "Cuernavaca",
      ],
      'explanation' => "He grew up in Barcelona because his father was an arts teacher there, but the family stems from Malaga. Later he moved to Paris in France, where he became world famous",
    ],
  ];

  /**
   * Public static function questions.
   */
  public static function questions() {
    return self::$questions;
  }

}
