<?php

include '../src/ElockyAPI.class.php';

# User management
#################

function getUserProfile($access_token) {
    $data = curlExec("https://www.elocky.com/webservice/user/.json", 'access_token=' . $access_token);
    printJson($data);
    return $data;
}

# Places management
###################


function getAddresses($access_token) {
    $data = curlExec("https://www.elocky.com/webservice/address/list.json", 'access_token=' . $access_token);
    printJson($data);
    return $data;
}

function getLog($access_token, $id) {
    $start = 1;
    $data = curlExec('https://www.elocky.com/webservice/address/log/' . $id . '/' . $start . '.json', 'access_token=' . $access_token);
    printJson($data);
    return $data;
}


//$api = new ElockyAPI("3_15riy2fqm0w00w0w44owc0ooo8w4c0kkww00ksskc8sskso8gw", "apijowrdlhk4cgs0cw0so4wcgscc44wksg40w84ko00woswkk");
//$api = new ElockyAPI("3_15riy2fqm0w00w0w44owc0ooo8w4c0kkww00ksskc8sskso8gw", "apijowrdlhk4cgs0cw0so4wcgscc44wksg40w84ko00woswkk", 'stephane.castejon@gmail.com', '_ossau_');

$api = new ElockyAPI('OTJiOGM1NmRkZDFmMDVhYTFlNTY5ODVlY2NiMmVlNjA4YzQ5MzIyZDBkMGJlMzBkZGVhYjg5NTI4NDQyZWY4OQ');

print('token:' . $api->getToken() . PHP_EOL);

//getUserProfile($access_token);
//getGuests($access_token);
//getAddresses($access_token);
//getLog($access_token, 449);

//print(json_encode($api->getCountries(), JSON_PRETTY_PRINT));

print('Places:' . PHP_EOL . json_encode($api->getPlaces(), JSON_PRETTY_PRINT) . PHP_EOL);

print('Accesses:' . PHP_EOL . json_encode($api->getAccesses(), JSON_PRETTY_PRINT) . PHP_EOL);

print('Guests:' . PHP_EOL . json_encode($api->getGuests(), JSON_PRETTY_PRINT) . PHP_EOL);

