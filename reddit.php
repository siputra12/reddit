<?php
$r = new Reddit;
$u = readline("[?] User\t");
$e = readline("[?] Email\t");
$p = readline("[?] Pass\t");
$data = $r->register($u, $e, $p);
print_r($data);
$r->clean_up();

class Reddit{
	private $cookies = "reddit.cookies";
	private $rawheaders = array("Content-Type" => "application/x-www-form-urlencoded", "X-Dev-Ad-Id" => "00000000-0000-0000-0000-000000000000", "Accept" => "*/*", "X-Reddit-Dpr" => "3", "Device-Name" => "AsusRog", "User-Agent" => "Reddit/Version 2024.14.0/Build 614594/iOS Version 17.4.1 (Build 21E236)");
	private $authentic;

	public function __construct(){
		$this->authentic = $this;
        $this->api = function($x = null, $headers = array()){
            switch($x){
            	case "acc";
            		$this->host = "accounts.reddit.com";
            		break;
            	case "gql";
            		$this->host = "gql-fed.reddit.com";
            		break;
            	default:
            		$this->host = "www.reddit.com";
            }
            return $this->host;
        };
        return;
	}

	private function init_reg($u, $e, $p){
		$this->set_token("loid");
		$init = curl(@json_encode(array("name" => "reddit","func" => "initiate_register","a" => $u,"b" => $e,"c" => $p)));
		$inits = @json_decode(str_replace(array("\t", "\r", "\n", " "), "", $init), true);
		$this->set_raw_headers(array(
			"X-Reddit-Device-Id" => $inits['cvi'],
			"Client-Vendor-Id" => $inits['cvi'],
			"X-Hmac-Signed-Result" => $inits['sign1'],
			"X-Hmac-Signed-Body" => $inits['sign2']
		));
		return $inits['data'];
	}

	private function set_token($type){
		$host = ($this->api)();
		$this->set_raw_headers(array("Host" => $host, "Authorization" => "Basic TE5EbzlrMW84VUFFVXc6"));
		$c = $this->curl("$host/auth/v2/oauth/access-token/$type", '{"scopes":["*","email"]}', true);
		$hs = @explode("\n", $c[0]);
		$arr = array("x-reddit-loid", "X-reddit-session");
		$hdr = array();
		foreach($hs as $h){
			$hdrs = @explode(": ", $h);
			if(in_array($hdrs[0], $arr)) $hdr[$hdrs[0]] = $hdrs[1];
		}
		$this->set_raw_headers($hdr);
		return @json_decode($c[1], true)['access_token'];
	}

	public function register($u, $e, $p){
		$data = $this->init_reg($u, $e, $p);
		$host = ($this->api)("acc");
		$path = "$host/api/register";
		$this->set_raw_headers(array("Host" => $host));
		return $this->curl($path, str_replace("'", "", $data));
	}

	private function set_raw_headers($headers = array()){
		$h = $this->rawheaders;
		foreach($headers as $n => $v) $h[$n] = $v;
		$this->rawheaders = $h;
		return $h;
	}

	private function get_headers(){
		$h = $this->rawheaders;
		$headers = array();
		foreach($h as $n => $v) $headers[] = "$n: $v";
		return $headers;
	}

	public function clean_up(){
		if(file_exists($this->cookies)) @unlink($this->cookies);
		return $this->authentic;
	}

	private function curl($path, $body = false, $hr = false){
		$header = $this->get_headers();
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://'.$path);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		if($body){
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		}
		$headers = array();
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookies);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookies);
		$result = @explode("\n\n", str_replace("\r", "", curl_exec($ch)));
		curl_close($ch);
		$result = $hr ? $result : $result[1];
		return $result;
	}
}

function curl($body){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'https://notpremium.lol/json_api.php');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
	$headers = array();
	$headers[] = 'Host: notpremium.lol';
	$headers[] = 'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:124.0) Gecko/20100101 Firefox/124.0';
	$headers[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8';
	$headers[] = 'Accept-Language: id,en-US;q=0.7,en;q=0.3';
	$headers[] = 'Content-Type: application/json';
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	$result = curl_exec($ch);
	curl_close($ch);
	return $result;
}