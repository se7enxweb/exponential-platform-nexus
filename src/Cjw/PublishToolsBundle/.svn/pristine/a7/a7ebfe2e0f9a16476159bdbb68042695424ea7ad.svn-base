<?php
namespace Cjw\PublishToolsBundle\Classes;

// include_once( "lib/ezxml/classes/ezxml.php" );

use DOMDocument;
use SimpleXMLElement;

class CjwPublishToolsWeather
{

    private $_format                = 'json';
    private $_timeOut               = 60;
//    private $_waterRegionId         = '2';
    private $_bshWaterId         = 2;
//    private $_waterBSHServerUrl     = 'https://www2.bsh.de/aktdat/bum/station_relaunch2.json';
    private $_waterBSHServerUrl     = 'https://www2.bsh.de/aktdat/bum/bum_data.json';
    private $_cacheDir  = 'var/site/storage-extra/wetter';
    private $_cachePath = 'var/site/storage-extra/wetter/current.html';
    private $_cacheFilename = 'current.html';
    private $_cacheSubDir           = '/wustrow_status';
    private $_cacheStatusFile           = 'status';
    const API_1_0    = 1;
    const API_2_0    = 2;
    private $_apiUrl_2_0            = "";
    private $_cacheFileName         = "";
    private $_container         = "";
    private $symbolCodeTranslations         = "";




    public function __construct($container = false)
    {
        if( $container )$this->_container = $container;

        setlocale(LC_ALL, 'de_DE@euro', 'de_DE', 'deu_deu');
        $this->setSymbolcodeTranslations();


    }



//    function getWaterTemp( $waterRegionId = false )
//    {
//
//        return $this->getWaterBSH($waterRegionId);
//    }

    protected function getWaterBSH( $BSHWaterId = 140130, $current_only = false )
    {
        if( $this->getContainer()->hasParameter('CacheFileNameWater')) {
            $cacheFileName = $this->getContainer()->getParameter('CacheFileNameWater');
            $cachePath = $this->_cacheDir.'/'.$cacheFileName;
        }
        $this->_bshWaterId = $BSHWaterId;
        $cacheTimeout                   = 5; // In Minuten
        $waterBSHServiceUrlWithLocation = $this->_waterBSHServerUrl;
        $json = $this->getFileContentWithCacheCheck ( $cacheTimeout, $this->_waterBSHServerUrl, $cachePath  );
        //get json with all water temperatures from Ostsee and Nordsee
//        $json = file_get_contents($waterBSHServiceUrlWithLocation);


        //if there is no json
        //set status file
        //send a mail with defined parameters
        if ( !$json )
            {
                $locations[140130] = 'ostseebad-wustrow';
                $locations[140105] = 'ostseebad-dierhagen';
                $now = time(); //jetzt
                $todaySeconds = $now % 86400;//vergangene Sekunden

                //Check whether status file exists
                //If not
                //create statusFile
                //Trigger email
                $useCache  = false;
                $status    = true;
                $cacheDir  = $this->_cacheDir.$this->_cacheSubDir ;
                $cachePath = $cacheDir.'/water_status';
                if ( !file_exists( $cacheDir ) ) {
                    if (!mkdir($cacheDir, 0777, true)) {
//                                    eZDebug::writeError("Couldn't create cache directory $cacheDir, perhaps wrong permissions", "eZINI");

                    }
                }
                if ( !file_exists( $cachePath ) )
                {
                     touch( $cachePath );
                    #####################

                    $to      = 'marco.wolff@jac-systeme.de';
                    $subject = 'Wasserdaten sind nicht erreichbar';
                    $message = 'Es wurden keine Wasserdaten von BSH.de empfangen';
                    $headers = 'From: webmaster@'.$locations[$BSHWaterId].'.de' . "\r\n" .
                        'Reply-To: noreply' . "\r\n" .
                        'X-Mailer: PHP/' . phpversion();

                    mail($to, $subject, $message, $headers);
                    #####################

                }
                //check time between 5 and 11
                //Trigger email
                else
                {
                    $todayHours = $todaySeconds / 3600;
                    $todayHours = round( $todayHours);

                    $weatherDate = date ("F d Y H:i:s.", filemtime( $cachePath ) );
                    if( $todayHours < 11 && $todayHours > 5 ) //zwischen 5 Uhr und 11 Uhr einmal pro Tag eine Mail
                    {
                        if ( !file_exists( $cacheDir. '/water_status'.date( "m.d.y") ) )
                        {
                         touch(  $cacheDir.'/water_status'.date( "m.d.y") );
                        #####################

                        $to      = 'marco.wolff@jac-systeme.de,dirk.lampe@jac-systeme.de';
                        $subject = 'Wasserdaten sind nicht erreichbar';
                        $message = 'Es wurden keine Wasserdaten von BSH.de empfangen. Die Daten sind vom '.$weatherDate. '.';
                        $headers = 'From: webmaster@'.$locations[$BSHWaterId].'.de' . "\r\n" .
                            'Reply-To: noreply' . "\r\n" .
                            'X-Mailer: PHP/' . phpversion();

                        mail( $to, $subject, $message, $headers );
                        #####################

                        }
                    }

                }
                $current[ 'cur_temp_c' ] = 'n./a.';
                return $current;
            }
//        return $BSHWaterId;
//        return json_decode($json,true);
        $jsonObject = json_decode($json,true);
//        echo "<pre>";var_dump($jsonObject[$BSHWaterId]);echo "<pre>";die();
        $water = false;
        //set water forecast
        //Look up for sstdata in json
        //Mapping water temperature from sstdata to today (morning noon evening night) and tomorrow (morning noon evening night)
        if ( isset( $jsonObject[$BSHWaterId]['sstdata'] ) && $jsonObject[$BSHWaterId]['sstdata'] != null ){
            if (! $current_only )
            {
                $water[ 'h6' ]     = $jsonObject[$BSHWaterId]['sstdata']['1']['0'];
                $water[ 'h12' ]    = $jsonObject[$BSHWaterId]['sstdata']['1']['1'];
                $water[ 'h18' ]    = $jsonObject[$BSHWaterId]['sstdata']['1']['2'];
                $water[ 'h0' ]     = $jsonObject[$BSHWaterId]['sstdata']['1']['3'];
                $water[ 'm6' ]     = $jsonObject[$BSHWaterId]['sstdata']['2']['0'];
                $water[ 'm12' ]    = $jsonObject[$BSHWaterId]['sstdata']['2']['1'];
                $water[ 'm18' ]    = $jsonObject[$BSHWaterId]['sstdata']['2']['2'];
                $water[ 'm0' ]     = $jsonObject[$BSHWaterId]['sstdata']['2']['3'];
            }


            //set Current Water Temp
            //Find out the time of day (morning noon evening night) and match it with the mapped sstdata
            $currentHour = date('H', time());
            if( $currentHour >= 0 &&  $currentHour < 9  ) $water['current'] = $water['h6'];
            if( $currentHour >= 9 &&  $currentHour < 15  ) $water['current'] = $water['h12'];
            //if( $currentHour >= 9 &&  $currentHour < 15  ) $water['current'] = 99;
            if( $currentHour >= 15 &&  $currentHour < 21  ) $water['current'] = $water['h18'];
            if( $currentHour >= 21 &&  $currentHour < 24  ) $water['current'] = $water['h0'];
        }


        //$currentWater = $jsonObject[$BSHWaterId]['sstdata']['1']['1'];
        return $water;
    }
//    function prepareCurrentWaterTemp()
//    {
//        $currentHour = date('H', time());
//    }
//
//    function request ( $location )
//    {
//        return null;
//    }

