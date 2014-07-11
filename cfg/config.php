<?php

/**
 * Konfiguracja
 */

// metoda losowa
$cfg_metodaLosowa = true;

// adresy url
$cfg_urlKonkurs                   = 'http://wypozycz.dzdn.pl/wol/zgadst.php';
$cfg_urlSkrocenieCzasuOczekiwania = 'http://wypozycz.dzdn.pl/wol/zgadspyt.php';
$cfg_urlSzukaniePoSygnaturze      = 'http://wypozycz.dzdn.pl/wol/katalog.php?co=s&i=';

// uzytkownik i haslo w serwisie
$cfg_uzytkownik = '';
$cfg_haslo      = '';

// minimalny i maksymalny czas "ludzkiego" opoznienia reakcji na gotowosc proby
$cfg_minReactionDelay = 3;
$cfg_maxReactionDelay = 20;

// timeout polaczenia
$cfg_timeout      = 0;

// domyslne opoznienie ponownej proby
$cfg_defaultDelay = 10;

// sciezka do pliku cookie
$cfg_cookieFile = dirname(dirname(__FILE__)) . '/tmp/cookie.txt';

// sciezka do katalogu logow
$cfg_logDir    = dirname(dirname(__FILE__)) . '/tmp/';

// kodowanie
$cfg_charset      = 'iso-8859-2';


/**
 * Ustawienia wstepne
 */

// kodowanie
mb_internal_encoding($cfg_charset);
