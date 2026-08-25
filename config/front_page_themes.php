<?php

return [
    /*
     * A future calendar selector can read start_date, end_date and priority from
     * each theme. Dates are intentionally unset in this preview-only version.
     */
    'active' => null,

    'themes' => [
        'sensommer' => ['id' => 'sensommer', 'name' => 'Sensommer', 'description' => 'Gyldent gress, grønt løv og sommerlys på hell.', 'start_date' => null, 'end_date' => null, 'priority' => null],
        'tidlig-host' => ['id' => 'tidlig-host', 'name' => 'Tidlig høst', 'description' => 'Lyse, grønne og gule blader ved høstens start.', 'start_date' => null, 'end_date' => null, 'priority' => null],
        'host' => ['id' => 'host', 'name' => 'Høst', 'description' => 'Varme, dempede toner og tydelige høstblader.', 'start_date' => null, 'end_date' => null, 'priority' => null],
        'senhost' => ['id' => 'senhost', 'name' => 'Senhøst', 'description' => 'Nakne greiner, kjølig kobber og roligere dager.', 'start_date' => null, 'end_date' => null, 'priority' => null],
        'forjul' => ['id' => 'forjul', 'name' => 'Førjul', 'description' => 'Mørk grønn gran og varme lys før advent.', 'start_date' => null, 'end_date' => null, 'priority' => null],
        'advent' => ['id' => 'advent', 'name' => 'Advent', 'description' => 'Dyp blå kveld med rolige, varme lys.', 'start_date' => null, 'end_date' => null, 'priority' => null],
        'jul' => ['id' => 'jul', 'name' => 'Jul', 'description' => 'Rødt, grønt og gull med gran og julepynt.', 'start_date' => null, 'end_date' => null, 'priority' => null],
        'nyttar' => ['id' => 'nyttar', 'name' => 'Nyttår', 'description' => 'Mørk natt, gullstjerner og et elegant fyrverkeri.', 'start_date' => null, 'end_date' => null, 'priority' => null],
        'mork-vinter' => ['id' => 'mork-vinter', 'name' => 'Mørk vinter', 'description' => 'Dyp blå stjernehimmel, frost og vinterro.', 'start_date' => null, 'end_date' => null, 'priority' => null],
        'vinter' => ['id' => 'vinter', 'name' => 'Vinter', 'description' => 'Klare blåtoner og lette snøfnugg.', 'start_date' => null, 'end_date' => null, 'priority' => null],
        'tidlig-var' => ['id' => 'tidlig-var', 'name' => 'Tidlig vår', 'description' => 'Spirer blant rester av frost og snø.', 'start_date' => null, 'end_date' => null, 'priority' => null],
        'var' => ['id' => 'var', 'name' => 'Vår', 'description' => 'Friske grønntoner, blomster og ny vekst.', 'start_date' => null, 'end_date' => null, 'priority' => null],
        'paske' => ['id' => 'paske', 'name' => 'Påske', 'description' => 'Gule egg, grønne kvister og lys vår.', 'start_date' => null, 'end_date' => null, 'priority' => null],
        '17-mai' => ['id' => '17-mai', 'name' => '17. mai', 'description' => 'Norske farger, flaggbånd og bjørkeblader.', 'start_date' => null, 'end_date' => null, 'priority' => null],
        'forsommer' => ['id' => 'forsommer', 'name' => 'Forsommer', 'description' => 'Lys himmel, friske blader og blomster.', 'start_date' => null, 'end_date' => null, 'priority' => null],
        'sommer' => ['id' => 'sommer', 'name' => 'Sommer', 'description' => 'Lyst, blått og rolig med solskinn.', 'start_date' => null, 'end_date' => null, 'priority' => null],
        'midtsommer' => ['id' => 'midtsommer', 'name' => 'Midtsommer', 'description' => 'Varm kveldssol, blomster og frodig grønt.', 'start_date' => null, 'end_date' => null, 'priority' => null],
    ],

    /*
     * A theme may occur more than once. Fixed ranges use month-day strings;
     * dynamic ranges are calculated by FrontPageThemeResolver in Europe/Oslo.
     */
    'calendar' => [
        ['theme' => 'nyttar', 'type' => 'fixed', 'start' => '12-29', 'end' => '01-03', 'priority' => 70],
        ['theme' => 'mork-vinter', 'type' => 'fixed', 'start' => '01-04', 'end' => '01-24', 'priority' => 10],
        ['theme' => 'vinter', 'type' => 'fixed', 'start' => '01-25', 'end' => '02-14', 'priority' => 10],
        ['theme' => 'tidlig-var', 'type' => 'fixed', 'start' => '02-15', 'end' => '03-09', 'priority' => 10],
        ['theme' => 'var', 'type' => 'fixed', 'start' => '03-10', 'end' => '05-03', 'priority' => 10],
        ['theme' => 'paske', 'type' => 'easter', 'priority' => 50],
        ['theme' => 'forsommer', 'type' => 'fixed', 'start' => '05-04', 'end' => '06-09', 'priority' => 10],
        ['theme' => '17-mai', 'type' => 'fixed', 'start' => '05-13', 'end' => '05-19', 'priority' => 50],
        ['theme' => 'sommer', 'type' => 'fixed', 'start' => '06-10', 'end' => '06-23', 'priority' => 10],
        ['theme' => 'midtsommer', 'type' => 'fixed', 'start' => '06-24', 'end' => '07-14', 'priority' => 10],
        ['theme' => 'sommer', 'type' => 'fixed', 'start' => '07-15', 'end' => '08-04', 'priority' => 10],
        ['theme' => 'sensommer', 'type' => 'fixed', 'start' => '08-05', 'end' => '08-25', 'priority' => 10],
        ['theme' => 'tidlig-host', 'type' => 'fixed', 'start' => '08-26', 'end' => '09-15', 'priority' => 10],
        ['theme' => 'host', 'type' => 'fixed', 'start' => '09-16', 'end' => '11-05', 'priority' => 10],
        ['theme' => 'senhost', 'type' => 'fixed', 'start' => '11-06', 'end' => '11-23', 'priority' => 10],
        ['theme' => 'forjul', 'type' => 'before_advent', 'priority' => 20],
        ['theme' => 'advent', 'type' => 'advent', 'priority' => 30],
        ['theme' => 'jul', 'type' => 'fixed', 'start' => '12-23', 'end' => '12-28', 'priority' => 60],
    ],
];
