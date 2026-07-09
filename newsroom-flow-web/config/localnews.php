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
| Domains favour stable flagship dailies + public radio/TV over volatile
| slugs. Verified against live outlets; spot-check when extending.
|
*/

return [

    'metros' => [
        // ============================ United States ============================
        // ---- Northeast ----
        'new york,ny'      => ['nytimes.com', 'nypost.com', 'ny1.com', 'gothamist.com', 'amny.com'],
        'buffalo,ny'       => ['buffalonews.com', 'btpm.org', 'wgrz.com'],
        'rochester,ny'     => ['democratandchronicle.com', 'wxxi.org', 'whec.com'],
        'albany,ny'        => ['timesunion.com', 'wamc.org', 'news10.com'],
        'boston,ma'        => ['bostonglobe.com', 'boston.com', 'wbur.org', 'universalhub.com'],
        'worcester,ma'     => ['telegram.com', 'masslive.com', 'wbur.org'],
        'philadelphia,pa'  => ['inquirer.com', 'phillyvoice.com', '6abc.com', 'billypenn.com'],
        'pittsburgh,pa'    => ['post-gazette.com', 'triblive.com', 'wesa.fm'],
        'hartford,ct'      => ['courant.com', 'ctpublic.org', 'wfsb.com'],
        'new haven,ct'     => ['nhregister.com', 'ctmirror.org', 'ctpublic.org'],
        'providence,ri'    => ['providencejournal.com', 'thepublicsradio.org', 'wpri.com'],
        'newark,nj'        => ['nj.com', 'northjersey.com', 'gothamist.com'],
        'manchester,nh'    => ['unionleader.com', 'nhpr.org', 'wmur.com'],
        'portland,me'      => ['pressherald.com', 'mainepublic.org', 'wmtw.com'],
        'burlington,vt'    => ['burlingtonfreepress.com', 'vermontpublic.org', 'wcax.com'],

        // ---- Mid-Atlantic / South Atlantic ----
        'washington,dc'    => ['washingtonpost.com', 'dcist.com', 'wtop.com', 'washingtoncitypaper.com'],
        'baltimore,md'     => ['baltimoresun.com', 'thebanner.com', 'wypr.org'],
        'wilmington,de'    => ['delawareonline.com', 'whyy.org', 'wdel.com'],
        'richmond,va'      => ['richmond.com', 'vpm.org', 'nbc12.com'],
        'virginia beach,va' => ['pilotonline.com', 'whro.org', '13newsnow.com'],
        'norfolk,va'       => ['pilotonline.com', 'whro.org', '13newsnow.com'],
        'charlotte,nc'     => ['charlotteobserver.com', 'wfae.org', 'wcnc.com'],
        'raleigh,nc'       => ['newsobserver.com', 'wral.com', 'wunc.org'],
        'charleston,sc'    => ['postandcourier.com', 'live5news.com', 'southcarolinapublicradio.org'],
        'columbia,sc'      => ['thestate.com', 'wistv.com', 'southcarolinapublicradio.org'],
        'atlanta,ga'       => ['ajc.com', '11alive.com', 'atlantanewsfirst.com', 'gpb.org'],
        'savannah,ga'      => ['savannahnow.com', 'wtoc.com', 'gpb.org'],
        'jacksonville,fl'  => ['jacksonville.com', 'news4jax.com', 'wjct.org'],
        'orlando,fl'       => ['orlandosentinel.com', 'cfpublic.org', 'clickorlando.com'],
        'miami,fl'         => ['miamiherald.com', 'wsvn.com', 'local10.com', 'miaminewtimes.com'],
        'tampa,fl'         => ['tampabay.com', 'wusf.org', 'wfla.com'],

        // ---- Midwest ----
        'cleveland,oh'     => ['cleveland.com', 'news5cleveland.com', 'wkyc.com', 'ideastream.org'],
        'columbus,oh'      => ['dispatch.com', 'wosu.org', 'nbc4i.com'],
        'cincinnati,oh'    => ['cincinnati.com', 'wcpo.com', 'wvxu.org'],
        'detroit,mi'       => ['freep.com', 'detroitnews.com', 'clickondetroit.com', 'bridgemi.com'],
        'grand rapids,mi'  => ['mlive.com', 'wgvunews.org', 'woodtv.com'],
        'chicago,il'       => ['chicagotribune.com', 'chicago.suntimes.com', 'blockclubchicago.org', 'wbez.org'],
        'indianapolis,in'  => ['indystar.com', 'wfyi.org', 'wthr.com'],
        'milwaukee,wi'     => ['jsonline.com', 'wuwm.com', 'tmj4.com'],
        'minneapolis,mn'   => ['startribune.com', 'kare11.com', 'mprnews.org'],
        'st. louis,mo'     => ['stltoday.com', 'stlpr.org', 'ksdk.com'],
        'kansas city,mo'   => ['kansascity.com', 'kcur.org', 'kshb.com'],
        'des moines,ia'    => ['desmoinesregister.com', 'iowapublicradio.org', 'kcci.com'],
        'omaha,ne'         => ['omaha.com', 'nebraskapublicmedia.org', 'ketv.com'],
        'wichita,ks'       => ['kansas.com', 'kmuw.org', 'kwch.com'],
        'fargo,nd'         => ['inforum.com', 'prairiepublic.org', 'valleynewslive.com'],
        'sioux falls,sd'   => ['argusleader.com', 'sdpb.org', 'keloland.com'],

        // ---- South Central ----
        'houston,tx'       => ['houstonchronicle.com', 'khou.com', 'houstonpublicmedia.org'],
        'san antonio,tx'   => ['expressnews.com', 'ksat.com', 'tpr.org'],
        'dallas,tx'        => ['dallasnews.com', 'wfaa.com', 'keranews.org'],
        'fort worth,tx'    => ['star-telegram.com', 'wfaa.com', 'keranews.org'],
        'austin,tx'        => ['statesman.com', 'kxan.com', 'kut.org'],
        'el paso,tx'       => ['elpasotimes.com', 'kvia.com', 'ktep.org'],
        'oklahoma city,ok' => ['oklahoman.com', 'kgou.org', 'kfor.com'],
        'tulsa,ok'         => ['tulsaworld.com', 'publicradiotulsa.org', 'ktul.com'],
        'new orleans,la'   => ['nola.com', 'wwltv.com', 'wwno.org'],
        'baton rouge,la'   => ['theadvocate.com', 'wafb.com', 'wrkf.org'],
        'little rock,ar'   => ['arkansasonline.com', 'ualrpublicradio.org', 'thv11.com'],
        'memphis,tn'       => ['commercialappeal.com', 'dailymemphian.com', 'wknofm.org'],
        'nashville,tn'     => ['tennessean.com', 'newschannel5.com', 'wsmv.com', 'wpln.org'],
        'knoxville,tn'     => ['knoxnews.com', 'wbir.com', 'wate.com', 'wvlt.tv', 'wuot.org'],
        // Northeast Tennessee — Tri-Cities. WJHL (News Channel 11) + WCYB are the
        // regional TV anchors covering all of NE TN; WETS is the ETSU/NPR station.
        'johnson city,tn'  => ['johnsoncitypress.com', 'wjhl.com', 'wets.org'],
        'jonesborough,tn'  => ['johnsoncitypress.com', 'wjhl.com', 'wets.org'],
        'telford,tn'       => ['johnsoncitypress.com', 'wjhl.com', 'wets.org'],
        'limestone,tn'     => ['greenevillesun.com', 'johnsoncitypress.com', 'wjhl.com'],
        'kingsport,tn'     => ['timesnews.net', 'wjhl.com', 'wcyb.com'],
        'bristol,tn'       => ['heraldcourier.com', 'wcyb.com', 'wjhl.com'],
        // Northeast Tennessee — Greene County. The Greeneville Sun is the
        // county's hometown daily; WEMT Fox 39 Greeneville runs under WCYB.
        'greeneville,tn'   => ['greenevillesun.com', 'wjhl.com', 'wcyb.com'],
        'afton,tn'         => ['greenevillesun.com', 'wjhl.com', 'wcyb.com'],
        'chuckey,tn'       => ['greenevillesun.com', 'wjhl.com', 'wcyb.com'],
        'louisville,ky'    => ['courier-journal.com', 'lpm.org', 'wlky.com'],
        'birmingham,al'    => ['al.com', 'wbhm.org', 'abc3340.com'],
        'jackson,ms'       => ['clarionledger.com', 'mpbonline.org', 'wlbt.com'],

        // ---- West ----
        'los angeles,ca'   => ['latimes.com', 'laist.com', 'dailynews.com', 'abc7.com'],
        'san diego,ca'     => ['sandiegouniontribune.com', 'kpbs.org', 'nbcsandiego.com'],
        'san francisco,ca' => ['sfchronicle.com', 'sfgate.com', 'sfstandard.com', 'kqed.org'],
        'san jose,ca'      => ['mercurynews.com', 'kqed.org', 'sanjosespotlight.com'],
        'sacramento,ca'    => ['sacbee.com', 'capradio.org', 'kcra.com'],
        'fresno,ca'        => ['fresnobee.com', 'kvpr.org', 'gvwire.com'],
        'seattle,wa'       => ['seattletimes.com', 'kuow.org', 'king5.com', 'thestranger.com'],
        'spokane,wa'       => ['spokesman.com', 'spokanepublicradio.org', 'krem.com'],
        'portland,or'      => ['oregonlive.com', 'opb.org', 'kgw.com', 'wweek.com'],
        'denver,co'        => ['denverpost.com', 'denverite.com', 'cpr.org', 'coloradosun.com'],
        'colorado springs,co' => ['gazette.com', 'krcc.org', 'koaa.com'],
        'phoenix,az'       => ['azcentral.com', 'kjzz.org', 'azfamily.com'],
        'tucson,az'        => ['tucson.com', 'azpm.org', 'kold.com'],
        'las vegas,nv'     => ['reviewjournal.com', 'knpr.org', 'ktnv.com'],
        'reno,nv'          => ['rgj.com', 'kunr.org', 'ktvn.com'],
        'salt lake city,ut' => ['sltrib.com', 'kuer.org', 'ksl.com'],
        'boise,id'         => ['idahostatesman.com', 'boisestatepublicradio.org', 'ktvb.com'],
        'albuquerque,nm'   => ['abqjournal.com', 'kunm.org', 'koat.com'],
        'billings,mt'      => ['billingsgazette.com', 'ypradio.org', 'ktvq.com'],
        'anchorage,ak'     => ['adn.com', 'alaskapublic.org', 'alaskasnewssource.com'],
        'honolulu,hi'      => ['staradvertiser.com', 'hawaiipublicradio.org', 'hawaiinewsnow.com'],

        // ============================ International ============================
        // keyed "city,cc"
        // ---- United Kingdom ----
        'london,gb'        => ['standard.co.uk', 'mylondon.news', 'bbc.co.uk'],
        'manchester,gb'    => ['manchestereveningnews.co.uk', 'bbc.co.uk'],
        'birmingham,gb'    => ['birminghammail.co.uk', 'bbc.co.uk'],
        'liverpool,gb'     => ['liverpoolecho.co.uk', 'bbc.co.uk'],
        'leeds,gb'         => ['leeds-live.co.uk', 'yorkshireeveningpost.co.uk', 'bbc.co.uk'],
        'glasgow,gb'       => ['glasgowlive.co.uk', 'heraldscotland.com', 'bbc.co.uk'],
        'edinburgh,gb'     => ['edinburghlive.co.uk', 'scotsman.com', 'bbc.co.uk'],
        // ---- Canada ----
        'toronto,ca'       => ['thestar.com', 'blogto.com', 'cp24.com', 'cbc.ca'],
        'vancouver,ca'     => ['vancouversun.com', 'dailyhive.com', 'cbc.ca'],
        'montreal,ca'      => ['montrealgazette.com', 'cbc.ca', 'cultmtl.com'],
        'calgary,ca'       => ['calgaryherald.com', 'livewirecalgary.com', 'cbc.ca'],
        'edmonton,ca'      => ['edmontonjournal.com', 'cbc.ca'],
        'ottawa,ca'        => ['ottawacitizen.com', 'cbc.ca'],
        // ---- Australia ----
        'sydney,au'        => ['smh.com.au', 'abc.net.au', 'dailytelegraph.com.au'],
        'melbourne,au'     => ['theage.com.au', 'heraldsun.com.au', 'abc.net.au'],
        'brisbane,au'      => ['couriermail.com.au', 'brisbanetimes.com.au', 'abc.net.au'],
        'perth,au'         => ['thewest.com.au', 'watoday.com.au', 'abc.net.au'],
        'adelaide,au'      => ['adelaidenow.com.au', 'abc.net.au'],
        // ---- Ireland / New Zealand ----
        'dublin,ie'        => ['irishtimes.com', 'independent.ie', 'thejournal.ie'],
        'cork,ie'          => ['echolive.ie', 'irishexaminer.com', 'rte.ie'],
        'auckland,nz'      => ['nzherald.co.nz', 'stuff.co.nz', 'rnz.co.nz'],
        'wellington,nz'    => ['stuff.co.nz', 'rnz.co.nz', 'nzherald.co.nz'],
        'christchurch,nz'  => ['stuff.co.nz', 'rnz.co.nz'],
        // ---- India / South Africa ----
        'mumbai,in'        => ['mid-day.com', 'hindustantimes.com', 'timesofindia.indiatimes.com'],
        'delhi,in'         => ['hindustantimes.com', 'thehindu.com', 'indianexpress.com'],
        'bengaluru,in'     => ['deccanherald.com', 'thehindu.com', 'timesofindia.indiatimes.com'],
        'johannesburg,za'  => ['timeslive.co.za', 'news24.com', 'ewn.co.za'],
        'cape town,za'     => ['iol.co.za', 'news24.com', 'timeslive.co.za'],
    ],

    'us_states' => [
        'al' => ['al.com', 'montgomeryadvertiser.com', 'alreporter.com'],
        'ak' => ['adn.com', 'alaskapublic.org'],
        'az' => ['azcentral.com', 'azmirror.com', 'tucson.com'],
        'ar' => ['arkansasonline.com', 'arkansasadvocate.com'],
        'ca' => ['latimes.com', 'sfchronicle.com', 'calmatters.org'],
        'co' => ['denverpost.com', 'coloradosun.com', 'cpr.org'],
        'ct' => ['courant.com', 'ctmirror.org', 'ctpost.com'],
        'de' => ['delawareonline.com', 'delawarepublic.org'],
        'fl' => ['miamiherald.com', 'tampabay.com', 'orlandosentinel.com'],
        'ga' => ['ajc.com', 'gpb.org'],
        'hi' => ['staradvertiser.com', 'hawaiipublicradio.org', 'civilbeat.org'],
        'id' => ['idahostatesman.com', 'idahopress.com', 'boisestatepublicradio.org'],
        'il' => ['chicagotribune.com', 'capitolnewsillinois.com', 'wbez.org'],
        'in' => ['indystar.com', 'indianacapitalchronicle.com'],
        'ia' => ['desmoinesregister.com', 'iowapublicradio.org'],
        'ks' => ['kansas.com', 'cjonline.com', 'kansasreflector.com'],
        'ky' => ['courier-journal.com', 'kentucky.com', 'lpm.org'],
        'la' => ['nola.com', 'theadvocate.com'],
        'me' => ['pressherald.com', 'bangordailynews.com', 'mainepublic.org'],
        'md' => ['baltimoresun.com', 'thebanner.com'],
        'ma' => ['bostonglobe.com', 'masslive.com', 'wbur.org'],
        'mi' => ['freep.com', 'bridgemi.com', 'mlive.com'],
        'mn' => ['startribune.com', 'mprnews.org'],
        'ms' => ['clarionledger.com', 'mississippitoday.org'],
        'mo' => ['stltoday.com', 'kansascity.com'],
        'mt' => ['billingsgazette.com', 'montanafreepress.org', 'mtpr.org'],
        'ne' => ['omaha.com', 'journalstar.com', 'nebraskapublicmedia.org'],
        'nv' => ['reviewjournal.com', 'thenevadaindependent.com'],
        'nh' => ['unionleader.com', 'nhpr.org', 'concordmonitor.com'],
        'nj' => ['nj.com', 'northjersey.com'],
        'nm' => ['abqjournal.com', 'santafenewmexican.com', 'sourcenm.com'],
        'ny' => ['nytimes.com', 'nystateofpolitics.com'],
        'nc' => ['newsobserver.com', 'charlotteobserver.com', 'wral.com'],
        'nd' => ['inforum.com', 'bismarcktribune.com', 'prairiepublic.org'],
        'oh' => ['cleveland.com', 'dispatch.com', 'cincinnati.com'],
        'ok' => ['oklahoman.com', 'tulsaworld.com'],
        'or' => ['oregonlive.com', 'opb.org'],
        'pa' => ['inquirer.com', 'spotlightpa.org', 'post-gazette.com'],
        'ri' => ['providencejournal.com', 'thepublicsradio.org'],
        'sc' => ['postandcourier.com', 'thestate.com', 'greenvilleonline.com'],
        'sd' => ['argusleader.com', 'sdpb.org'],
        'tn' => ['tennessean.com', 'wbir.com', 'commercialappeal.com'],
        'tx' => ['texastribune.org', 'dallasnews.com', 'houstonchronicle.com'],
        'ut' => ['sltrib.com', 'deseret.com', 'kuer.org'],
        'vt' => ['vtdigger.org', 'burlingtonfreepress.com', 'vermontpublic.org'],
        'va' => ['richmond.com', 'pilotonline.com', 'virginiamercury.com'],
        'wa' => ['seattletimes.com', 'spokesman.com'],
        'wv' => ['wvgazettemail.com', 'wvnews.com', 'wvpublic.org'],
        'wi' => ['jsonline.com', 'wisconsinwatch.org'],
        'wy' => ['wyofile.com', 'trib.com', 'wyomingpublicmedia.org'],
    ],

    'countries' => [
        'gb' => ['bbc.co.uk', 'theguardian.com', 'independent.co.uk', 'telegraph.co.uk'],
        'ca' => ['cbc.ca', 'theglobeandmail.com', 'thestar.com', 'nationalpost.com'],
        'au' => ['abc.net.au', 'smh.com.au', 'theage.com.au', 'news.com.au'],
        'ie' => ['irishtimes.com', 'rte.ie', 'independent.ie'],
        'nz' => ['nzherald.co.nz', 'stuff.co.nz', 'rnz.co.nz'],
        'in' => ['thehindu.com', 'timesofindia.indiatimes.com', 'indianexpress.com'],
        'za' => ['news24.com', 'timeslive.co.za', 'iol.co.za'],
        'sg' => ['straitstimes.com', 'channelnewsasia.com'],
        'de' => ['dw.com', 'thelocal.de', 'spiegel.de'],
        'fr' => ['lemonde.fr', 'thelocal.fr', 'france24.com'],
        'nl' => ['nltimes.nl', 'dutchnews.nl'],
        'es' => ['elpais.com', 'thelocal.es'],
        'it' => ['ansa.it', 'thelocal.it'],
        'mx' => ['eluniversal.com.mx', 'mexiconewsdaily.com'],
        'br' => ['folha.uol.com.br', 'riotimesonline.com'],
        'jp' => ['japantimes.co.jp', 'nhk.or.jp', 'japantoday.com'],
        'ph' => ['inquirer.net', 'rappler.com', 'philstar.com'],
        'ng' => ['punchng.com', 'premiumtimesng.com', 'guardian.ng'],
        'ke' => ['nation.africa', 'standardmedia.co.ke', 'the-star.co.ke'],
    ],
];
