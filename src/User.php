<?php

namespace ElockyAPI;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Implement the Elocky API.
 * The User class is either an anonymous user or an authenticated user depending on the credential parameters 
 * at object creation.
 * @see https://elocky.com/fr/doc-api-test Elocky API
 * @author domotruc
 *
 */
class User {
      
    const ACCESS_TOKEN_ID = 'access_token';
    const REFRESH_TOKEN_ID = 'refresh_token';
    const EXPIRY_DATE_ID = 'expiry_date';
    
    // Client id and secret
    private $client_id;
    private $client_secret;
    
    /**
     * @var string authenticated user name
     */
    private $username;
    
    /**
     * @var string authenticated user password
     */
    private $password;
    
    /**
     * PSR-3 compliant logger 
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * @var string access token
     */
    private $access_token;
    
    /**
     * @var string token to request token refresh
     */
    private $refresh_token;
    
    /**
     * @var \DateTime Token expiry date
     */
    private $expiry_date;   

    # CONSTRUCTORS
    ##############
    
    function __construct() {
        
        // Default logger that does nothing
        $this->logger = new NullLogger();
        
        $a = func_get_args();
        $i = func_num_args();
        if (method_exists($this,$f='__construct'.$i)) {
            call_user_func_array(array($this,$f),$a);
        }
    }
    
    protected function __construct2($_client_id, $_client_secret) {
        $this->client_id = $_client_id;
        $this->client_secret = $_client_secret;
        $this->logger->debug('anonymous user creation');
    }

    protected function __construct3($_client_id, $_client_secret, LoggerInterface $_logger) {
        $this->logger = $_logger;
        $this->__construct2($_client_id, $_client_secret);
    }
    
    protected function __construct4($_client_id, $_client_secret, $_username, $_password) {
        $this->client_id = $_client_id;
        $this->client_secret = $_client_secret;
        $this->username = $_username;
        $this->password = $_password;
        $this->logger->debug('authenticated user creation');
    }
    
    protected function __construct5($_client_id, $_client_secret, $_username, $_password, LoggerInterface $_logger) {
        $this->logger = $_logger;
        $this->__construct4($_client_id, $_client_secret, $_username, $_password);
    }
    
    # API functionalities management
    ################################    
    public static function printJson($s) {
        print(json_encode(json_decode($s), JSON_PRETTY_PRINT));
    }
    
    # User management
    #################
    /**
     * Return the user profile
     * @see https://elocky.com/fr/doc-api-test#get-user Elocky API
     * @return array user profile as an associative array
     */
    public function requestUserProfile() {
        $this->manageToken();
        return $this->curlExec("https://www.elocky.com/webservice/user/.json", 'access_token=' . $this->access_token);
    }
    
    /**
     * Download and save the user photo
     * @param string $_filename filename of the photo to retrieve
     * @param string $_save_dir local directory to save the photo
     */
    public function requestUserPhoto($_filename, $_save_dir) {
        $this->manageToken();
        $photo = $this->curlExec("https://www.elocky.com/webservice/user/photo/" . $_filename . "/download.json", 'access_token=' . $this->access_token, false);
        return file_put_contents($_save_dir . '/' . $_filename, $photo);
    }
    
    
    # Places management
    ###################
    
    /**
     * Return the list of countries and time zone
     * @see https://elocky.com/fr/doc-api-test#liste-pays Elocky API
     * @return array list of countries and time zone
     */
    public function requestCountries() {
        $this->manageToken();
        return $this->curlExec("https://www.elocky.com/webservice/address/country.json", 'access_token=' . $this->access_token);
    }
    
    /** 
     * Return the places associated to this user
     * @see https://elocky.com/fr/doc-api-test#liste-lieu Elocky API
     * @return array list of places as an associative array
     * @throws \Exception in case of communication error with the Elocky server
     */
    public function requestPlaces() {
        $this->manageToken();
        return $this->curlExec("https://www.elocky.com/webservice/address/list.json", 'access_token=' . $this->access_token);
    }
    
    /**
     * Download and save the place photo
     * @param string $_filename filename of the photo to retrieve
     * @param string $_save_dir local directory to save the photo
     */
    public function requestPlacePhoto($_filename, $_save_dir) {
        $this->manageToken();
        $photo = $this->curlExec("https://www.elocky.com/webservice/address/photo/" . $_filename . "/download.json", 'access_token=' . $this->access_token, false);
        return file_put_contents($_save_dir . '/' . $_filename, $photo);
    }
    
    public function requestHistory($_place_id, $_start_nb) {
        $this->manageToken();
        return $this->curlExec("https://www.elocky.com/webservice/address/log/" . $_place_id . "/" . $_start_nb . ".json", 'access_token=' . $this->access_token);
    }
    
    # Access management
    ###################
    
    public function requestAccesses() {
        $this->manageToken();
        return $this->curlExec("https://www.elocky.com/webservice/access/list/user.json", 'access_token=' . $this->access_token);
    }
    
    public function requestGuests() {
        $this->manageToken();
        return $this->curlExec("https://www.elocky.com/webservice/access/list/invite.json", 'access_token=' . $this->access_token);
    }
    
    # Object management
    ###################
    public function requestObjects($_refAdmin, $_idPlace) {
        $this->manageToken();
        return $this->curlExec("https://www.elocky.com/webservice/address/object/" . $_refAdmin . "/" . $_idPlace . ".json", 'access_token=' . $this->access_token);
    }
    
    // FIXME: tentative, pas encore supporté par l'API
    public function requestOpening($_idBoard) {
        $this->manageToken();
        return $this->curlExec("https://www.elocky.com/webservice/object/open/" . $_idBoard . ".json", 'access_token=' . $this->access_token);
    }
    