    //use for Wustrow Weather, delivered from DLRG-Tower to our ftp-Server
    function htmlRequest( $htmlRequestServiceUrl , $BSHWaterId = null , $container = false )
    {
        if( $container )$this->_container = $container;
        if( $this->getContainer()->hasParameter('CacheFileName')) {
            $this->_cacheFileName = $this->getContainer()->getParameter('CacheFileName');
            $this->_cachePath = $this->_cacheDir.'/'.$this->_cacheFileName;
        }
        // url ('http://www.ostseebad-wustrow.de/var/ostseebad-wustrow/storage-extra/wetter/current.html');
        $htmlRequestServiceHtml  = $this->getFileContentWithCacheCheck ( $this->_timeOut, $htmlRequestServiceUrl, $this->_cachePath );
        $weatherDate = $this->extractDate ( $htmlRequestServiceHtml, '<meta name="date" content="', 10 );

        //Prüfen ob Datei aktuell ist
        //nach 26 Stunden ist die Datei veraltet.
        $weatherTimestamp = strtotime($weatherDate) + 7200;// Plus zwei Stunden wegen Sommer und Winterzeit
        $now = time(); //jetzt
        $todaySeconds = $now % 86400;
        $today = $now - $todaySeconds ;// current Day 00:00:00
        if ( $weatherTimestamp <  $today )
        {
            //prüfen ob statusFile existiert
            //Falls nein
            //statusFile anlegen
            //Email auslösen
            $useCache  = false;
            $status    = true;
            $cacheDir  = $this->_cacheDir.$this->_cacheSubDir;
            $cachePath = $cacheDir.'/weather_status' ;
            if ( !file_exists( $cacheDir ) )
            {
                if ( mkdir( $cacheDir, 0777, true ) )
                {


                }
            }

            if ( !file_exists( $cachePath ) )
            {
                touch( $cachePath );
                #####################

                $to      = 'marco.wolff@jac-systeme.de';
                $subject = 'Wetter in Wustrow veraltet';
                $message = 'Wetterdaten sind vom '.$weatherDate;
                $headers = 'From: webmaster@ostseebad-wustrow.de' . "\r\n" .
                    'Reply-To: noreply' . "\r\n" .
                    'X-Mailer: PHP/' . phpversion();

                mail($to, $subject, $message, $headers);
                #####################

            }
            //Falls ja
            //Zeit prüfen und gegen 8 Uhr ne Mail schicken
            else
            {
                $todayHours = $todaySeconds / 3600;
                $todayHours = round( $todayHours);
                if( $todayHours < 11 && $todayHours > 5)
                {
                    if ( !file_exists($cacheDir. '/weather_status'.date( "m.d.y") ) )
                    {
                        touch( $cacheDir. '/weather_status'.date( "m.d.y") ) ;
                        #####################

                        $to      = 'marco.wolff@jac-systeme.de';
                        $subject = 'Wetter in Wustrow veraltet';
                        $message = 'Die Wetterdaten sind vom '.$weatherDate;
                        $headers = 'From: webmaster@ostseebad-wustrow.de' . "\r\n" .
                            'Reply-To: noreply' . "\r\n" .
                            'X-Mailer: PHP/' . phpversion();

                        mail($to, $subject, $message, $headers);
                        #####################

                    }
                }

            }



            $current[ 'cur_temp_c' ] = 'n./a.';
        }
        else
        {
            $current[ 'cur_temp_c' ] = $this->extract_partical_text ( $htmlRequestServiceHtml, 'Temperatur Außen', 'aktuell*°C' );
            $temp = explode( '°C', $current[ 'cur_temp_c' ]);
            if ( count($temp) > 1 )
            {
                $current[ 'cur_temp_c' ] = $temp[0];
            }

        }
        $water_data              = $this->getWaterBSH( $BSHWaterId );

        $content = array( "current_conditions" => $current, "forecast" => false, "current_water" => $water_data );

        return $content;
    }


    //Wetter ausliefern je nach Bedarf
    //Unterscheiden ob alte Api oder neue Api.
    // Alte Api ist definitiv abgeschaltet, aber bei einem neuen Api-Change
    //kann der Code schnell angepasst werden.
    //Mode und Url in der yml speichern!

