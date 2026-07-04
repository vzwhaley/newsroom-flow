<?php

/*
|--------------------------------------------------------------------------
| Curated local-news source directory (precision layer 2)
|--------------------------------------------------------------------------
|
| Maps a place to the domains of its real local outlets. When an area feed
| refreshes, the article engine BIASES toward these domains so results are
| genuinely local ("the Cleveland paper's front page") instead of national
| stories that merely mention the city.
|
| Resolution order (LocalSources::forArea): exact metro → US state → country.
| An area with no match still works — it just relies on the geocoded
| city/state/country query alone (precision layer 1).
|
| This is a living directory: extend it freely. Keys are lowercase.
|   'metros'   — keyed "city,st" (US) or "city,cc" (international)
|   'us_states'— keyed by 2-letter state, statewide outlets
|   'countries'— keyed by 2-letter country code, national/among-local outlets
|
*/

return [

    'metros' => [
        // ---- United States ----
        'new york,ny'      => ['nytimes.com', 'nypost.com', 'ny1.com', 'gothamist.com', 'amny.com'],
        'los angeles,ca'   => ['latimes.com', 'laist.com', 'dailynews.com', 'abc7.com'],
        'chicago,il'       => ['chicagotribune.com', 'chicago.suntimes.com', 'blockclubchicago.org', 'wgntv.com'],
        'houston,tx'       => ['houstonchronicle.com', 'khou.com', 'click2houston.com'],
        'phoenix,az'       => ['azcentral.com', 'abc15.com', 'azfamily.com'],
        'philadelphia,pa'  => ['inquirer.com', 'phillyvoice.com', '6abc.com', 'billypenn.com'],
        'san antonio,tx'   => ['expressnews.com', 'ksat.com', 'mysanantonio.com'],
        'san diego,ca'     => ['sandiegouniontribune.com', 'kpbs.org', 'nbcsandiego.com'],
        'dallas,tx'        => ['dallasnews.com', 'wfaa.com', 'dallasobserver.com'],
        'austin,tx'        => ['statesman.com', 'kxan.com', 'austonia.com', 'kut.org'],
        'san francisco,ca' => ['sfchronicle.com', 'sfgate.com', 'sfstandard.com', 'kqed.org'],
        'seattle,wa'       => ['seattletimes.com', 'kuow.org', 'king5.com', 'thestranger.com'],
        'denver,co'        => ['denverpost.com', 'denverite.com', '9news.com', 'coloradosun.com'],
        'boston,ma'        => ['bostonglobe.com', 'boston.com', 'wbur.org', 'universalhub.com'],
        'atlanta,ga'       => ['ajc.com', '11alive.com', 'atlantanewsfirst.com'],
        'miami,fl'         => ['miamiherald.com', 'wsvn.com', 'local10.com', 'miaminewtimes.com'],
        'washington,dc'    => ['washingtonpost.com', 'dcist.com', 'wtop.com', 'washingtoncitypaper.com'],
        'cleveland,oh'     => ['cleveland.com', 'news5cleveland.com', 'wkyc.com', 'ideastream.org'],
        'detroit,mi'       => ['freep.com', 'detroitnews.com', 'clickondetroit.com', 'bridgemi.com'],
        'minneapolis,mn'   => ['startribune.com', 'kare11.com', 'mprnews.org'],
        'portland,or'      => ['oregonlive.com', 'opb.org', 'kgw.com', 'wweek.com'],
        'las vegas,nv'     => ['reviewjournal.com', 'ktnv.com', 'lasvegassun.com'],
        'nashville,tn'     => ['tennessean.com', 'wsmv.com', 'newschannel5.com'],
        'new orleans,la'   => ['nola.com', 'wwltv.com', 'wdsu.com'],

        // ---- International (keyed "city,cc") ----
        'london,gb'        => ['standard.co.uk', 'mylondon.news', 'bbc.co.uk'],
        'manchester,gb'    => ['manchestereveningnews.co.uk', 'bbc.co.uk'],
        'toronto,ca'       => ['thestar.com', 'blogto.com', 'cp24.com', 'cbc.ca'],
        'vancouver,ca'     => ['vancouversun.com', 'dailyhive.com', 'cbc.ca'],
        'sydney,au'        => ['smh.com.au', 'abc.net.au', 'dailytelegraph.com.au'],
        'melbourne,au'     => ['theage.com.au', 'heraldsun.com.au', 'abc.net.au'],
        'dublin,ie'        => ['irishtimes.com', 'independent.ie', 'thejournal.ie'],
        'auckland,nz'      => ['nzherald.co.nz', 'stuff.co.nz', 'rnz.co.nz'],
    ],

    'us_states' => [
        'ny' => ['nytimes.com', 'nystateofpolitics.com'],
        'ca' => ['latimes.com', 'sfchronicle.com', 'calmatters.org'],
        'tx' => ['texastribune.org', 'dallasnews.com', 'houstonchronicle.com'],
        'fl' => ['miamiherald.com', 'tampabay.com', 'orlandosentinel.com'],
        'oh' => ['cleveland.com', 'dispatch.com', 'cincinnati.com'],
        'il' => ['chicagotribune.com', 'capitolnewsillinois.com'],
        'pa' => ['inquirer.com', 'spotlightpa.org', 'post-gazette.com'],
        'mi' => ['freep.com', 'bridgemi.com', 'mlive.com'],
        'wa' => ['seattletimes.com', 'spokesman.com'],
        'ga' => ['ajc.com', 'gpb.org'],
        'ma' => ['bostonglobe.com', 'masslive.com'],
        'co' => ['denverpost.com', 'coloradosun.com'],
        'or' => ['oregonlive.com', 'opb.org'],
        'az' => ['azcentral.com', 'azmirror.com'],
        'nc' => ['newsobserver.com', 'charlotteobserver.com', 'wral.com'],
        'nj' => ['nj.com', 'northjersey.com'],
        'va' => ['richmond.com', 'pilotonline.com', 'virginiamercury.com'],
        'tn' => ['tennessean.com', 'wbir.com'],
        'mn' => ['startribune.com', 'mprnews.org'],
        'la' => ['nola.com', 'theadvocate.com'],
        'nv' => ['reviewjournal.com', 'thenevadaindependent.com'],
        'wi' => ['jsonline.com', 'wisconsinwatch.org'],
        'mo' => ['stltoday.com', 'kansascity.com'],
        'in' => ['indystar.com'],
        'md' => ['baltimoresun.com', 'thebaltimorebanner.com'],
    ],

    'countries' => [
        'gb' => ['bbc.co.uk', 'theguardian.com', 'independent.co.uk', 'telegraph.co.uk'],
        'ca' => ['cbc.ca', 'theglobeandmail.com', 'thestar.com', 'nationalpost.com'],
        'au' => ['abc.net.au', 'smh.com.au', 'theage.com.au', 'news.com.au'],
        'ie' => ['irishtimes.com', 'rte.ie', 'independent.ie'],
        'nz' => ['nzherald.co.nz', 'stuff.co.nz', 'rnz.co.nz'],
        'in' => ['thehindu.com', 'timesofindia.indiatimes.com', 'indianexpress.com'],
        'za' => ['news24.com', 'timeslive.co.za', 'iol.co.za'],
        'sg' => ['straitstimes.com', 'channelnewsasia.com', 'todayonline.com'],
        'de' => ['dw.com', 'thelocal.de', 'spiegel.de'],
        'fr' => ['lemonde.fr', 'thelocal.fr', 'france24.com'],
    ],
];
