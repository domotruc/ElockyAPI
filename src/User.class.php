<?php

namespace ElockyAPI;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Implemeent the Elocky API.
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
    public function requestUserProfile() : array {
        $this->manageToken();
        return $this->curlExec("https://www.elocky.com/webservice/user/.json", 'access_token=' . $this->access_token);
    }
    
    
    # Places management
    ###################
    
    /**
     * Return the list of countries and time zone
     * @see https://elocky.com/fr/doc-api-test#liste-pays Elocky API
     * @return array list of countries and time zone
     */
    public function requestCountries() : array {
        $this->manageToken();
        return $this->curlExec("https://www.elocky.com/webservice/address/country.json", 'access_token=' . $this->access_token);
    }
    
    /** 
     * Return the places associated to this user
     * @see https://elocky.com/fr/doc-api-test#liste-lieu Elocky API
     * @return array list of places as an associative array
     */
    public function requestPlaces() : array {
        $this->manageToken();
        return $this->curlExec("https://www.elocky.com/webservice/address/list.json", 'access_token=' . $this->access_token);
    }
    
    # Access management
    ###################
    
    public function requestAccesses() : array {
        $this->manageToken();
        return $this->curlExec("https://www.elocky.com/webservice/access/list/user.json", 'access_token=' . $this->access_token);
    }
    
    public function requestGuests() : array {
        $this->manageToken();
        return $this->curlExec("https://www.elocky.com/webservice/access/list/invite.json", 'access_token=' . $this->access_token);
    }
    
    # Object management
    ###################
    public function requestObjects($_refAdmin, $_idPlace) : array {
        $this->manageToken();
        return $this->curlExec("https://www.elocky.com/webservice/address/object/" . $_refAdmin . "/" . $_idPlace . ".json", 'access_token=' . $this->access_token);
    }
    
    ###################################
    
    
    /**
     * Return token data related to this User.
     * @return array associative array which keys are ACCESS_TOKEN_ID, REFRESH_TOKEN_ID and EXPIRY_DATE_ID
     * (EXPIRY_DATE_ID is a timestamp format)
     */
    public function getAuthenticationData() : array {
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
    public function getTokenExpiryDate() : \DateTime {
        return $this->expiry_date;
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
                $this->logger->info('current token is no more valid, refresh it');
                $this->refreshToken();
            }
        }
        else {
            $this->logger->info('token initialization');
            $this->initToken();
        }
    }
    
    protected function requestAnonymousToken() : array {
        return $this->curlExec("https://www.elocky.com/oauth/v2/token", $this->getSecretIdFields() ."&grant_type=client_credentials");
    }
    
    protected function requestUserToken() : array {
        return $this->curlExec("https://www.elocky.com/oauth/v2/token",
                $this->getSecretIdFields() . "&grant_type=password&username=" . $this->username . "&password=" . $this->password);
    }
    
    protected function requestUserTokenRefresh() : array {
        return $this->curlExec("https://www.elocky.com/oauth/v2/token",
                $this->getSecretIdFields() . "&grant_type=refresh_token&refresh_token=" . $this->refresh_token);
    }
    
    protected function refreshToken() {
        if (isset($this->refresh_token)) {
            $this->logger->info('refresh the current token');
            $this->processToken($this->requestUserTokenRefresh());
        }
        else {
            $this->initToken();
        }
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
     * @return boolean TRUE if token is still valid, FALSE if not
     */
    protected function isTokenValid() : bool {
        return ($this->expiry_date > (new \DateTime())->add(new \DateInterval('PT60S')));
    }
    
    /**
     * @param string $url request url to contact
     * @param string $param request parameters
     * @throws \Exception if the Elocky servers returns a non JSON string; or if the Elocky server returned an error
     * @return array JSON array
     */
    protected function curlExec($url, $param) : array {
        $ch = curl_init($url . '?' . $param);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        curl_close($ch);

        $this->logger->debug('Reception from Elocky server: ' . $data);
        
        $jsonArray = json_decode($data, TRUE);
        if (json_last_error() != JSON_ERROR_NONE) {
            $msg = json_last_error_msg();
            $this->logger->critical('json decoding error: ' . $msg);
            throw new \Exception($msg);
        }
        
        if (array_key_exists('error', $jsonArray)) {
            $this->logger->error('Elocky server returns an error: ' . $data);
            throw new \Exception($data);
        }
        
        return $jsonArray;
    }
    
    protected function getSecretIdFields() : string {
        return "client_id=" . $this->client_id . "&client_secret=" . $this->client_secret;
    }
    
    private function processToken($_jsonArray) {
        $this->access_token = $_jsonArray['access_token'];
        if (array_key_exists('refresh_token', $_jsonArray))
            $this->refresh_token = $_jsonArray['refresh_token'];
        $this->expiry_date = (new \DateTime())->add(new \DateInterval('PT'.$_jsonArray['expires_in'].'S'));
    }
}