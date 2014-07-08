<?php

// konfiguracja
require_once dirname(__FILE__) . '/cfg/config.php';
// biblioteka
require_once dirname(__FILE__) . '/lib/lib.php';


$wynik = null;
do {
    $tresc = stronaKonkursu();

    // logowanie
    if (jestWTresci($tresc, 'Podaj swoj± nazwê u¿ytkownika i has³o.')) {
        zalogujDoSerwisu();
    }
    
    // przekroczona ilosc zwyciestw w miesiacu
    elseif (jestWTresci($tresc, 'Nie mo¿esz zwyciê¿yæ w konkursie wiêcej ni¿')) {
        komunikat(KOMUNIKAT_TYP_INFO, 'Przekroczono dopuszczalna ilosc zwyciestw w miesiacu');
        $wynik = 0;
    }
    
    // losowanie jeszcze sie nie zaczelo
    elseif (jestWTresci($tresc, 'Do rozpoczêcia dzisiejszej edycji pozosta³o jeszcze')) {
        $czas  = czasOczekiwaniaNaNowaSesje($tresc);
        $czekaj = czekaj($czas);
        komunikat(KOMUNIKAT_TYP_INFO, 'Oczekiwanie na nowa sesje tego dnia: ' . formatujCzas($czekaj) . ' (wymagane: ' . $czas . ')');
        $wynik = $czekaj;
    }
    
    // trzeba poczekac na losowanie
    elseif (jestWTresci($tresc, 'Musisz zaczekaæ jeszcze')) {
        $czas = czasOczekiwaniaNaLosowanie($tresc);
        $czekaj = czekaj($czas);
        komunikat(KOMUNIKAT_TYP_INFO, 'Oczekiwanie na losowanie: ' . formatujCzas($czekaj) . ' (wymagane: ' . $czas . ')');

        // mozna skrocic czas oczekiwania
        if (jestWTresci($tresc, 'Skróæ do 15 minut czekanie')) {
            zakresLosowania($tresc);
            skrocCzasOczekiwaniaNaLosowanie();
        } else {
            $wynik = $czekaj;
        }
    }
    
    // koniec edycji - ktos wygral wczesniej
    elseif (jestWTresci($tresc, 'Do udzia³u w nastêpnej edycji zapraszamy jutro.')) {
        $ktoWygral = ktoWygral($tresc);
        komunikat(KOMUNIKAT_TYP_INFO, 'W dniu dzisiejszym koniec edycji.\n' . $ktoWygral);
        $wynik = 0;
    }
    
    // losowanie
    elseif (jestWTresci($tresc, 'Dzisiejsza liczba wylosowana dla ciebie ')) {
        if (typujWynik($tresc)) {
            $wynik = 0;
        } else {
            $czekaj = czekaj();
            komunikat(KOMUNIKAT_TYP_INFO, 'Oczekiwanie na losowanie: ' . formatujCzas($czekaj));
            $wynik = $czekaj;
        }
    }
    
    // blad
    else {
        komunikat(KOMUNIKAT_TYP_BLAD, 'Cos poszlo nie tak');
        $wynik = 0;
    }
} while (is_null($wynik));

// kod wyjscia
exit($wynik);