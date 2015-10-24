<?php
/*
 *
 * @ PHP class for accessing DigitalOcean API version 2
 *
 * @ Version    : 1.0.0
 * @ Author     : Hristo Mitev
 * @ Release on : 2015-10-24
 * @ Website    : http://hmitev.com
 * @ E-mail     : mail@hmitev.com
 *
 */

class digitalOcean {
    private $baseurl = 'https://api.digitalocean.com/v2';
    private $token = '';
    private $allowedMethods = array('POST','GET','PUT','DELETE');
    private $result = array();

    function __construct($token) {
        $this->token = $token;
    }

    public function getResult(){
        return $this->result;
    }

    public function getError(){
        return isset($this->result['error']) ? $this->result['error'] : NULL;
    }

    public function setError($error){
        $this->result['error'] = $error;
    }


    private function request($method = 'GET', $file, $parameters = null){
        if(!in_array($method,$this->allowedMethods)){
            $this->setError('Not allowed method!');
            return false;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$this->baseurl.$file);
        curl_setopt($ch, CURLOPT_POST, (int) ($method != 'GET') );

        if($parameters) {
            $datafunction = $method == 'GET' ? 'http_build_query' : 'json_encode';
            curl_setopt($ch, CURLOPT_POSTFIELDS, $datafunction($parameters));
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer '. $this->token,
            'Content-Type: application/json'
        ));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if(!in_array($method,array('POST','GET'))) curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if( ($response = curl_exec ($ch)) === false)
        {
            $this->setError('Curl error: ' . curl_error($ch));
            curl_close ($ch);
            return false;
        }

        curl_close ($ch);

        $return = @json_decode($response, true);

