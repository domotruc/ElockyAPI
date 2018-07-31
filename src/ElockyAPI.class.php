<?php

class ElockyAPI {
    
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
     * @var DateTime Token expiry date
     */
    private $expiry_date;
    
    /**
     * @var callable Callback called after reception of API result
     */
    private $postreq_callback;
    

    /*
     * CONSTRUCTORS
     */
    
    function __construct() {
        $a = func_get_args();
        $i = func_num_args();
        if (method_exists($this,$f='__construct'.$i)) {
            call_user_func_array(array($this,$f),$a);
        }
    }
    
    protected function __construct1($_token) {
        $this->access_token = $_token;
    }
    
    protected function __construct2($_client_id, $_client_secret) {
        $this->client_id = $_client_id;
        $this->client_secret = $_client_secret;
        $this->processToken($this->requestAnonymousToken());
    }

    protected function __construct4($_client_id, $_client_secret, $_username, $_password) {
        $this->client_id = $_client_id;
        $this->client_secret = $_client_secret;
        $this->username = $_username;
        $this->password = $_password;
        $this->processToken($this->requestUserToken());
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
    
    # Places management
    ###################
    
    /**
     * Return the list of countries and time zone
     * @return array list of countries and time zone
     */
    public function getCountries() {
        return $this->curlExec("https://www.elocky.com/webservice/address/country.json", 'access_token=' . $this->access_token);
    }
    
    public function getPlaces() {
        return $this->curlExec("https://www.elocky.com/webservice/address/list.json", 'access_token=' . $this->access_token);
    }
    
    # Access management
    ###################
    
    public function getAccesses() {
        return $this->curlExec("https://www.elocky.com/webservice/access/list/user.json", 'access_token=' . $this->access_token);
    }
    
    public function getGuests() {
        return $this->curlExec("https://www.elocky.com/webservice/access/list/invite.json", 'access_token=' . $this->access_token);
    }
    
    # Object management
    ###################
    public function getObjects() {
        
    }
    
    
    
    ###################################
    
    
    public function getAccessToken() {
        return $this->access_token;
    }
    
    public function getRefreshToken() {
        return $this->refresh_token;
    }
    
    public function getTokenExpiryDate() {
        return $this->expiry_date;
    }
    
    protected function requestAnonymousToken() {
        return $this->curlExec("https://www.elocky.com/oauth/v2/token", $this->getSecretIdFields() ."&grant_type=client_credentials");
    }
    
    protected function requestUserToken() {
        return $this->curlExec("https://www.elocky.com/oauth/v2/token",
                $this->getSecretIdFields() . "&grant_type=password&username=" . $this->username . "&password=" . $this->password);
    }
    
    protected function curlExec($url, $param) {
        $ch = curl_init($url . '?' . $param);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        curl_close($ch);
        if (isset($this->postreq_callback))
            call_user_func($this->postreq_callback, $data);
        return json_decode($data, TRUE);
    }
        
    protected function getSecretIdFields() {
        return "client_id=" . $this->client_id . "&client_secret=" . $this->client_secret;
    }
     
    private function processToken($_jsonArray) {
        $this->access_token = $_jsonArray['access_token'];
        if (array_key_exists('refresh_token', $_jsonArray))
            $this->refresh_token = $_jsonArray['refresh_token'];
        $this->expiry_date = (new DateTime())->add(new DateInterval('PT'.$_jsonArray['expires_in'].'S'));
        
    }
}