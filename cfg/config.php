<?php

/**
 * Konfiguracja
 */

// kodowanie
$cfg_charset      = 'iso-8859-2';

// timeout polaczenia
$cfg_timeout      = 10;

// domyslne opoznienie ponownej proby w przypadku bleduu parsowania czasu
$cfg_defaultDelay = 30;

// minimalny i maksymalny czas "ludzkiego" opoznienia reakcji na gotowosc proby
$cfg_minReactionDelay = 60;
$cfg_maxReactionDelay = 180;

// sciezka do pliku cookie
$cfg_cookieFile = dirname(dirname(__FILE__)) . '/tmp/cookie.txt';

// sciezka do katalogu logow
$cfg_logDir    = dirname(dirname(__FILE__)) . '/tmp/';

// uzytkownik i haslo w serwisie
$cfg_uzytkownik = 'qwerty';
$cfg_haslo      = 'qwerty';

// adresy url
$cfg_urlKonkurs                   = 'http://wypozycz.dzdn.pl/wol/zgadst.php';
$cfg_urlSkrocenieCzasuOczekiwania = 'http://wypozycz.dzdn.pl/wol/zgadspyt.php';
$cfg_urlSzukaniePoSygnaturze      = 'http://wypozycz.dzdn.pl/wol/katalog.php?co=s&i=';


/**
 * Ustawienia wstepne
 */

// kodowanie
mb_internal_encoding($cfg_charset);
