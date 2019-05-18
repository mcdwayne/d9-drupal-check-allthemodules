<?php

/**
 * @file
 * Predefined definitions.
 */

/**
 * Define the Moon Phases to be displayed on the phase page.
 */
$new_moon = '<p>' . t('What is this ghostly image? It is a new moon&#8230; a sort of moon that you cannot see from Earth&#8230; except during the stirring moments of a solar eclipse.') . '</p>';
$new_moon .= '<p>' . t('A new moon is between the Earth and sun. If it is directly between, a solar eclipse takes place. But that does not happen every month. Instead, once each month, the moon comes all the way around in its orbit so that it is more or less between us and the sun. This is a new moon: a moon whose lighted half is facing entirely away from Earth.') . '</p>';
$new_moon .= '<p>' . t('A new moon rises when the sun rises and sets when the sun sets. It crosses the sky with the sun during the day. Each new lunar cycle is measured beginning at each new moon. Astronomers call one lunar cycle a "lunation."') . '</p>';
$new_moon .= '<cite>' . t('<a href="@new_moon_url" target="_blank" class="small">Read more at EarthSky.org</a>', ['@new_moon_url' => 'http://www.earthsky.org/article/new-moon']) . '</cite>';

define('MOON_PHASE_NEW_MOON', $new_moon);

$waxing_cresent = '<p>' . t('A waxing crescent moon-sometimes called a "young moon"-is always seen in the west after sunset. At this moon phase, the Earth, moon and sun are located nearly on a line in space. If they were more precisely on a line, as they are at new moon, we would not see the moon. The moon would travel across the sky during the day, lost in the sun&amp;s glare. Instead, a waxing crescent moon is seen one day to several days after new moon. It rises one hour to several hours behind the sun and follows the sun across the sky during the day. When the sun sets, and the sky darkens, the moon pops into view in the western sky.') . '</p>';
$waxing_cresent .= '<p>' . t('Because the waxing crescent moon is nearly on a line with the Earth and sun, the "day side" is facing mostly away from us. We see only a slender fraction of the day side: a crescent moon. Each evening, because the moon is moving eastward in orbit around Earth, the moon appears farther from the sunset glare. Each evening, as the moon&amp;s orbital motion carries it away from the Earth/sun line, we see more of the moon&amp;s day side. Thus the crescent in the west after sunset appears to wax, or grow fatter each evening.') . '</p>';
$waxing_cresent .= '<p>' . t("Note that a crescent moon has nothing to do with Earth's shadow on the moon. The only time Earth's shadow can fall on the moon is at full moon, during a lunar eclipse. There IS a shadow on a crescent moon, but it is the moon's OWN shadow. Night on the moon happens on the part of the moon submerged in the moon's own shadow. Likewise, night on Earth happens on the part of Earth submerged in Earth's own shadow.") . '</p>';
$waxing_cresent .= '<p>' . t('You sometimes see a pale glow on the darkened portion (night side) of a crescent moon. This glow is due to light reflected from Earth&amp;s day side. It is called "earthshine."') . '</p>';
$waxing_cresent .= '<cite>' . t('<a href="@waxing_cresent_url" target="_blank" class="small">Read more at EarthSky.org</a>', ['@waxing_cresent_url' => 'http://www.earthsky.org/article/waxing-crescent']) . '</cite>';
define('MOON_PHASE_WAXING_CRESCENT', $waxing_cresent);