    // lat and lon des gesuchten Ortes
    private function getWeather( $apiUrl, $apiMode = 2 )
    {
        $weatherData = false;
        $xmlOrJson = false;
        $doc = false;
        $timeList = false;
        $this->_apiUrl_2_0 = $apiUrl;
        $cachePath = $this->_cachePath;
        switch ($apiMode)
        {
            //For the new Api with xml
            // URL -> https://api.met.no/weatherapi/locationforecast/2.0/classic?lat=54.2875153&lon=12.2660585
            //classic
            //TODO change to json
            case CjwPublishToolsWeather::API_2_0:
                if( $this->getContainer()->hasParameter('CacheFileName')) {
                    $this->_cacheFileName = $this->getContainer()->getParameter('CacheFileName');
                    $this->_cachePath = $this->_cacheDir.'/'.$this->_cacheFileName;
                }
                $xmlOrJson = $this->getFileContentWithCacheCheck(5, $this->_apiUrl_2_0, $this->_cachePath );
                if ( strpos( $xmlOrJson, 'xmlns') > 0 ){

                    $doc = $this->getDoc($xmlOrJson);
                    if( $doc && isset( $doc) )
                    {
                        $timeList = $doc->getElementsByTagName('time');
                        if( $timeList && isset( $timeList) )
                        {
//                          $weatherData = $this->prepareCurrentWeatherWithNewApiXml($timeList);
                            $weatherData['forecast'] = $this->prepareForecastWithNewApiXml($timeList);
                            reset( $weatherData['forecast']);
                            $weatherData['current_conditions'] = $weatherData['forecast'][key($weatherData['forecast'])];
                        }
                    }
                }
                //For the new Api with json
                // URL -> https://api.met.no/weatherapi/locationforecast/2.0/complete?lat=54.2875153&lon=12.2660585
                //complete

                else{
                    $data = json_decode( $xmlOrJson ,true );
//                    return json_decode( $xmlOrJson ,true );
                    if ($data["type"] == "Feature"){
                        $data = json_decode( $xmlOrJson ,true );
                        $weatherData['forecast'] = $this->prepareForecastWithNewApiJson($data["properties"]["timeseries"]);
                        reset( $weatherData['forecast']);
                        $weatherData['current_conditions'] = $weatherData['forecast'][key($weatherData['forecast'])];
//                        $weatherData['current_conditions'] = $this->prepareCurrentWeatherWithNewApiJson($data["properties"]["timeseries"][0]);
                    }
                }

//            case CjwPublishToolsWeather::API_1_0: // Not used, but maybe hold for the next ApiChange
//                $xmlOrJson = $this->getFileContentWithCacheCheck(5, $this->_apiUrl, $this->_cacheFileName);
//                if( isset( $xmlOrJson->status ) )
//                {
//                    $doc = $this->getDoc($xmlOrJson);
//                    if( $doc && isset( $doc) )
//                    {
//                        $timeList = $doc->getElementsByTagName('time');
//                        if( $timeList && isset( $timeList) )
//                        {
//                            $weatherData = $this->prepareCurrentWeather($timeList);
//                            $weatherData['forecast'] = $this->prepareForecast($timeList);
//                        }
//                    }
//                }

        }
        return $weatherData;
    }

    //Generate DOMDocument from xml
    private function getDoc($xml)
    {
        $doc= new DOMDocument();
        $doc->validateOnParse = true;
        if($doc->loadXML( $xml ))
        {
            return $doc;
        }
        return false;
    }



    private function prepareForecastWithNewApiJson( $times , $onlyCurrent = false)
    {
        //TODO fertigstellen bei Bedarf die Struktur der Json ist völlig anders als die der Xml
        //einige Informationen sind nicht vorhanden
        $forecast = false;
        $id = 0;
        $i = 0;
        $forecastHours = 48 ;
        $now = time();
        $forecastEndTime = 3600 * $forecastHours + $now;//Von jetzt an 48 Stunden
        foreach ($times as $time )
        {



            $_day                    = explode('T', $time["time"])[0];
            $_time                    = explode('T', $time["time"])[1];
            $_time                    = explode('Z', $_time)[0];
            //$forecast[$i] ['day']                     = explode('T',$time[$i]->getAttribute('from'))[0];
            //$forecast[$i] ['daytime']                 = explode('T',$time[$i]->getAttribute('from'))[1];
            $timestamp              = strtotime($_day." ".$_time);

            if (  $timestamp <= $forecastEndTime
                && $timestamp >= $now - 3600 ) {
                $i = $timestamp;
                $forecast[$i] = [];
                $forecast[$i] ['timestamp'] = $timestamp;
                $forecast[$i] ['day'] = date("D j.m.Y", $forecast[$i] ['timestamp']);
                $forecast[$i] ['daytime'] = date("H:i", $forecast[$i] ['timestamp']);
                $forecast[$i] ['cur_temp_c'] = $time["data"]["instant"]["details"]["air_temperature"];

                $forecast[$i] ['cur_precipitation'] = false;
                $forecast[$i] ['probability_of_precipitation'] = false;
                $forecast[$i] ['cur_icon'] = "cloudy.svg";

                if (isset($time["data"]["next_1_hours"]) || array_key_exists("next_1_hours", $time["data"])) {
                    $forecast[$i] ['cur_precipitation'] = (float)$time["data"]["next_1_hours"]["details"]["precipitation_amount"];
                    $forecast[$i] ['probability_of_precipitation'] = (float)$time["data"]["next_1_hours"]["details"]["probability_of_precipitation"];
                    $forecast[$i] ['cur_icon'] = $time["data"]["next_1_hours"]["summary"]["symbol_code"] . '.svg';
                    $forecast[$i] ['symbol_code_translations'] = $this->getSymbolcodeTranslations( explode( "_" ,$time["data"]["next_1_hours"]["summary"]["symbol_code"] )[0] ) ;
                    $forecast[$i] ['id_condition'] = $time["data"]["next_1_hours"]["summary"]["symbol_code"] ;

                }

                $forecast[$i] ['cur_wind'] = $time["data"]["instant"]["details"]["wind_speed"];
                $forecast[$i] ['wind_kmh'] = round(3.6 * $time["data"]["instant"]["details"]["wind_speed"]);
                $forecast[$i] ['wind_mps'] = $time["data"]["instant"]["details"]["wind_speed"];
                $forecast[$i] ['cur_winddirection'] = round($time["data"]["instant"]["details"]["wind_from_direction"]);
                $forecast[$i] ['wind_speed_of_gust'] = $time["data"]["instant"]["details"]["wind_speed_of_gust"];
                $forecast[$i] ['air_pressure'] = $time["data"]["instant"]["details"]["air_pressure_at_sea_level"];
                $forecast[$i] ['relative_humidity'] = $time["data"]["instant"]["details"]["relative_humidity"];

                if ($onlyCurrent) return $forecast;
            }
        }
        return $forecast;
    }

