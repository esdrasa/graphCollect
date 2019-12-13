<?php
ini_set('memory_limit', '4096M');

function multiRequest($data, $options = array())
{
    $curly = array();
    $result = array();
    $mh = curl_multi_init();

    foreach ($data as $id => $d) {

        $curly[$id] = curl_init();

        $url = (is_array($d) && !empty($d['url'])) ? $d['url'] : $d;
        curl_setopt($curly[$id], CURLOPT_URL, $url);
        curl_setopt($curly[$id], CURLOPT_HEADER, 0);
        curl_setopt($curly[$id], CURLOPT_RETURNTRANSFER, 1);

        if (is_array($d)) {
            if (!empty($d['post'])) {
                curl_setopt($curly[$id], CURLOPT_POST, 1);
                curl_setopt($curly[$id], CURLOPT_POSTFIELDS, $d['post']);
            }
        }

        if (!empty($options)) {
            curl_setopt_array($curly[$id], $options);
        }

        curl_multi_add_handle($mh, $curly[$id]);
    }

    $running = null;
    do {
        curl_multi_exec($mh, $running);
    } while ($running > 0);

    foreach ($curly as $id => $c) {
        $result[$id] = json_decode(curl_multi_getcontent($c), true);
        curl_multi_remove_handle($mh, $c);
    }

    curl_multi_close($mh);

    return $result;
}

function dailyRequests($p_date, $p_hourOfDay = array())
{
    $stringRequests = array();
    for ($i = 0; $i < sizeof($p_hourOfDay) - 1; $i++) {
        $stringRequests[$i] = "https://www.dcc.ufrrj.br/ocupationdb/api.php?period_from=" . $p_date . "%20" . $p_hourOfDay[$i] . "&period_to=" . $p_date . "%20" . $p_hourOfDay[$i + 1];
    }
    return $stringRequests;
}

function getDailyUsers($p_date, $p_hoursOfDay)
{
    $requestsOfADay = multiRequest(dailyRequests($p_date, $p_hoursOfDay));
    $macCollect = array();
    $graphCollect = array();
    for ($i = 0; $i < sizeof($requestsOfADay); $i++) {
        for ($j = 0; $j < sizeof($requestsOfADay[$i]); $j++) {
            if (!isset($graphCollect[$i . "h-" . ($i + 1) . "h"][$requestsOfADay[$i][$j]["device_id"]])) {
                $graphCollect[$i . "h-" . ($i + 1) . "h"][$requestsOfADay[$i][$j]["device_id"]] = 0;
            } elseif (!in_array($requestsOfADay[$i][$j]["mac"], $macCollect)) {
                $graphCollect[$i . "h-" . ($i + 1) . "h"][$requestsOfADay[$i][$j]["device_id"]] += 1;
            }
            if (!in_array($requestsOfADay[$i][$j]["mac"], $macCollect)) {
                $macCollect[] = $requestsOfADay[$i][$j]["mac"];
            }
        }
    }
    return $graphCollect;
    // return array("macCollect" => $macCollect, "graphCollect" => $graphCollect)
}

function getWeeklyUsers($finalDate, $p_hoursOfDay)
{
    $graphCollect = array();
    $initialDate = date("Y-m-d", strtotime($finalDate . " -7 days"));
    for ($i = 0; $i < 7; $i++) {
        echo ($i + 1) . "/7\n";
        $loopDate = date("Y-m-d", strtotime($initialDate . " +" . ($i + 1) . " days"));
        // $rawDataJson = multiRequest(dailyRequests($loopDate, $p_hoursOfDay));
        $arrayDaily = getDailyUsers($loopDate, $p_hoursOfDay);
        foreach ($arrayDaily as $hourInterval => $campusBlocks) {
            foreach ($campusBlocks as $block => $totalUsers) {
                $graphCollect[$loopDate][$block] += $totalUsers;
            }
        }
    }
    return $graphCollect;
}

$hoursOfDay = [
    "00:00:00",
    "01:00:00",
    "02:00:00",
    "03:00:00",
    "04:00:00",
    "05:00:00",
    "06:00:00",
    "07:00:00",
    "08:00:00",
    "08:00:00",
    "10:00:00",
    "11:00:00",
    "12:00:00",
    "13:00:00",
    "14:00:00",
    "15:00:00",
    "16:00:00",
    "17:00:00",
    "18:00:00",
    "19:00:00",
    "20:00:00",
    "21:00:00",
    "22:00:00",
    "23:00:00",
    "23:59:59",
];

$macBlacklist = [
    "1",
    "10",
    "11",
    "12",
    "13",
    "14",
    "16",
    "17",
    "199",
    "21",
    "28",
    "3",
    "31",
    "32",
    "36",
    "37",
    "38",
    "40",
    "41",
    "43",
    "47",
    "5",
    "50",
    "6",
    "63",
    "7",
    "8",
    "9",
];

$date = "2019-11-29";
// print_r(dailyRequests($date, $hoursOfDay));
print_r(getWeeklyUsers($date, $hoursOfDay));
// print_r(getDailyUsers($date, $hoursOfDay));
