<?php

define('KOMUNIKAT_TYP_BLAD', 'BLAD');
define('KOMUNIKAT_TYP_INFO', 'INFO');
define('KOMUNIKAT_TYP_DEBUG', 'DEBUG');

/**
 * Zapis logow do pliku
 * @param string $typ
 * @param string $wiadomosc
 */
function zapiszDoPliku($typ, $wiadomosc)
{
    global $cfg_logDir;

    $data      = date('r');
    $wiadomosc = '### ' . $data . ' [' . $typ . '] ' . $wiadomosc;

    $nazwaPlikuLogow = date('Ymd') . '.log';

    file_put_contents($cfg_logDir . $nazwaPlikuLogow, $wiadomosc, FILE_APPEND);
}

/**
 * Czy tryb CGI
 * @return boolean
 */
function czyTrybCgi()
{
    return preg_match('/cgi|cli/i', PHP_SAPI) === 1;
}

/**
 * Wypisanie komunikatu na ekranie
 * @param string $wiadomosc
 * @param boolean $data
 */
function piszNaEkran($wiadomosc, $data = false)
{
    if (czyTrybCgi() === false) {
        $wiadomosc = nl2br($wiadomosc);
    }
    
    if ($data === true) {
        $wiadomosc = date('Y-m-d H:i:s') . ' ' . $wiadomosc;
    }

    echo $wiadomosc;
}

/**
 * Logowanie komunikatow
 * @param integer $typ
 * @param string $wiadomosc
 * @param boolean $nowaLinia
 * @param boolean $data
 */
function komunikat($typ, $wiadomosc, $nowaLinia = true, $data = true)
{
    global $cfg_dataWKomunikatach;
    
    $znakiNowejLinii = array('\r\n', '\n\r', '\r', '\n');

    if ($nowaLinia === true) {
        $wiadomosc .= '\n';
    }
    $wiadomosc = str_replace($znakiNowejLinii, PHP_EOL, $wiadomosc);

    zapiszDoPliku($typ, $wiadomosc . ($nowaLinia === false ? PHP_EOL : ''));

    switch ($typ) {
        case KOMUNIKAT_TYP_INFO:
            piszNaEkran($wiadomosc, ($cfg_dataWKomunikatach && $data));
            break;
        case KOMUNIKAT_TYP_BLAD:
            piszNaEkran('[' . $typ . '] ' . $wiadomosc, ($cfg_dataWKomunikatach && $data));
            break;
        case KOMUNIKAT_TYP_DEBUG:
            break;
    }
}

/**
 * Obsluga bledow
 * @param resource $ch
 */
function obslugaBledu($ch)
{
    if (curl_errno($ch)) {
        komunikat(KOMUNIKAT_TYP_BLAD, 'Curl error: ' . curl_error($ch));
    }
}

/**
 * Odczyt tresci strony
 * @param string $adresUrl
 * @return string
 */
function czytajStrone($adresUrl)
{
    global $cfg_timeout, $cfg_cookieFile;

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $adresUrl);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $cfg_timeout);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cfg_cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cfg_cookieFile);

    $tresc = curl_exec($ch);

    obslugaBledu($ch);

    curl_close($ch);

    komunikat(KOMUNIKAT_TYP_DEBUG, $tresc . '\n');

    return $tresc;
}

/**
 * Pobierz tresc strony konkursu
 * @return string
 */
function stronaKonkursu()
{
    global $cfg_urlKonkurs;

    return czytajStrone($cfg_urlKonkurs);
}

/**
 * Czy wyrazenie jest w tresci
 * @param string $tresc
 * @param string $wyrazenie
 * @return boolean
 */
function jestWTresci($tresc, $wyrazenie)
{
    $wynik = mb_strpos($tresc, $wyrazenie);

    return ($wynik !== false);
}

/**
 * Logowanie do serwisu
 * @return boolean
 */