    private function prepareForecastWithNewApiXml($times, $onlyCurrent = false)
    {
        $forecast = false;
        $id = 0;
        $forecastHours = 58 ;
        $now = time();
        $forecastEndTime = 3600 * $forecastHours + $now;//Von jetzt an 48 Stunden
        foreach ($times as $time )
        {
            /*
            time hat 3 unterschiedliche Formate.
            Die "Sechs-Stunden Zusammenfassung" wird sofort aussortiert !
            <time datatype="forecast" from="2021-11-04T18:00:00Z" to="2021-11-05T00:00:00Z">
             <location altitude="0" latitude="54.28751" longitude="12.26605">
               <precipitation unit="mm" value="6.3" minvalue="3.4" maxvalue="9.0"></precipitation>
               <minTemperature id="TTT" unit="celsius" value="8.9"></minTemperature>
               <maxTemperature id="TTT" unit="celsius" value="9.3"></maxTemperature>
               <symbol id="Rain" number="10" code="heavyrain"></symbol>
               <symbolProbability unit="probabilitycode" value="1"></symbolProbability>
             </location>
            </time>

            Die anderen Formate enthalten die wichtigen Informationen.
            Diese haben einen Zeitraum ($period) von 0 oder 1 Stunde
               //Temperatur, Windrichtung, Windstärke, (Luftdruck, Feuchtigkeit)
                <time datatype="forecast" from="2021-11-05T00:00:00Z" to="2021-11-05T00:00:00Z"> <== Zeitraum 0
                 <location altitude="0" latitude="54.28751" longitude="12.26605">
                   <temperature id="TTT" unit="celsius" value="9.3"></temperature>
                   <windDirection id="dd" deg="326.8" name="NW"></windDirection>
                   <windSpeed id="ff" mps="11.3" beaufort="6" name="Liten kuling"></windSpeed>
                   <windGust id="ff_gust" mps="15.0"></windGust>
                   <humidity unit="percent" value="83.5"></humidity>
                   <pressure id="pr" unit="hPa" value="1004.9"></pressure>
                   <cloudiness id="NN" percent="100.0"></cloudiness>
                   <fog id="FOG" percent="0.0"></fog>
                   <lowClouds id="LOW" percent="58.2"></lowClouds>
                   <mediumClouds id="MEDIUM" percent="100.0"></mediumClouds>
                   <highClouds id="HIGH" percent="100.0"></highClouds>
                   <temperatureProbability unit="probabilitycode" value="0"></temperatureProbability>
                   <windProbability unit="probabilitycode" value="0"></windProbability>
                   <dewpointTemperature id="TD" unit="celsius" value="6.6"></dewpointTemperature>
                 </location>
               </time>

               //Regenmenge und Code für die Bewölkung
               <time datatype="forecast" from="2021-11-04T23:00:00Z" to="2021-11-05T00:00:00Z"> <== Zeitraum 1 Stunde
                 <location altitude="0" latitude="54.28751" longitude="12.26605">
                   <precipitation unit="mm" value="0.4" minvalue="0.0" maxvalue="0.4"></precipitation>
                   <symbol id="LightRain" number="9" code="rain"></symbol>
                 </location>
               </time>
            */
            $period = 3601; // greater than 1 Hour
            $timeFrom = 0;
            if ( ! ( $time->getAttribute('from') === null ) )
            {
                $timeFrom =  strtotime(str_replace(['T','Z'],' ',$time->getAttribute('from')));
                $timeTo =  strtotime(str_replace(['T','Z'],' ',$time->getAttribute('to')));
                $period = $timeTo - $timeFrom;

            }
            //"Sechs-Stunden-Zusammenfassung" hat 21600s.
            if ( $period <= 3600 //Prüfen ob richtiges Format-Keine "Sechs-Stunden Zusammenfassung"
                && !isset( $time->getElementsByTagName('minTemperature')[0]) //Prüfen ob richtiges Format-Keine "Sechs-Stunden Zusammenfassung"
                && $timeTo <= $forecastEndTime
                && $timeTo >= $now - 3600 )
            {
                //Zusammenfügen der 2 relevanten Formate zu einem Datensatz.
                // Key ist der Timestamp "from"
                $id = $timeTo;

                $forecast[$id] ['timestamp']               = strtotime(str_replace(['T','Z'],' ',$time->getAttribute('to')));
                $hour                                      = (int) date("H", $forecast[$id] ['timestamp']);
                $forecast[$id] ['day']                     = date("D j.m.Y",$forecast[$id] ['timestamp']);
                $forecast[$id] ['daytime']                 = date("H:i",$forecast[$id] ['timestamp']);

                if ( isset( $time->getElementsByTagName('temperature')[0]) )//Format mit Temperatur und Wind
                {
                    $forecast[$id] ['cur_temp_c']              = (float) $time->getElementsByTagName('temperature')[0]->getAttribute('value');
                    $forecast[$id] ['cur_wind']                = 3.6*$time->getElementsByTagName('windSpeed')[0]->getAttribute('mps');
                    $forecast[$id] ['wind_kmh']                = round(3.6*$time->getElementsByTagName('windSpeed')[0]->getAttribute('mps'));
                    $forecast[$id] ['wind_mps']                = $time->getElementsByTagName('windSpeed')[0]->getAttribute('mps');
                    $forecast[$id] ['winddirection']           = $time->getElementsByTagName('windDirection')[0]->getAttribute('name');
                    $forecast[$id] ['cur_winddirection']       = round($time->getElementsByTagName('windDirection')[0]->getAttribute('deg'));
                    if ( $onlyCurrent )return $forecast;

                }
                elseif ( isset( $time->getElementsByTagName('precipitation')[0]) )//Format mit Regen und Bewölkung
                {
                    $forecast[$id] ['cur_precipitation']       = (float) $time->getElementsByTagName('precipitation')[0]->getAttribute('value');
                    $forecast[$id] ['cur_icon']                = $time->getElementsByTagName('symbol')[0]->getAttribute('number');
                    $forecast[$id] ['cur_cloudage']            = $time->getElementsByTagName('symbol')[0]->getAttribute('code');
                    $forecast[$id] ['id_condition']            = (int)$time->getElementsByTagName('symbol')[0]->getAttribute('number');

                    //Für das Mapping den Code der Bewölkung mit einer Nullstelle erweitern
                    if( $forecast[$id] ['id_condition'] < 10 )
                    {
                        $forecast[$id] ['id_condition'] = "0".$forecast[$id] ['id_condition'];
                    }
                    //Uhrzeit prüfen für Tag-Icons oder Nacht-Icons
                    //den Code der Bewölkung mit "d" oder "n" erweitern
                    if( $hour > 6 && $hour < 18 )
                    {
                        $forecast[$id] ['id_condition'] = $forecast[$id] ['id_condition']."d";
                    }
                    else
                    {
                        $forecast[$id] ['id_condition'] = $forecast[$id] ['id_condition']."n";
                    }
                }
            }

        }
        return $forecast;
    }
    private function prepareCurrentWeatherWithNewApiJson( $timeList )
    {
        $weatherData = false;
        if( $timeList  )
        {
            $current['cur_datetime']            = $timeList["time"];
            $current['cur_temp_c']              = $timeList["data"]["instant"]["details"]["air_temperature"];
            $current['cur_precipitation']       = $timeList["data"]["next_1_hours"]["details"]["precipitation_amount"];
            $current['cur_icon']                = $timeList["data"]["next_1_hours"]["summary"]["symbol_code"];
            $current['wind_kmh']                = 3.6*$timeList["data"]["instant"]["details"]["wind_speed"];
            $current['wind_mps']                = $timeList["data"]["instant"]["details"]["wind_speed"];
            $current['cur_winddirection']       = $timeList["data"]["instant"]["details"]["wind_from_direction"];
//            $current['winddirection']           = $timeList[0]->getElementsByTagName('windDirection')[0]->getAttribute('code');
            $current['cur_cloudage']            = $timeList["data"]["next_1_hours"]["summary"]["symbol_code"];
            $current['id_condition']            = $timeList["data"]["next_1_hours"]["summary"]["symbol_code"];
            //var_dump($current);
            $weatherData['current_conditions'] = $current;
        }
//        var_dump( $weatherData);die();
        return $weatherData;
    }
    function getContainer() {
        return $this->_container;
    }
    function apiRequest($apiMode, $apiUrl, $BSHWaterId = null, $container = false )
    {
        $this->_cacheFilename = "current.xml";
//        $content = parent::apiRequest( $location );
        if( $container )$this->_container = $container;
        $water_data      = $this->getWaterBSH( $BSHWaterId );
        //Datum wird benötigt um Wetter- und Wasserdaten in der Tabelle zusammenzuführen
        //Wasserdaten haben Kennzeichnung "h6" für "heute 6Uhr" oder "m12" für "morgen 12Uhr"
        $dates = [];
        $dates[ 'today' ] = mktime( 0, 0, 0 );
        $dates[ 'tommorow' ] = mktime( 23, 59, 59 );
        $water_data_timestamps = [];
        //Zeiten (timestamps) festlegen bis wann der Wert angezeigt wird
        //zum Beispiel wird ab 9:00 Uhr bis 15:00 Uhr der Messwert von 12:00 Uhr angezeigt
        $water_data_timestamps[ 'h6' ] = $dates[ 'today' ] + 9 * 3600;//heute 9:00 Uhr unsw.
        $water_data_timestamps[ 'h12' ] = $dates[ 'today' ] + 15 * 3600;
        $water_data_timestamps[ 'h18' ] = $dates[ 'today' ] + 21 * 3600;
        $water_data_timestamps[ 'h0' ] = $dates[ 'today' ] + 27 * 3600;//morgen 3:00 Uhr
        $water_data_timestamps[ 'm6' ] = $dates[ 'tommorow' ] + 9 * 3600;
        $water_data_timestamps[ 'm12' ] = $dates[ 'tommorow' ] + 15 * 3600;
        $water_data_timestamps[ 'm18' ] = $dates[ 'tommorow' ] + 21 * 3600;
        $water_data_timestamps[ 'm0' ] = $dates[ 'tommorow' ] + 27 * 3600;


        $current = $this->getWeather( $apiUrl, $apiMode = 2 );
//        return $current;

        $content = [
            "current_conditions"    => $current[ 'current_conditions' ],
            "forecast"              => $current [ 'forecast' ],
            "current_water"         => $water_data,
            "dates"                 => $dates,
            "water_data_timestamps" => $water_data_timestamps
        ];

        return $content;
    }

