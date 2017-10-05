<?php
function debug ($var) {
    echo '<pre>';
    print_r($var);
    echo '</pre>';
}

class DB
{   
    public function __construct($token)
    {
        $this->token = $token;

        $this->apis = array(
            "timetables" => array(
                "url"=> "https://api.deutschebahn.com/timetables/v1/",
                "return" => "xml"
            ),
            "fahrplan-plus" => array(
                "url" => "https://api.deutschebahn.com/fahrplan-plus/v1/",
                "return" => "json"
            ));
    }
    
    public function convertTimeToDBTimeString($unix_time)
    {   
        $result = [];
        $result['date'] = date('ymd', $unix_time);
        $result['hour'] = date('H', $unix_time);
        $result['min'] = date('i', $unix_time);
        return $result;
    }
    
    public function convertDBTimeStringToTime($db_time)
    {
        $unix_time = strtotime($db_time);
        return $unix_time;
    }
    
    public function loadData($api, $request)
    {
        // Init cURL
        $ch = curl_init();

        // Set opts
        curl_setopt($ch, CURLOPT_URL, $this->apis[$api]['url'].$request);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        // Set headers
        $headers = array();
        $headers[] = "Authorization: Bearer ".$this->token;
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Execute
        $result = curl_exec($ch);

        // Check errors
        if (curl_errno($ch)) {
            echo 'Fehler';
            trigger_error('Fehler:' . curl_error($ch));
            return false;
        }

        // Close
        curl_close ($ch);
        
        if ($this->apis[$api]['return'] == "xml") {
            // XML-Api
            $xml = simplexml_load_string($result, "SimpleXMLElement", LIBXML_NOCDATA);
            $json = json_encode($xml);
            $array = json_decode($json, true);
        } elseif ($this->apis[$api]['return'] == "json") {
            // JSON-Api
            $array = json_decode($result, true);
        }
        
            
        return $array;
    }

    public function getStations ($name) {
        // Create request
        $request = 'location/'.rawurlencode($name);
        
        // Execute request
        $result = $this->loadData('fahrplan-plus', $request);

        // // Format stations
        // $stations = [];
        // foreach ($result as $station) {
        //     $id = $station['id'];
        //     unset($station['id']);
        //     $stations[$id] = $station;
        // }

        return $result;
    }

    public function getStation ($name) {
        // Get stations
        $stations = $this->getStations($name);

        if (count($stations) > 0) {
            // Found a station, get the first
            return reset($stations);
        } else {
            // No station found
            return False;
        }
    }

    public function getTimetable ($station_id, $time = 0) {
        if ($time == 0) {
            $time = time();
        }

        // Convert times
        $db_time = $this->convertTimeToDBTimeString($time);
        $date = $db_time['date'];
        $hour = $db_time['hour'];

        $plan_request = 'plan/'.$station_id.'/'.$date.'/'.$hour;
        echo $plan_request;
        $plan = $this->loadData('timetables', $plan_request);

        // Remove attribute object with station name
        unset($plan['@attributes']);

        // Resort plan (and reformat)
        $resorted_plan = [];

        foreach ($plan['s'] as $trip) {
            $id = $trip['@attributes']['id'];
            unset($trip['@attributes']);

            $trip['train']['typ'] = $trip['tl']['@attributes']['t'];
            $trip['train']['owner'] = $trip['tl']['@attributes']['o'];
            $trip['train']['klasse'] = $trip['tl']['@attributes']['c'];
            $trip['train']['nummer'] = $trip['tl']['@attributes']['n'];

            unset($trip['tl']);

            $resorted_plan[$id] = $trip;
        }

        return $resorted_plan;
    }
}


//curl -X GET --header "Accept: application/xml" --header "Authorization: Bearer 7e75089d7fc7f1076621bdc63f2b66f4" "https://api.deutschebahn.com/timetables/v1/station/AL"

echo "Start";
$db = new DB('7e75089d7fc7f1076621bdc63f2b66f4');
$station = $db->getStation('Hamburg Hbf');
$station_id = $station['id'];

$timetable = $db->getTimetable($station_id);
debug($timetable);