    ###################################
    
    
    /**
     * Return token data related to this User.
     * @return array associative array which keys are ACCESS_TOKEN_ID, REFRESH_TOKEN_ID and EXPIRY_DATE_ID
     * (EXPIRY_DATE_ID is a timestamp format)
     */
    public function getAuthenticationData() {
        $this->manageToken();
        return array(
                self::ACCESS_TOKEN_ID => $this->access_token,
                self::REFRESH_TOKEN_ID => $this->refresh_token,
                self::EXPIRY_DATE_ID => $this->expiry_date->getTimestamp()
        );
    }
    
    /**
     * Set token data previously retrieved with getAuthenticationData.
     * @param array associative array which keys are ACCESS_TOKEN_ID, REFRESH_TOKEN_ID and EXPIRY_DATE_ID
     * (EXPIRY_DATE_ID is a timestamp format)
     */
    public function setAuthenticationData(array $_authData) {
        $this->access_token = $_authData[self::ACCESS_TOKEN_ID];
        $this->refresh_token = $_authData[self::REFRESH_TOKEN_ID];
        $this->expiry_date = (new \DateTime())->setTimestamp($_authData[self::EXPIRY_DATE_ID]);
        $this->logger->debug('authentication data set');
    }
    
    /**
     * Return the token expiry date
     * @return \DateTime Token expiry date
     */
    public function getTokenExpiryDate() {
        return $this->expiry_date;
    }
        
    /**
     * Refresh the access token
     * Request a new token if needed
     */
    public function refreshToken() {
        if (isset($this->refresh_token)) {
            $this->logger->info('refresh the current token');
            $this->processToken($this->requestUserTokenRefresh());
        }
        else {
            $this->initToken();
        }
    }
    
    /**
     * Manage the token validity. This method shall be called before each request to the Elocky server
     * to insure that the token is defined and valid.
     */
    protected function manageToken() {
        if (isset($this->access_token)) {
            if ($this->isTokenValid()) {
                $this->logger->debug('current token is still valid');
            }
            else {
                $this->logger->info('current token has expired, refresh it');
                try {
                    $this->refreshToken();
                }
                catch (\Exception $e) {
                    $msg = json_decode($e->getMessage(), TRUE);
                    if ($msg['error'] == 'invalid_grant') {
                        $this->logger->info('refresh token has expired, get a new one');
                        $this->initToken();
                    }
                    else
                        throw $e;
                }
            }
        }
        else {
            $this->logger->info('token initialization');
            $this->initToken();
        }
    }
    
    protected function requestAnonymousToken() {
        return $this->curlExec("https://www.elocky.com/oauth/v2/token", $this->getSecretIdFields() ."&grant_type=client_credentials");
    }
    
    protected function requestUserToken() {
        return $this->curlExec("https://www.elocky.com/oauth/v2/token",
                $this->getSecretIdFields() . "&grant_type=password&username=" . $this->username . "&password=" . $this->password);
    }
    
    protected function requestUserTokenRefresh() {
        return $this->curlExec("https://www.elocky.com/oauth/v2/token",
                $this->getSecretIdFields() . "&grant_type=refresh_token&refresh_token=" . $this->refresh_token);
    }
    
    /**
     * Initialize an access token for this User.
     * If username/password are set an authenticated access is requested. An anonymous one otherwise.
     * @see User::$username
     * @see User::$password
     */
    protected function initToken() {
        if (isset($this->username)) {
            $this->logger->info('request an authenticated user access');
            $this->processToken($this->requestUserToken());
        }
        else {
            $this->logger->info('request an anonymous access');
            $this->processToken($this->requestAnonymousToken());
        }
    }
    
    /**
     * Returns whether or not the token is valid.
     * @return bool TRUE if token is still valid, FALSE if not
     */
    protected function isTokenValid() {
        return ($this->expiry_date > (new \DateTime())->add(new \DateInterval('PT60S')));
    }
    
    /**
     * Execute a request to the Elocky server
     * @param string $url request url to contact
     * @param string $param request parameters
     * @param bool $is_json set to true if the server response is supposed to be JSON formatted
     * @throws \Exception if the Elocky servers returns a non JSON string; or if the Elocky server returned an error
     * @return array JSON array
     */
    protected function curlExec($url, $param, $is_json = true) {
        $ch = curl_init($url . '?' . $param);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        curl_close($ch);

        if ($data === FALSE) {
            throw new \Exception(json_encode(
                    array("error" => "connexion_error",
                          "error_description" => "cannot connect to the distant server")));
        }
          
        if (strlen($data) == 0) {
            throw new \Exception(json_encode(array("error" => "data_error", "error_description" => "no data retrieved from the distant server")));
        }
        
        if ($is_json) {
            $this->logger->debug('reception of ' . strval($data));
            $ret_data = json_decode($data, TRUE);
            if (json_last_error() != JSON_ERROR_NONE) {
                throw new \Exception(json_encode(array("error" => "json_error", "error_description" => json_last_error_msg())));
            }
            
            if (array_key_exists('error', $ret_data)) {
                throw new \Exception($data);
            }
        }
        else {
            $this->logger->debug('reception of ' . strlen($data) . ' bytes');
            $ret_data = $data;
        }
        
        return $ret_data;
    }
    
    protected function getSecretIdFields() {
        return "client_id=" . $this->client_id . "&client_secret=" . $this->client_secret;
    }
    
    private function processToken($_jsonArray) {
        $this->access_token = $_jsonArray['access_token'];
        if (array_key_exists('refresh_token', $_jsonArray))
            $this->refresh_token = $_jsonArray['refresh_token'];
        $this->expiry_date = (new \DateTime())->add(new \DateInterval('PT'.$_jsonArray['expires_in'].'S'));
    }
}