    function getCurrentAirTemp( $apiUrl, $container = false )
    {
        if( $container )$this->_container = $container;
        $weatherData = false;
        $weatherDataCurrent = false;
        $this->_apiUrl_2_0 = $apiUrl;
        $cachePath = $this->_cachePath;

        if( $this->getContainer()->hasParameter('CacheFileNameCurrentAir')) {
            $this->_cacheFileName = $this->getContainer()->getParameter('CacheFileNameCurrentAir');
            $cachePath = $this->_cacheDir.'/'.$this->_cacheFileName;
        }
        $xmlOrJson = $this->getFileContentWithCacheCheck(5, $this->_apiUrl_2_0, $cachePath );
        if ( strpos( $xmlOrJson, 'xmlns') > 0 ){

            $doc = $this->getDoc($xmlOrJson);
            if( $doc && isset( $doc) )
            {
                $timeList = $doc->getElementsByTagName('time');
                if( $timeList && isset( $timeList) )
                {
//                          $weatherData = $this->prepareCurrentWeatherWithNewApiXml($timeList);
                    $weatherData['forecast']  = $this->prepareForecastWithNewApiJson( $timeList ,true );
                    reset( $weatherData['forecast']);
                    $weatherDataCurrent['current_conditions'] = $weatherData['forecast'][key($weatherData['forecast'])];
//                    $weatherData['current_conditions'] = $weatherData['forecast'][key($weatherData['forecast'])];
                }
            }
        }
        else{
            $data = json_decode( $xmlOrJson ,true );
//                    return json_decode( $xmlOrJson ,true );
            if ($data["type"] == "Feature"){
                $data = json_decode( $xmlOrJson ,true );
                $weatherData['forecast'] = $this->prepareForecastWithNewApiJson($data["properties"]["timeseries"]);
                reset( $weatherData['forecast']);
                $weatherDataCurrent['current_conditions'] = $weatherData['forecast'][key($weatherData['forecast'])];
//                        $weatherData['current_conditions'] = $this->prepareCurrentWeatherWithNewApiJson($data["properties"]["timeseries"][0]);
            }
        }
//        return $weatherData;
        return $weatherDataCurrent;

    }
    protected function getFileContentWithCacheCheck ( $timeout, $url, $cachePath = false )
    {
        $useCache  = false;
        $status    = true;
        $data    = false;
        $cacheDir  = $this->_cacheDir;
        if ( $cachePath == false ) $cachePath = $this->_cachePath;
        $timeoutMinutes = $timeout;

        if ( (int) $timeout <= 0 or $timeout === false )$timeoutMinutes = $this->_timeOut;
        if ( !file_exists( $cacheDir ) )
        {
            if ( !mkdir( $cacheDir, 0777, true ) )
            {
//                var_dump('kein cachedir');
//                eZDebug::writeError( "Couldn't create cache directory $cacheDir, perhaps wrong permissions", "eZINI" );
                return false;
            }
        }

        if ( file_exists( $cachePath ) )
        {

            if ( filemtime( $cachePath ) + 60 * $timeoutMinutes >= time() )

            {
                $useCache = true;
            }
        }

        if ( ! $useCache )
//        if ( 1 )
        {
            if( $url == '' ){
                $url = 'https://www.ostseebad-wustrow.de/var/site/storage-extra/wetter/'.$this->_cacheFilename;
            }
            $data = utf8_encode( $this->file_get_contents_curl( $url ) );


            if ( strpos( $data, 'Werner Krenn') > 0 )
//            if ( substr( $data, 0, 5 ) == '<?xml' || strpos( $data, 'xmlns') > 0 )
            {

                if ( $data != false )
                {

                    file_put_contents( $cachePath , utf8_decode( $data ) );
                    return $data;
                }
            }
//            if ( strpos( $data, 'Werner Krenn') > 0 )
            elseif ( substr( $data, 0, 12 ) == '<weatherdata' || strpos( $data, 'xmlns') > 0 )
            {

                if ( $data != false )
                {

                    file_put_contents( $cachePath , utf8_decode( $data ) );
                    return $data;
                }
            }
            //For new API
            elseif( isset( json_decode( $data ,true )["type"] ) && json_decode( $data ,true )["type"] == 'Feature' )
            {
                file_put_contents( $cachePath , utf8_decode( $data ) );
            }
            // For BSH Water
            elseif( isset( json_decode( $data ,true )[$this->_bshWaterId] ) )
            {
                file_put_contents( $cachePath , utf8_decode( $data ) );
            }
        }
        else
        {
            $data = utf8_encode( file_get_contents ( $cachePath ) );
//            $data = utf8_encode( $this->file_get_contents_curl( $cachePath ) );
        }

        return $data;
    }