$first_quarter = '<p>' . t('A first quarter moon looks like half a pie. It rises at noon and is high overhead at sunset. It sets around midnight.') . '</p>';
$first_quarter .= '<p>' . t('First quarter moon comes a week after new moon. Now, as seen from above, the moon in its orbit around Earth is at right angles to a line between the Earth and sun.') . '</p>';
$first_quarter .= '<p>' . t('A first quarter moon is called "first quarter" because it is one quarter of the way around in its orbit of Earth, as measured from one new moon to the next. Also, although some people call this a "half moon," and although it really does appear half-lit to us, it is good to recall that the illuminated portion of a first quarter moon truly is just a quarter. On the night of first quarter moon, we see half the moon&amp;s day side, or a TRUE quarter of the moon. Another lighted quarter of the moon shines just as brightly in the direction opposite Earth!') . '</p>';
$first_quarter .= '<cite>' . t('<a href="@first_quarter_url" target="_blank" class="small">Read more at EarthSky.org</a>', ['@first_quarter_url' => 'http://www.earthsky.org/article/first-quarter']) . '</cite>';
define('MOON_PHASE_FIRST_QUARTER', $first_quarter);

$waxing_gibbous = '<p>' . t('A waxing gibbous moon appears high in the east at sunset. It is nearly, but not quite, a full moon... appearing more than half lighted but less than full. This moon phase comes between one and two weeks after new moon.') . '</p>';
$waxing_gibbous .= '<p>' . t('The moon has moved in its orbit so that it is now relatively far from the sun in our sky. A waxing gibbous moon rises during the hours between noon and sunset. It sets in the wee hours after midnight. People sometimes see a waxing gibbous moon in the afternoon, shortly after moonrise, while it is ascending in the east as the sun is descending in the west. It is easy to see a waxing gibbous moon in the daytime because, at this phase of the moon, a large fraction of the moon&amp;s day side is facing our way. Thus a waxing gibbous moon is more noticeable in the sky than a crescent moon, with only a slim fraction of the lunar day side visible. Also, a waxing gibbous moon is far from the sun on the sky&amp;s dome, so the sun&amp;s glare is not hiding it from view.') . '</p>';
$waxing_gibbous .= '<p>' . t('The word "gibbous" comes from a root word that means "hump-backed." You can see the hump-backed shape of the waxing gibbous moon.') . '</p>';
$waxing_gibbous .= '<cite>' . t('<a href="@waxing_gibbous_url" target="_blank" class="small">Read more at EarthSky.org</a>', ['@waxing_gibbous_url' => 'http://www.earthsky.org/article/waxing-gibbous']) . '</cite>';
define('MOON_PHASE_WAXING_GIBBOUS', $waxing_gibbous);

$full_moon = '<p>' . t("A full moon always rises in the east around the time the sun is setting in the west. At full moon, we are seeing all of the moon's day side. The moon and sun are on a line, with Earth in between. It is as though Earth is the fulcrum of a seesaw, and the moon and sun are sitting on either end of the seesaw. Thus as the sun sets in the west, the full moon rises. When the sun is below our feet at midnight, the full moon is highest in the sky. When the sun rises again at dawn, the full moon is setting.") . '</p>';
$full_moon .= '<p>' . t('In many ways, a full moon is the opposite of a new moon. At both the new and full phases, the moon is on a line with the Earth and sun. At new moon, the moon is in the middle position along the line. At full moon, Earth is in the middle.') . '</p>';
$full_moon .= '<p>' . t('Full moon always comes about two weeks after new moon, when the moon is midway around in its orbit of Earth, as measured from one new moon to the next.') . '</p>';
$full_moon .= '<p>' . t("If there is a lunar eclipse, it must happen at full moon. It is only a full moon that Earth's shadow, extending opposite the sun, can fall on the moon's face.") . '</p>';
$full_moon .= '<cite>' . t('<a href="" target="_blank" class="small">Read more at EarthSky.org\'</a>', ['@full_moon_url' => 'http://www.earthsky.org/article/full-moon']) . '</cite>';
define('MOON_PHASE_FULL_MOON', $full_moon);

