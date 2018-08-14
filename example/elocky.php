<?php

require '../src/User.class.php';
include 'credential.php';

use ElockyAPI\User as User;

function printRequestResult($_data) {
    print($_data . PHP_EOL);
}

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

$f = $_SERVER['HOME'] . '/tmp/elocky_auth.txt';

// Try with wrong id
try {
    $api = new User('', '');
} catch (Exception $e) {
    print('ERROR: ' . $e->getMessage() . PHP_EOL);
} 

// Anonymous user
$api = new User(CLIENT_ID, CLIENT_SECRET);

//Authenticated user
$api = new User(CLIENT_ID, CLIENT_SECRET, USERNAME, PASSWORD);
        
if (file_exists($f)) {
    $authData = json_decode(file_get_contents($f), TRUE);
    $api->setAuthenticationData($authData);
}

print('expiry token date:' . $api->getTokenExpiryDate()->format('Y-m-d H:i:s') . PHP_EOL);

$userProfile = $api->requestUserProfile();
print('User profile:' . PHP_EOL . json_encode($userProfile, JSON_PRETTY_PRINT) . PHP_EOL);

print('Places:' . PHP_EOL . json_encode($api->requestPlaces(), JSON_PRETTY_PRINT) . PHP_EOL);

print('Accesses:' . PHP_EOL . json_encode($api->requestAccesses(), JSON_PRETTY_PRINT) . PHP_EOL);

print('Guests:' . PHP_EOL . json_encode($api->requestGuests(), JSON_PRETTY_PRINT) . PHP_EOL);

print('Objects:' . PHP_EOL . json_encode($api->requestObjects(), JSON_PRETTY_PRINT) . PHP_EOL);

file_put_contents($f, $api->getAuthenticationData());