    function file_get_contents_curl( $url )
    {
//        $url = 'https://api.met.no/weatherapi/locationforecast/2.0/compact?lat=54.2875153&lon=12.2660585';
//        $url = 'https://api.met.no/weatherapi/locationforecast/2.0/classic?lat=54.2875153&lon=12.2660585';
        $data = false;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //Set curl to return the data instead of printing it to the browser.
        curl_setopt($ch, CURLOPT_URL, $url);
        $data = curl_exec($ch);


        curl_close($ch);
        if (strpos( $data,'Not found'))
        {

            return false;
        }

        return $data;
    }
//
//    private function getIdCondition ( $id = null, $service = null )
//    {
//        $new_id = false;
//
//        $wwo_arr = array(
//            0  => array ( 113 ),
//            1  => array ( 116 ),
//            2  => null,
//            3  => array ( 122 ),
//            4  => array ( 248 ),
//            8  => array ( 176, 293, 299 ),
//            6  => array ( 296, 302 ),
//            65 => array ( 308, 359 ),
//            9  => array ( 389, 200, 386, 392, 395 ),
//            73 => array ( 179, 323, 329, 368 ),
//            75 => array ( 227, 335, 371 ),
//            7  => array ( 326, 332, 338 ),
//            69 => array ( 182, 362, 365 ),
//            68 => array ( 317, 320 ),
//            2  => array ( 119 ),
//            48 => array ( 260 ),
//            53 => array ( 263, 266, 305, 356 ),
//            57 => array ( 185, 281, 311, 314, 284 ),
//            4  => array ( 143 )
//        );
//
//        if ( $service == 'WWO')
//        {
//            foreach ( $wwo_arr as $key => $item )
//            {
//                if ( array_search( $id, $item) !== false )
//                {
//                    $new_id = $key;
//                    break;
//                }
//            }
//        }
//        else
//            $new_id = $id;
//
//        return (string) $new_id;
//    }

// extract the Date from the current Weahter html file from Wustrow
// Date is needed to ensure the Weather file is up to date
    public function extractDate( $html, $string, $charCount )
    {
        $pos = strpos ( $html , $string );
        $startPos = $pos + strlen( $string);
        $date = substr ( $html , $startPos, $charCount ) ;

        return $date;

    }

