<?php
function debug($var)
{
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
                "url" => "https://api.deutschebahn.com/timetables/v1/",
                "return" => "xml"
            ),
            "fahrplan-plus" => array(
                "url" => "https://api.deutschebahn.com/fahrplan-plus/v1/",
                "return" => "json"
            ));
    }

    public function convertDBTimeStringToTime($db_time)
    {
        $unix_time = strtotime($db_time);
        return $unix_time;
    }

    public function getStation($name)
    {
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

    public function getStations($name)
    {
        // Create request
        $request = 'location/' . rawurlencode($name);

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

    public function loadData($api, $request)
    {
        // Init cURL
        $ch = curl_init();

        // Set opts
        curl_setopt($ch, CURLOPT_URL, $this->apis[$api]['url'] . $request);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        // Set headers
        $headers = array();
        $headers[] = "Authorization: Bearer " . $this->token;
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
        curl_close($ch);

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

    public function getTimetable($station_id, $time = 0)
    {
        $states = array(
            'a' => 'added',
            'c' => 'canceled',
            'p' => 'planned'
        );

        if ($time == 0) {
            $time = time();
        }

        // Convert times
        $db_time = $this->convertTimeToDBTimeString($time);
        $date = $db_time['date'];
        $hour = $db_time['hour'];

        // Normal plan
        $plan_request = 'plan/' . $station_id . '/' . $date . '/' . $hour;
        echo $plan_request;
        $plan = $this->loadData('timetables', $plan_request);

        // Changes
        $changes_request = 'fchg/' . $station_id;
        $changes = $this->loadData('timetables', $changes_request);

        // Remove attribute object with station name
        unset($plan['@attributes']);
        unset($changes['@attributes']);

        $changes = $changes['s'];

        $resorted_changes = [];

        foreach ($changes as $change) {
            $change_id = $change['@attributes']['id'];
            unset($change['@attributes']);
            $resorted_changes[$change_id] = $change;
        }
        //debug($resorted_changes);


        // Resort plan (and reformat)
        $resorted_plan = [];

        foreach ($plan['s'] as $trip) {

            $id = $trip['@attributes']['id'];
            unset($trip['@attributes']);

            if (isset($resorted_changes[$id])) {
                $is_changed = true;
                $changed_trip = $resorted_changes[$id];
            } else {
                $is_changed = false;
            }

            // Get information about train
            $trip['train']['type'] = $trip['tl']['@attributes']['t'];
            $trip['train']['owner'] = $trip['tl']['@attributes']['o'];
            $trip['train']['class'] = $trip['tl']['@attributes']['c']; // Example: IC, EC, ICE, RE, RB, IRE
            $trip['train']['number'] = $trip['tl']['@attributes']['n']; // Example: 2073 2003 etc.
            unset($trip['tl']);

            if (isset($trip['ar'])) {
                // Arrival
                $type_obj_ar = $trip['ar']['@attributes'];

                $trip['arrival']['planned']['time'] = $type_obj_ar['pt'];
                $trip['arrival']['planned']['platform'] = $type_obj_ar['pp'];
                $trip['arrival']['planned']['route'] = explode('|', $type_obj_ar['ppth']);

                if (isset($type_obj_ar['ps'])) {
                    $trip['arrival']['planned']['status'] = $states[$type_obj_ar['ps']];
                }

                unset($trip['ar']);
            }

            if ($is_changed) {
                if (isset($changed_trip['ar']['@attributes'])) {
                    $type_obj_ar = $changed_trip['ar']['@attributes'];

                    if (isset($type_obj_ar['ct'])) {
                        $trip['arrival']['changed']['time'] = $type_obj_ar['ct'];
                    }

                    if (isset($type_obj_ar['cp'])) {
                        $trip['arrival']['changed']['platform'] = $type_obj_ar['cp'];
                    }

                    if (isset($type_obj_ar['cpth'])) {
                        $trip['arrival']['changed']['route'] = explode('|', $type_obj_ar['cpth']);
                    }

                    if (isset($type_obj_ar['cs'])) {
                        $trip['arrival']['changed']['status'] = $states[$type_obj_ar['cs']];
                    }
                }
            }

            if (isset($trip['dp'])) {
                // Departure
                $type_obj_dp = $trip['dp']['@attributes'];

                $trip['departure']['planned']['time'] = $type_obj_dp['pt'];
                $trip['departure']['planned']['platform'] = $type_obj_dp['pp'];
                $trip['departure']['planned']['route'] = explode('|', $type_obj_dp['ppth']);
                if (isset($type_obj_dp['ps'])) {
                    $trip['departure']['planned']['status'] = $states[$type_obj_dp['ps']];
                }

                unset($trip['dp']);
            }

            if ($is_changed) {
                if (isset($changed_trip['dp']['@attributes'])) {
                    $type_obj_dp = $changed_trip['dp']['@attributes'];

                    if (isset($type_obj_dp['ct'])) {
                        $trip['departure']['changed']['time'] = $type_obj_dp['ct'];
                    }

                    if (isset($type_obj_dp['cp'])) {
                        $trip['departure']['changed']['platform'] = $type_obj_dp['cp'];
                    }

                    if (isset($type_obj_dp['cpth'])) {
                        $trip['departure']['changed']['route'] = explode('|', $type_obj_dp['cpth']);
                    }

                    if (isset($type_obj_dp['cs'])) {
                        $trip['departure']['changed']['status'] = $states[$type_obj_dp['cs']];
                    }
                }
            }


            // Line > Example: IC2073
            if (isset($type_obj_dp['l'])) {
                $trip['line'] = $type_obj_dp['l'];
            } elseif (isset($type_obj_ar['l'])) {
                $trip['line'] = $type_obj_ar['l'];
            } else {
                $trip['line'] = $trip['train']['class'] . $trip['train']['number'];
            }

            unset($type_obj_ar);
            unset($type_obj_dp);

            $resorted_plan[$id] = $trip;
        }


        return $resorted_plan;
    }

    public function convertTimeToDBTimeString($unix_time)
    {
        $result = [];
        $result['date'] = date('ymd', $unix_time);
        $result['hour'] = date('H', $unix_time);
        $result['min'] = date('i', $unix_time);
        return $result;
    }
}


//curl -X GET --header "Accept: application/xml" --header "Authorization: Bearer 7e75089d7fc7f1076621bdc63f2b66f4" "https://api.deutschebahn.com/timetables/v1/station/AL"

//echo "Start";
//$db = new DB('7e75089d7fc7f1076621bdc63f2b66f4');
//$station = $db->getStation('Hamburg Hbf');
//$station_id = $station['id'];
//echo "<br>";
//echo $station_id;
//echo "<br>";
//$timetable = $db->getTimetable($station_id);
//debug($timetable);