function zalogujDoSerwisu()
{
    global $cfg_timeout, $cfg_urlKonkurs, $cfg_uzytkownik, $cfg_haslo, $cfg_cookieFile;

    $fields = array(
        'usn'  => $cfg_uzytkownik,
        'usp'  => $cfg_haslo,
        'uspm' => 'on'
    );

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $cfg_urlKonkurs);
    curl_setopt($ch, CURLOPT_POST, count($fields));
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $cfg_timeout);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cfg_cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cfg_cookieFile);

    $tresc    = curl_exec($ch);
    $rezultat = $tresc !== false;

    obslugaBledu($ch);

    curl_close($ch);

    komunikat(KOMUNIKAT_TYP_DEBUG, $tresc . '\n');
    komunikat(KOMUNIKAT_TYP_INFO, 'Logowanie do serwisu: ' . ($rezultat ? 'SUKCES' : 'B£¡D'));

    return $rezultat;
}

/**
 * Parsowanie czasu
 * @param string $czas
 * @return integer
 */
function czasWSekundach($czas = null)
{
    global $cfg_defaultDelay;

    if (!is_null($czas)) {
        $pattern = '/((\d{1,2}) godz. )?((\d{1,2}) min. )?(\d{1,2}) sek./is';
        if (preg_match($pattern, $czas, $matches) === 1) {
            $godzin = intval($matches[2]);
            $minut  = intval($matches[4]);
            $sekund = intval($matches[5]);

            return $godzin * 3600 + $minut * 60 + $sekund;
        } else {
            komunikat(KOMUNIKAT_TYP_BLAD, 'Blad parsowania czasu');

            return $cfg_defaultDelay;
        }
    }

    return 0;
}

/**
 * Zwraca czas oczekiwania na kolejna probe
 * @param string $czas
 * @return integer
 */
function czekaj($czas = null)
{
    global $cfg_minReactionDelay, $cfg_maxReactionDelay;

    return czasWSekundach($czas) + rand($cfg_minReactionDelay, $cfg_maxReactionDelay);
}

/**
 * Parsuje czas oczekiwania na losowanie
 * @param string $tresc
 * @return string|null
 */
function czasOczekiwaniaNaNowaSesje($tresc)
{
    $pattern = '/Do rozpoczêcia dzisiejszej edycji pozosta³o jeszcze (.*?)<br>/is';
    if (preg_match($pattern, $tresc, $matches) === 1) {
        return $matches[1];
    }

    komunikat(KOMUNIKAT_TYP_BLAD, 'Brak danych czasu oczekiwania na nowa sesje');
    return null;
}

/**
 * Parsuje czas oczekiwania na losowanie
 * @param string $tresc
 * @return string|null
 */
function czasOczekiwaniaNaLosowanie($tresc)
{
    $pattern = '/Musisz zaczekaæ jeszcze (.*?) do nastêpnego typowania/is';
    if (preg_match($pattern, $tresc, $matches) === 1) {
        return $matches[1];
    }

    komunikat(KOMUNIKAT_TYP_BLAD, 'Brak danych czasu oczekiwania na losowanie');
    return null;
}

/**
 * Pobierz tresc strony skracania
 * @return string
 */
function stronaSkracania()
{
    global $cfg_urlSkrocenieCzasuOczekiwania;

    return czytajStrone($cfg_urlSkrocenieCzasuOczekiwania);
}

/**
 * Parsuje sygnature
 * @param string $tresc
 * @return string|null
 */
function sygnatura($tresc)
{
    $pattern = '/<label for="odp">Wska¿ autora ksi±¿ki: .*? - Sygnatura: (.*?)<\/label>/is';
    if (preg_match($pattern, $tresc, $matches) === 1) {
        $sygnatura = $matches[1];
        komunikat(KOMUNIKAT_TYP_DEBUG, 'Sygantura: ' . $sygnatura);
        return $sygnatura;
    }

    komunikat(KOMUNIKAT_TYP_BLAD, 'Brak sygnatury');
    return null;
}

/**
 * Pobierz strone ksiazki
 * @param string $sygnatura
 * @return string
 */
function stronaKsiazki($sygnatura)
{
    global $cfg_urlSzukaniePoSygnaturze;

    return czytajStrone($cfg_urlSzukaniePoSygnaturze . $sygnatura);
}