    /**
     * Extract a Textsnippet from html-table cells at first part with limitation $first_string on rows and in second part
     * extract string after $second_string
     *
     * if $second string contains <star> then the string left til begin and the string right til the end will be deleted
     *
     * @param $html
     * @param $first_string
     * @param $second_string
     * @return bool|mixed
     */
    public function extract_partical_text ( $html, $first_string, $second_string )
    {
        $dom = new DOMDocument();
        @$dom->loadHTML( utf8_decode( $html ) );

        if ( strstr( $second_string, '*' ))
            $second_string = explode( "*", $second_string);
        else
            $second_string = array ( $second_string );

        foreach($dom->getElementsByTagName('tr') as $tr)
        {
            if (strstr($tr->textContent, $first_string))
            {
                foreach ($tr->getElementsByTagName('td') as $td)
                {
                    if (strstr($td->textContent, $second_string[0]))
                    {
                        $result = preg_replace('/^.?' . $second_string[0] . '/', '', $td->textContent);
                        if (count($second_string) == 2)
                            return preg_replace('/' . $second_string[1] . '.*$/', '', $result);
                    }
                }
            }
        }

        return false;
    }
    function getSymbolCodes () {
        return json_decode ('
            {"Error" : 0,
            "Sun" : 1,
            "LightCloud" : 2,
            "PartlyCloud" : 3,
            "Cloud" : 4,
            "LightRainSun" : 5,
            "LightRainThunderSun" : 6,
            "SleetSun" : 7,
            "SnowSun" : 8,
            "LightRain" : 9,
            "Rain" : 10,
            "RainThunder" : 11,
            "Sleet" : 12,
            "Snow" : 13,
            "SnowThunder" : 14,
            "Fog" : 15,
            "SleetSunThunder" : 20,
            "SnowSunThunder" : 21,
            "LightRainThunder" : 22,
            "SleetThunder" : 23,
            "DrizzleThunderSun" : 24,
            "RainThunderSun" : 25,
            "LightSleetThunderSun" : 26,
            "HeavySleetThunderSun" : 27,
            "LightSnowThunderSun" : 28,
            "HeavySnowThunderSun" : 29,
            "DrizzleThunder" : 30,
            "LightSleetThunder" : 31,
            "HeavySleetThunder" : 32,
            "LightSnowThunder" : 33,
            "HeavySnowThunder" : 34,
            "DrizzleSun" : 40,
            "RainSun" : 41,
            "LightSleetSun" : 42,
            "HeavySleetSun" : 43,
            "LightSnowSun" : 44,
            "HeavySnowSun" : 45,
            "Drizzle" : 46,
            "LightSleet" : 47,
            "HeavySleet" : 48,
            "LightSnow" : 49,
            "HeavySnow" : 50,
            "Dark_Sun" : 101,
            "Dark_LightCloud" : 102,
            "Dark_PartlyCloud" : 103,
            "Dark_LightRainSun" : 105,
            "Dark_LightRainThunderSun" : 106,
            "Dark_SleetSun" : 107,
            "Dark_SnowSun" : 108,
            "Dark_SleetSunThunder" : 120,
            "Dark_SnowSunThunder" : 121,
            "Dark_DrizzleThunderSun" : 124,
            "Dark_RainThunderSun" : 125,
            "Dark_LightSleetThunderSun" : 126,
            "Dark_HeavySleetThunderSun" : 127,
            "Dark_LightSnowThunderSun" : 128,
            "Dark_HeavySnowThunderSun" : 129,
            "Dark_DrizzleSun" : 140,
            "Dark_RainSun" : 141,
            "Dark_LightSleetSun" : 142,
            "Dark_HeavySleetSun" : 143,
            "Dark_LightSnowSun" : 144,
            "Dark_HeavySnowSun" : 145}',true, 2);
    }

    function createSymbolsArrayCodeAsKey()
    {
//        return $this->getSymbolCodes();
        $symbolsArray = [];
        $symbolCodeObject = $this->getSymbolCodes();
//        foreach ( $this->getSymbolCodes() as $symbolCodeObject ) {
        foreach ( $symbolCodeObject as $symbolCode => $key ) {
            $symbolsArray[$key] = $symbolCode;
        }
//        }
        return $symbolsArray;
    }
    function mapIconsToSymbolIds (){
        $rasterHeight = 30;
        $rasterWidth = 40;
        $unit = "px";

            $SymbolDescription = json_decode('{
                "0": "Error",
                "1": "-3,-2,Sun",
                "2": "-6,-4,LightCloud",
                "3": "-240px,-90px,PartlyCloud",
                "4": "-240px,-60px,Cloud",
                "5": "-5,-7,LightRainSun",
                "6": "-2,-1,LightRainThunderSun",
                "7": "-3,-7,SleetSun",
                "8": "SnowSun",
                "9": "LightRain",
                "10": "-4,-13,Rain",
                "11": "RainThunder",
                "12": "-3,-6,Sleet",
                "13": "-2,-8,Snow",
                "14": "0,-6,SnowThunder",
                "15": "Fog",
                "20": "SleetSunThunder",
                "21": "SnowSunThunder",
                "22": "LightRainThunder",
                "23": "SleetThunder",
                "24": "DrizzleThunderSun",
                "25": "RainThunderSun",
                "26": "LightSleetThunderSun",
                "27": "HeavySleetThunderSun",
                "28": "LightSnowThunderSun",
                "29": "HeavySnowThunderSun",
                "30": "DrizzleThunder",
                "31": "LightSleetThunder",
                "32": "HeavySleetThunder",
                "33": "LightSnowThunder",
                "34": "HeavySnowThunder",
                "40": "DrizzleSun",
                "41": "-5,-7,RainSun",
                "42": "-3,-8,LightSleetSun",
                "43": "HeavySleetSun",
                "44": "-2,-13,LightSnowSun",
                "45": "HeavySnowSun",
                "46": "DrizzleNiesel",
                "47": "LightSleet",
                "48": "HeavySleet",
                "49": "LightSnow",
                "50": "HeavySnow",
                "101": "Dark_Sun",
                "102": "Dark_LightCloud",
                "103": "Dark_PartlyCloud",
                "105": "Dark_LightRainSun",
                "106": "Dark_LightRainThunderSun",
                "107": "Dark_SleetSun",
                "108": "Dark_SnowSun",
                "120": "Dark_SleetSunThunder",
                "121": "Dark_SnowSunThunder",
                "124": "Dark_DrizzleThunderSun",
                "125": "Dark_RainThunderSun",
                "126": "Dark_LightSleetThunderSun",
                "127": "Dark_HeavySleetThunderSun",
                "128": "Dark_LightSnowThunderSun",
                "129": "Dark_HeavySnowThunderSun",
                "140": "Dark_DrizzleSun",
                "141": "Dark_RainSun",
                "142": "Dark_LightSleetSun",
                "143": "Dark_HeavySleetSun",
                "144": "Dark_LightSnowSun",
                "145": "Dark_HeavySnowSun"
            }', true);

    }
    function getSymbolcodeTranslations ( $key )
    {
        return $this->symbolCodeTranslations[ $key ];
    }

    function setSymbolcodeTranslations ( ){
        $this->symbolCodeTranslations = json_decode(
    '{
            "clearsky":{
            "ger-DE":"klarer Himmel",
            "eng-GB":"clear sky"
            },
            "cloudy":{
            "ger-DE":"bewölkter Himmel",
            "eng-GB":"cloudy sky"
            },
            "fair":{
            "ger-DE":"heiterer Himmel",
            "eng-GB":"fair sky"
            },
            "fog":{
            "ger-DE":"Nebel",
            "eng-GB":"fog"
            },
            "heavyrain":{
            "ger-DE":"starker Regen",
            "eng-GB":"heavy rain"
            },
            "heavyrainandthunder":{
            "ger-DE":"starker Regen und Gewitter",
            "eng-GB":"heavy rain and thunder"
            },
            "heavyrainshowers":{
            "ger-DE":"heftige Regenschauer",
            "eng-GB":"heavy rain showers"
            },
            "heavyrainshowersandthunder":{
            "ger-DE":"starke Regenschauer und Gewitter",
            "eng-GB":"heavy rain showers and thunderstorms"
            },
            "heavysleet":{
            "ger-DE":"heftiger Graupel",
            "eng-GB":"heavy sleet"
            },
            "heavysleetandthunder":{
            "ger-DE":"starke Graupel und Gewitter",
            "eng-GB":"heavy sleet and thunderstorms"
            },
            "heavysleetshowers":{
            "ger-DE":"heftige Graupelschauer",
            "eng-GB":"heavy sleet showers"
            },
            "heavysleetshowersandthunder":{
            "ger-DE":"heftige Graupelschauer und Gewitter",
            "eng-GB":"heavy sleet showers and thunderstorms"
            },
            "heavysnow":{
            "ger-DE":"starker Schnee",
            "eng-GB":"heavy snow"
            },
            "heavysnowandthunder":{
            "ger-DE":"starker Schnee und Gewitter",
            "eng-GB":"heavy snow and thunderstorms"
            },
            "heavysnowshowers":{
            "ger-DE":"heftige Schneeschauer",
            "eng-GB":"heavy snow showers"
            },
            "heavysnowshowersandthunder":{
            "ger-DE":"heftige Schneeschauer und Gewitter",
            "eng-GB":"heavy snow showers and thunderstorms"
            },
            "lightrain":{
            "ger-DE":"leichter Regen",
            "eng-GB":"light rain"
            },
            "lightrainandthunder":{
            "ger-DE":"leichter Regen und Gewitter",
            "eng-GB":"light rain and thunderstorms"
            },
            "lightrainshowers":{
            "ger-DE":"leichter Regenschauer",
            "eng-GB":"light rain shower"
            },
            "lightrainshowersandthunder":{
            "ger-DE":"leichter Regenschauer und Gewitter",
            "eng-GB":"light rain and thunderstorm"
            },
            "lightsleet":{
            "ger-DE":"leichter Graupel",
            "eng-GB":"light sleet"
            },
            "lightsleetandthunder":{
            "ger-DE":"leichter Graupel und Gewitter",
            "eng-GB":"light sleet and thunderstorm"
            },
            "lightsleetshowers":{
            "ger-DE":"leichter Graupelschauer",
            "eng-GB":"light sleet shower"
            },
            "lightsnow":{
            "ger-DE":"leichter Schnee",
            "eng-GB":"light snow"
            },
            "lightsnowandthunder":{
            "ger-DE":"leichter Schnee und Gewitter",
            "eng-GB":"light snow and thunderstorm"
            },
            "lightsnowshowers":{
            "ger-DE":"leichter Schneeschauer und Gewitter",
            "eng-GB":"light snow shower and thunderstorm"
            },
            "lightssleetshowersandthunder":{
            "ger-DE":"leichter Graupelschauer und Gewitter",
            "eng-GB":"light sleet shower and thunderstorm"
            },
            "lightssnowshowersandthunder":{
            "ger-DE":"leichter Schneeschauer und Gewitter",
            "eng-GB":"light snow shower and thunderstorm"
            },
            "partlycloudy":{
            "ger-DE":"teilweise bewölkter Himmel",
            "eng-GB":"partly cloudy sky"
            },
            "rain":{
            "ger-DE":"Regen",
            "eng-GB":"rain"
            },
            "rainandthunder":{
            "ger-DE":"Regen und Gewitter",
            "eng-GB":"rain and thunderstorm"
            },
            "rainshowers":{
            "ger-DE":"Regenschauer",
            "eng-GB":"Rain shower"
            },
            "rainshowersandthunder":{
            "ger-DE":"Regenschauer und Gewitter",
            "eng-GB":"Rain shower and thunderstorm"
            },
            "sleet":{
            "ger-DE":"Graupel",
            "eng-GB":"Sleet"
            },
            "sleetandthunder":{
            "ger-DE":"Graupel und Gewitter",
            "eng-GB":"Sleet and thunderstorms"
            },
            "sleetshowers":{
            "ger-DE":"Graupelschauer",
            "eng-GB":"Sleet showers"
            },
            "sleetshowersandthunder":{
            "ger-DE":"Graupelschauer und Gewitter",
            "eng-GB":"Sleet showers and thunderstorms"
            },
            "snow":{
            "ger-DE":"Schnee",
            "eng-GB":"Snow"
            },
            "snowandthunder":{
            "ger-DE":"Schnee und Gewitter",
            "eng-GB":"Snow and thunderstorm"
            },
            "snowshowers":{
            "ger-DE":"Schneeschauer",
            "eng-GB":"Snow shower"
            },
            "snowshowersandthunder":{
            "ger-DE":"Schneeschauer und Gewitter",
            "eng-GB":"Snow showers and thunderstorms"
            }
        }', true);

    }

}
?>
