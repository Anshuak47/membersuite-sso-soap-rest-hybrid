<?php

class Curl{
   
	public $url = '';
	public $method = 'post'; // post or get
	public $returnval = true;
	public $debug = false;
	public $header = ''; // array
	public $post = true; // true or false
	public $postdata='';
	/** @var bool|string */
	protected static $curl_error = false;
	
	public function init(){
		
		// cURL initilization 
		$curl = curl_init();

		// This is custom to this wordpress plugin:
		$certPath = $_SERVER['DOCUMENT_ROOT'].'/wp-content/plugins/membersuite-sso/ms_sdk/lib/mozilla.pem';
		curl_setopt($curl, CURLOPT_CAINFO, $certPath);
		
		// setting up the cURL URL
		$this->curlurl($curl);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, $this->returnval);
		$this->setheader($curl);
		curl_setopt ($curl, CURLOPT_POST, $this->post);
		// setting the postdata in the cURL that needs to be posted
		$this->curlpostdata($curl);
		$result= curl_exec($curl);
		// Show curl errors
        static::$curl_error = curl_error($curl);
        if($this->debug) {
		    $response = curl_error($curl);
		    return $response;
		}
		curl_close ($curl);
		return $result;
	}

    /**
     * @return bool|string
     */
    public static function getCurlError()
    {
        return self::$curl_error;
    }

	private function curlpostdata($curl){
	
		$retpostdata = curl_setopt ($curl, CURLOPT_POSTFIELDS, $this->postdata);
		return $retpostdata;
	}
	private function curlurl($curl){
	
		$returl = curl_setopt ($curl, CURLOPT_URL, $this->url);
		return $returl;
	}
	
	// Set cURL headers
	private function setheader($curl){
	
		$retheader = curl_setopt($curl,CURLOPT_HTTPHEADER,$this->header);
		return $retheader;
	}
    
}
?>