/**
 * Parsuje id autora
 * @param string $tresc
 * @return integer|null
 */
function idAutora($tresc)
{
    $pattern = '/Autor: <a href="katalog.php\?co=a&i=(\d*?)">/is';
    if (preg_match($pattern, $tresc, $matches) === 1) {
        $idAutora = intval($matches[1]);
        komunikat(KOMUNIKAT_TYP_DEBUG, 'ID Autora: ' . $idAutora);
        return $idAutora;
    }

    komunikat(KOMUNIKAT_TYP_BLAD, 'Brak danych autora');
    return null;
}

/**
 * Zgadniecie autora
 * @param integer $idAutora
 * @return boolean
 */
function zgadnijAutora($idAutora)
{
    global $cfg_timeout, $cfg_urlSkrocenieCzasuOczekiwania, $cfg_cookieFile;

    $fields = array(
        'odp' => $idAutora
    );

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $cfg_urlSkrocenieCzasuOczekiwania);
    curl_setopt($ch, CURLOPT_POST, count($fields));
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $cfg_timeout);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cfg_cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cfg_cookieFile);

    $tresc    = curl_exec($ch);
    $rezultat = $tresc !== false;

    obslugaBledu($ch);

    curl_close($ch);

    komunikat(KOMUNIKAT_TYP_DEBUG, $tresc . '\n');

    return $rezultat;
}

/**
 * Skrocenie czasu oczekiwania
 */
function skrocCzasOczekiwaniaNaLosowanie()
{
    komunikat(KOMUNIKAT_TYP_INFO, 'Proba skrocenia czasu oczekiwania');

    $trescStronySkracania = stronaSkracania();
    $sygnatura            = sygnatura($trescStronySkracania);
    $trescStronyKsiazki   = stronaKsiazki($sygnatura);
    $idAutora             = idAutora($trescStronyKsiazki);
    zgadnijAutora($idAutora);
}

/**
 * Zwraca zwyciezce edycji
 * @param string $tresc
 * @return string
 */
function ktoWygral($tresc)
{
    global $cfg_uzytkownik;

    $pattern = '/Zwyciêzc± dzisiejszej edycji konkursu jest u¿ytkownik &quot;(.*?)&quot;./is';
    if (preg_match($pattern, $tresc, $matches) === 1) {
        $zwyciezca = $matches[1];
        komunikat(KOMUNIKAT_TYP_DEBUG, 'Zwyciezca: ' . $zwyciezca);
        if ($zwyciezca === $cfg_uzytkownik) {
            return 'Zwyciezca zostales TY! Gratulacje !!!';
        } else {
            return 'Zwyciezca zostal "' . $zwyciezca . '"';
        }
    }

    komunikat(KOMUNIKAT_TYP_BLAD, 'Brak danych zwyciezcy');
    return null;
}

/**
 * Wylicza szanse trafienia
 * @param integer $zakresOd
 * @param integer $zakresDo
 * @return integer
 */
function szansa($zakresOd, $zakresDo)
{
    return $zakresDo - $zakresOd - 1;
}

/**
 * Zwraca typowany wynik
 * @param integer $zakresOd
 * @param integer $zakresDo
 * @param boolean $komunikat
 * @return integer
 */
function typowanyWynik($zakresOd, $zakresDo, $komunikat = true)
{
    global $cfg_metodaLosowa;

    if ($cfg_metodaLosowa === true) {
        $wynik = mt_rand($zakresOd + 1, $zakresDo - 1);
    } else {
        $wynik = intval(ceil($zakresDo - $zakresOd) / 2) + $zakresOd;
    }

    if ($komunikat) {
        komunikat(KOMUNIKAT_TYP_INFO, 'Typowany wynik: ' . $wynik, false);
    }
    return $wynik;
}

/**
 * Formatuje czas
 * @param integer $sekund
 * @return string
 */