$waning_gibbous = '<p>' . t('A waning gibbous moon sails over the eastern horizon in the hours between sunset and midnight. It is past full now... appearing less than full but more than half lighted.') . '</p>';
$waning_gibbous .= '<p>' . t('What can I say about a waning gibbous moon? Only that it can surprise you if you happen to be out late in the evening. It rises eerily some hours after sunset, glowing redly like a full moon when it is near the horizon. Sometimes it looks like a misshapen clone of a full moon.') . '</p>';
$waning_gibbous .= '<p>' . t('Because it comes up late at night, the waning gibbous moon prompts people to start asking, "Where is the moon? I looked for it last night and could not find it." It also initiates a rash of questions seeing the moon during the day. If it rises late at night, you know the waning gibbous moon must set after sunrise. In fact, in the few days after full moon, you will often see the waning gibbous moon in the west in early morning, floating against the pale blue sky.') . '</p>';
$waning_gibbous .= '<cite>' . t('<a href="@waning_gibbous_url" target="_blank" class="small">Read more at EarthSky.org</a>', ['@waning_gibbous_url' => 'http://www.earthsky.org/article/waning-gibbous']) . '</cite>';
define('MOON_PHASE_WANING_GIBBOUS', $waning_gibbous);

$third_quarter = '<p>' . t('Last quarter moon comes about three weeks after new moon. Now, as seen from above, the moon in its orbit around Earth is at right angles to a line between the Earth and sun. The moon is now three-quarters of the way around in its orbit of Earth, as measured from one new moon to the next.') . '</p>';
$third_quarter .= '<p>' . t("A last quarter moon looks half-illuminated. It rises around midnight, appears at its highest in the sky at dawn, and sets around noon. After this, the moon will begin edging noticeably closer to the sun again on the sky's dome. Fewer people notice the moon during the day from about last quarter on, because the sun's glare begins to dominate the moon.") . '</p>';
$third_quarter .= '<p>' . t("A last quarter moon can be used as a guidepost to Earth's direction of motion in orbit around the sun. In other words, when you look at a last quarter moon high in the predawn sky, you're gazing out approximately along the path of Earth's orbit, in a forward direction. The moon is moving in orbit around the sun with the Earth. But, if we could somehow anchor the moon in space... tie it down, keep it still... Earth's orbital speed of 18 miles per second would carry us across the space between us and the moon in only a few hours.") . '</p>';
$third_quarter .= '<cite>' . t('<a href="@third_quarter_url" target="_blank" class="small">Read more at EarthSky.org</a>', ['@third_quarter_url' => 'http://www.earthsky.org/article/last-quarter']) . '</cite>';
define('MOON_PHASE_THIRD_QUARTER', $third_quarter);

$waning_cresent = '<p>' . t('A waning crescent moon-sometimes called an "old moon"-is seen in the east before dawn. Now the moon has moved nearly entirely around in its orbit of Earth, as measured from one new moon to the next.') . '</p>';
$waning_cresent .= '<p>' . t('Because the moon is nearly on a line with the Earth and sun again, the "day side" of the moon is facing mostly away from us once more. We see only a slender fraction of the moon&amp;s day side: a crescent moon. Each morning before dawn, because the moon is moving eastward in orbit around Earth, the moon appears closer to the sunrise glare. We see less and less of the moon&amp;s day side, and thus the crescent in the east before dawn appears thinner each day.') . '</p>';
$waning_cresent .= '<p>' . t("The moon, as always, is rising in the east day after day. But most people won't see this moon phase unless they get up early. When the sun comes up, and the sky grows brighter, the waning crescent moon fades. Now the moon is so near the Earth/sun line that the sun's glare is drowning this slim moon from view. Still, the waning crescent is up there, nearly all day long, moving ahead of the sun across the sky's dome. It sets in the west several hours or less before sunset.") . '</p>';
$waning_cresent .= '<cite>' . t('<a href="@waning_crescent_url" target="_blank" class="small">Read more at EarthSky.org</a>', ['@waning_crescent_url' => 'http://www.earthsky.org/article/waning-crescent']) . '</cite>';
define('MOON_PHASE_WANING_CRESCENT', $waning_cresent);