        if(empty($return)){
            $this->setError('Invalid response: '.$response);
            return false;
        } else {
            $this->result['result'] = $return;
            return true;
        }
    }


    /**
     * Account functions
     */

    public function accountInfo(){
        return $this->request('GET','/account');
    }


    /**
     * Domains functions
     */

    public function listAllDomains(){
        return $this->request('GET','/domains');
    }

    public function createNewDomain($domainName, $ipAddress){
        return $this->request('POST','/domains',  array('name' => $domainName, 'ip_address' => $ipAddress));
    }

    public function getDomain($domainName){
        return $this->request('GET','/domains/'.$domainName);
    }

    public function deleteDomain($domainName){
        return $this->request('DELETE','/domains/'.$domainName);
    }

    public function listAllDomainRecords($domainName){
        return $this->request('GET','/domains/'.$domainName.'/records');
    }

    public function createNewDomainRecord($type, $domainName, $data, $priority, $port, $weight){
        return $this->request('POST','/domains/'.$domainName.'/records', array('type'=>$type,'name'=>$domainName,'data'=>$data,'priority'=>$priority,'port'=>$port,'weight'=>$weight));
    }

    public function getDomainRecord($domainName, $recordID){
        return $this->request('GET','/domains/'.$domainName.'/records/'.$recordID);
    }

    public function updateDomainRecord($recordID, $type, $domainName, $data, $priority, $port, $weight){
        return $this->request('PUT','/domains/'.$domainName.'/records/'.$recordID, array('type'=>$type,'name'=>$domainName,'data'=>$data,'priority'=>$priority,'port'=>$port,'weight'=>$weight));
    }

    public function deleteDomainRecord($domainName, $recordID){
        return $this->request('DELETE','/domains/'.$domainName.'/records/'.$recordID);
    }

    /**
     * Droplets functions
     */

    public function listAllDroplets(){
        return $this->request('GET','/droplets');
    }


    public function createNewDroplet($name, $regionId, $sizeId, $imageId,  $ssh_keys = null, $backups = false, $ipv6 = false,  $private_networking = null, $userData = null){

        if($this->validateSize($sizeId) == false) {
            return false;
        }elseif($this->validateImage($imageId) == false) {
            return false;
        }elseif($this->validateRegion($regionId) == false) {
            return false;
        }else{

            $parameters = array('name' => $name, 'region' => $regionId, 'size' => $sizeId, 'image' => $imageId, 'backups'=>$backups, 'ipv6'=>$ipv6, 'user_data'=>$userData);

            if(!is_null($ssh_keys))
                $parameters['ssh_keys'] = $ssh_keys;

            if(!is_null($private_networking))
                $parameters['private_networking'] = $private_networking;

            return $this->request('POST', '/droplets', $parameters);

        }
    }

    public function checkoutDroplet($dropletId){
        return $this->request('GET','/droplets/' . $dropletId);
    }

    public function rebootDroplet($dropletId){
        return $this->request('POST', '/droplets/' . $dropletId .'/actions', array('type'=>'reboot'));
    }

    public function powerCycleDroplet($dropletId){
        return $this->request('POST', '/droplets/' . $dropletId .'/actions', array('type'=>'power_cycle'));
    }

    public function shutdownDroplet($dropletId){
        return $this->request('POST', '/droplets/' . $dropletId .'/actions', array('type'=>'shutdown'));
    }

    public function powerOffDroplet($dropletId){
        return $this->request('POST', '/droplets/' . $dropletId .'/actions', array('type'=>'power_off'));
    }

    public function powerOnDroplet($dropletId){
        return $this->request('POST', '/droplets/' . $dropletId .'/actions', array('type'=>'power_on'));
    }

    public function restoreDroplet($dropletId, $imageId){
        if($this->validateImage($imageId) == false) {
            return false;
        }else{
            return $this->request('POST', '/droplets/' . $dropletId . '/actions', array('type' => 'restore', 'image' => $imageId));
        }
    }

    public function resetPasswordDroplet($dropletId){
        return $this->request('POST', '/droplets/' . $dropletId .'/actions', array('type'=>'password_reset'));
    }

    public function resizeDroplet($dropletId, $sizeId){
        if($this->validateSize($sizeId) == false) {
            return false;
        }else{
            return $this->request('POST', '/droplets/' . $dropletId . '/actions', array('type' => 'resize', 'size' => $sizeId));
        }
    }

    public function rebuildDroplet($dropletId, $imageId){
        if($this->validateImage($imageId) == false) {
            return false;
        }else{
            return $this->request('POST', '/droplets/' . $dropletId . '/actions', array('type' => 'rebuild', 'image' => $imageId));
        }
    }

    public function renameDroplet($dropletId, $name){
        return $this->request('POST', '/droplets/' . $dropletId . '/actions', array('type' => 'rename', 'name' => $name));
    }

    public function snapshotDroplet($dropletId, $name){
        return $this->request('POST', '/droplets/' . $dropletId . '/actions', array('type' => 'snapshot', 'name' => $name));
    }

    public function enableBackupDroplet($dropletId){
        return $this->request('POST', '/droplets/' . $dropletId . '/actions', array('type'=>'enable_backups'));
    }

    public function disableBackupDroplet($dropletId){
        return $this->request('POST', '/droplets/' . $dropletId . '/actions', array('type'=>'disable_backups'));
    }

    public function deleteDroplet($dropletId){
        // Scrub data per default
        return $this->request('DELETE', '/droplets/' . $dropletId);
    }

    public function validateDroplet($dropletId){
        $found = false;

        $checkoutDroplet = $this->checkoutDroplet($dropletId);

        if($checkoutDroplet['status'] == 'OK') {
            $found = true;
        }

        return $found;
    }

    /**
     * Regions functions
     */

    public function regions(){
        return $this->request('/regions');
    }

    public function validateRegion($regionId){
        $found = false;

        $regions = $this->regions();
        $regions = $regions['regions'];

        foreach($regions as $value){
            if($value['id'] == $regionId) {
                $found = true;
                break;
            }
        }

        return $found;
    }

    /**
     * Images functions
     */

    public function images($filter = ''){
        $parameters = array();
        if($filter){
            $parameters = array('filter' => $filter);
        }

        return $this->request('GET', '/images', $parameters);
    }

    public function checkoutImage($imageId){
        return $this->request('GET', '/images/' . $imageId);
    }

    public function deleteImage($imageId){
        return $this->request('DELETE', '/images/' . $imageId);
    }

    public function transferImage($imageId, $region){
        return $this->request('POST', '/images/' . $imageId . '/actions', array('type'=>'transfer', 'region'=>$region));
    }

    public function updateImage($imageId, $name){
        return $this->request('PUT', '/images/' . $imageId , array( 'name'=>$name));
    }

    public function validateImage($imageId){
        $found = false;

        $checkoutImage = $this->checkoutImage($imageId);

        if($checkoutImage['status'] == 'OK') {
            $found = true;
        }

        return $found;
    }

    /**
     * Sizes functions
     */

    public function sizes(){
        return $this->request('GET', '/sizes');
    }

    public function validateSize($sizeId){
        $found = false;

        $sizes = $this->sizes();
        $sizes = $sizes['sizes'];

        foreach($sizes as $value) {
            if($value['slug'] == $sizeId) {
                $found = true;
                break;
            }
        }

        return $found;
    }




    /**
     * SSH Keys functions
     */

    public function listAllKeys(){
        return $this->request('GET', '/account/keys');
    }

    public function createNewKey($name, $ssh_pub_key){
        return $this->request('POST', '/account/keys', array('name' => $name, 'public_key' => $ssh_pub_key));
    }

    public function getKey($keyId){
        return $this->request('GET', '/account/keys/' . $keyId);
    }

    public function updateKey($keyId, $name){
        return $this->request('PUT', '/account/keys/' . $keyId , array('name' => $name));
    }

    public function destroyKey($keyId)
    {
        return $this->request('DELETE', '/account/keys/' . $keyId);
    }


    /**
     * Events functions
     */

    public function checkoutEvent($eventId){
        return $this->request('/events/' . $eventId);
    }
}

?>