function formatujCzas($sekund)
{
    $format = 's \s\e\k.';
    if ($sekund > 59) {
        $format = 'i \m\i\n. ' . $format;
    }
    if ($sekund > 3599) {
        $format = 'G \g\o\d\z. ' . $format;
    }
    return gmdate($format, $sekund);
}

/**
 * Szacowanie czasu do zakonczenia
 * @param integer $zakresOd
 * @param integer $zakresDo
 * @return integer
 */
function szacowanyCzasDoZakonczenia($zakresOd, $zakresDo)
{
    global $cfg_maxReactionDelay;

    $czas = 0;
    $etap = 15 * 60 + $cfg_maxReactionDelay;
    do {
        $szansa   = szansa($zakresOd, $zakresDo);
        $zakresOd = typowanyWynik($zakresOd, $zakresDo, false);
        $czas += $etap;
    } while ($szansa > 1);

    $czas -= $etap;

    return $czas;
}

/**
 * Parsuje zakres losowania
 * @param string $tresc
 * @return array|null
 */
function zakresLosowania($tresc)
{
    global $cfg_metodaLosowa;

    $pattern = '/Dzisiejsza liczba wylosowana dla ciebie jest wiêksza od (\d{1,7}) i mniejsza od (\d{1,7})/is';
    if (preg_match($pattern, $tresc, $matches) === 1) {
        $zakresOd = intval($matches[1]);
        $zakresDo = intval($matches[2]);

        $szansa = szansa($zakresOd, $zakresDo);

        komunikat(KOMUNIKAT_TYP_INFO, 'Zakres od: ' . $zakresOd . ' do ' . $zakresDo . '. Szansa trafienia: 1 do ' . $szansa);
        if ($cfg_metodaLosowa !== true) {
            komunikat(KOMUNIKAT_TYP_INFO, 'Szacowany czas do zakonczenia: ' . formatujCzas(szacowanyCzasDoZakonczenia($zakresOd, $zakresDo)));
        }

        return array($zakresOd, $zakresDo);
    }

    komunikat(KOMUNIKAT_TYP_BLAD, 'Brak zakresu losowania');
    return null;
}

/**
 * Zgadywanie wyniku
 * @param integer $wynik
 * @return boolean
 */
function strzelaj($wynik)
{
    global $cfg_timeout, $cfg_urlKonkurs, $cfg_cookieFile;

    $fields = array(
        'typ' => $wynik
    );

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $cfg_urlKonkurs);
    curl_setopt($ch, CURLOPT_POST, count($fields));
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $cfg_timeout);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cfg_cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cfg_cookieFile);

    $tresc = curl_exec($ch);

    obslugaBledu($ch);

    curl_close($ch);

    komunikat(KOMUNIKAT_TYP_DEBUG, $tresc . '\n');

    return $tresc;
}

/**
 * Czy trafiono wynik
 * @param string $tresc
 * @return boolean
 */
function czyTrafienie($tresc)
{
    $pattern   = '/Gratulujemy wygranej w dzisiejszej edycji konkursu!/is';
    $trafienie = (preg_match($pattern, $tresc, $matches) === 1);
    if ($trafienie === false) {
        komunikat(KOMUNIKAT_TYP_INFO, ' ... pudlo :(', true, false);
    } else {
        komunikat(KOMUNIKAT_TYP_INFO, ' ... Wygrales :) !!!', true, false);
    }

    return $trafienie;
}

/**
 * Typuj wynik
 * @param string $tresc
 * @return boolean
 */
function typujWynik($tresc)
{
    komunikat(KOMUNIKAT_TYP_INFO, 'Proba trafienia wyniku');

    list($zakresOd, $zakresDo) = zakresLosowania($tresc);
    $typowanyWynik = typowanyWynik($zakresOd, $zakresDo);
    $trescWyniku   = strzelaj($typowanyWynik);

    return czyTrafienie($trescWyniku);
}

/**
 * Symulacja dzialania czlowieka
 */
function symulujCzlowieka()
{
    $czekaj = czekaj();
    komunikat(KOMUNIKAT_TYP_INFO, 'Oczekiwanie na losowanie: ' . formatujCzas($czekaj));

    return $czekaj;
}
