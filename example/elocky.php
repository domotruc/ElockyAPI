<?php

require '../vendor/autoload.php';
include 'credential.php';

use ElockyAPI\User as User;
use Psr\Log\AbstractLogger;

class Logger extends AbstractLogger {
    public function log($level, $message, array $context = array()) {
        print('ElockyAPI:' . $level . ':' . $message . PHP_EOL);
    }
}

// Try with wrong id
try {
    new User('', '');
} catch (Exception $e) {
    print('ERROR: ' . $e->getMessage() . PHP_EOL);
} 

// Anonymous user
//$api = new User(CLIENT_ID, CLIENT_SECRET);

// Authenticated user
$api = new User(CLIENT_ID, CLIENT_SECRET, USERNAME, PASSWORD, new Logger());

// Restore token data
$token_filename = DATA_DIR . '/elocky_auth.txt';
if (file_exists($token_filename)) {
    $authData = json_decode(file_get_contents($token_filename), TRUE);
    $api->setAuthenticationData($authData);
    print('expiry token date:' . $api->getTokenExpiryDate()->format('Y-m-d H:i:s') . PHP_EOL);
}

// User profile retrieval
$userProfile = $api->requestUserProfile();
print('User profile:' . PHP_EOL . json_encode($userProfile, JSON_PRETTY_PRINT) . PHP_EOL);

// User photo retrieval
print('User photo saved to: ' . DATA_DIR . '/' . $userProfile['photo'] . PHP_EOL);
$api->requestUserPhoto($userProfile['photo'], DATA_DIR);

// Places retrieval
$places = $api->requestPlaces();
print('Places:' . PHP_EOL . json_encode($places, JSON_PRETTY_PRINT) . PHP_EOL);

// Place photo retrieval
print('Place photo of "' . $places['lieux'][0]['address'] . '" saved to: ' . DATA_DIR . '/' . $places['lieux'][0]['photo'] . PHP_EOL);
$api->requestPlacePhoto($places['lieux'][0]['photo'], DATA_DIR);

// Access retrieval
print('Accesses:' . PHP_EOL . json_encode($api->requestAccesses(), JSON_PRETTY_PRINT) . PHP_EOL);

// Guests retrieval
print('Guests:' . PHP_EOL . json_encode($api->requestGuests(), JSON_PRETTY_PRINT) . PHP_EOL);

// Objects retrieval
print('Objects of "' . $places['lieux'][0]['address'] . '":' . PHP_EOL . json_encode($api->requestObjects($userProfile['reference'], $places['lieux'][0]['id']), JSON_PRETTY_PRINT) . PHP_EOL);

// History retrieval
print('History of "' . $places['lieux'][0]['address'] . '":' . PHP_EOL . json_encode($api->requestHistory($places['lieux'][0]['id'], 1), JSON_PRETTY_PRINT) . PHP_EOL);

// Try to open a place
//print('Open ' . $places['lieux'][0]['address'] . ':' . PHP_EOL . json_encode($api->requestOpening($places['lieux'][0]['board'][0]['id']), JSON_PRETTY_PRINT) . PHP_EOL);

// Save token data
file_put_contents($token_filename, json_encode($api->getAuthenticationData()));
