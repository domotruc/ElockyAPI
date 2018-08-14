<?php

namespace ElockyAPI;


/**
 * Implemeent the Elocky API.
 * The User class is either an anonymous user or an authenticated user depending on the credential parameters 
 * at object creation.
 * @see https://elocky.com/fr/doc-api-test Elocky API
 * @author domotruc
 *
 */
class User {
    
    
    /**
     * @var unknown
     */
    const ACCESS_TOKEN_ID = 'access_token';

    const REFRESH_TOKEN_ID = 'refresh_token';
    const EXPIRY_DATE_ID = 'expiry_date';
    
    
    // Client id and secret
    private $client_id;
    private $client_secret;
    
    private $username;
    private $password;
    
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
    
    /**
     * @var callable Callback called after reception of API result
     */
    private $postreq_callback;
    

    # CONSTRUCTORS
    ##############
    
    function __construct() {
        $a = func_get_args();
        $i = func_num_args();
        if (method_exists($this,$f='__construct'.$i)) {
            call_user_func_array(array($this,$f),$a);
        }
    }
    
    protected function __construct2($_client_id, $_client_secret) {
        $this->client_id = $_client_id;
        $this->client_secret = $_client_secret;
    }

    protected function __construct4($_client_id, $_client_secret, $_username, $_password) {
        $this->client_id = $_client_id;
        $this->client_secret = $_client_secret;
        $this->username = $_username;
        $this->password = $_password;
    }
    
    protected function __construct5($_client_id, $_client_secret, $_username, $_password, $_postreq_callback) {
        $this->setPostRequestCallback($_postreq_callback);
        $this->__construct4($_client_id, $_client_secret, $_username, $_password);
    }
    
    # API functionalities management
    ################################
    /**
     * Set the callback to be called after each API request result reception
     * Callback shall have one string parameter which is the JSON request result
     * @param callable Callback 
     */
    public function setPostRequestCallback(callable $_postreq_callback) {
        $this->postreq_callback = $_postreq_callback;
    }
    
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
     */
    public function requestPlaces() {
        $this->manageToken();
        return $this->curlExec("https://www.elocky.com/webservice/address/list.json", 'access_token=' . $this->access_token);
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
    
    ###################################
    
    
    /**
     * Return token data related to this User.
     * @return array associative array which keys are ACCESS_TOKEN_ID, REFRESH_TOKEN_ID and EXPIRY_DATE_ID
     * (EXPIRY_DATE_ID is a timestamp format)
     */
    public function getAuthenticationData() {
        $this->manageToken();
        return json_encode(array(
                self::ACCESS_TOKEN_ID => $this->access_token,
                self::REFRESH_TOKEN_ID => $this->refresh_token,
                self::EXPIRY_DATE_ID => $this->expiry_date->getTimestamp()
        ));
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
    }
    
    /**
     * Return the token expiry date
     * @return \DateTime Token expiry date
     */
    public function getTokenExpiryDate() {
        return $this->expiry_date;
    }
        
    /**
     * Manage the token validity. This method shall be called before each request to the Elocky server
     * to insure that the token is defined and valid.
     */
    protected function manageToken() {
        if (isset($this->access_token)) {
            if (!$this->isTokenValid()) {
                $this->refreshToken();
            }
        }
        else {
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
    
    protected function refreshToken() {
        if (isset($this->refresh_token)) {
            $this->processToken($this->requestUserTokenRefresh());
        }
        else {
            $this->processToken($this->initToken());
        }
    }
    
    protected function initToken() {
        if (isset($this->username))
            $this->processToken($this->requestUserToken());
        else
            $this->processToken($this->requestAnonymousToken());
    }
    
    /**
     * Returns whether or not the token is valid.
     * @return boolean TRUE if token is still valid, FALSE if not
     */
    protected function isTokenValid() {
        return ($this->expiry_date > (new \DateTime())->add(new \DateInterval('PT60S')));
    }
    
    /**
     * @param string $url request url to contact
     * @param string $param request parameters
     * @throws Exception if the Elocky servers returns a non JSON string; or if the Elocky server returned an error
     * @return array JSON array
     */
    protected function curlExec($url, $param) {
        $ch = curl_init($url . '?' . $param);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        curl_close($ch);

        // Call the callback if defined
        if (isset($this->postreq_callback))
            call_user_func($this->postreq_callback, $data);
        
        $jsonArray = json_decode($data, TRUE);
        if (json_last_error() != JSON_ERROR_NONE)
            throw new \Exception(json_last_error_msg());
        
        if (array_key_exists('error', $jsonArray))
            throw new \Exception($data);
        
        return $jsonArray;
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