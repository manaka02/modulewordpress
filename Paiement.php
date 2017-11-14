<?php


class Paiement {
    private $public_key;
    private $private_key;
    private $client_id;
    private $client_secret;
    private $util;
    private $site_url;
    private $token=null;
    const URL_AUTH = "https://pro.ariarynet.com/oauth/v2/token";
    const URL_PAIEMENT = "https://pro.ariarynet.com/api/paiements";
    const URL_RESULTAT = "https://pro.ariarynet.com/api/resultats";
    const URL_PAIE =  "https://moncompte.ariarynet.com/paiement/";
    const URL_RESULT_PAIE = "https://moncompte.ariarynet.com/paiement_resultat";
	
    /**
     * Paiement constructor.
     * @param $public_key
     * @param $private_key
     * @param $client_id
     * @param $client_secret
     * @param $site_url
     * @param Util $util
     */
    public function __construct($params){//$public_key, $private_key, $client_id, $client_secret
        $this->public_key = $params['public_key'];
        $this->private_key = $params['private_key'];
        $this->client_id = $params['client_id'];
        $this->client_secret = $params['client_secret'];
        $this->site_url= "http://www.tsaramora.com";
        
    }


    /**
     * @return mixed
     */
    private function getAccess(){
        $util = new Util();
        if($this->token!=null) return $this->token;
        $param = array(
            'client_id'=>$this->client_id,
            'client_secret'=>$this->client_secret,
            'grant_type'=> 'client_credentials'
        );
        $responseCurl = $util->sendCurl(self::URL_AUTH,"POST",array(),$param);
        $json = json_decode(
            $responseCurl
        );
        if(isset($json->error)){
            throw new Exception($json->error.": ".$json->error_description);
        }
        $this->token=$json->access_token;
        return $json->access_token;
    }
	
    /**
     * @param $url
     * @param array $params_{"unitto_send
     * @return bool|int|string
     */
    private function send($url,array $params_to_send){
        $util = new Util();
        $params_crypt=$util->crypter($this->public_key,json_encode($params_to_send));
        $params=array(
            "site_url"=>$this->site_url,
            "params" => $params_crypt
        );
		/*$param_test = bin2hex($params_crypt);
        var_dump($param_test, hex2bin($param_test));
		die();*/
        $headers=array("Authorization:Bearer ".$this->getAccess());
        $json=$util->sendCurl($url,"POST",$headers,$params);
        $error=json_decode($json);
        // var_dump($error);
        if(isset($error->error)){
            throw new Exception($error->error.": ".$error->error_description);
        }
        return $util->decrypter($this->private_key,$json);
    }

	/**
     * @param $idpanier
     * @param $montant
     * @param $nom
     * @param $reference
     * @param $adresseip
     * @return bool|int|string
     */
    public function initPaie($idpanier,$montant,$nom,$reference,$adresseip){
        $now=new DateTime();
        $params=array(
            "unitemonetaire"=>"Ar",
            "adresseip"=>$adresseip,
            "date"=>$now->format('Y-m-d H:i:s'),
            "idpanier"=>$idpanier,
            "montant"=>$montant,
            "nom"=>$nom,
            "reference"=>$reference
        );
        $id=$this->send(self::URL_PAIEMENT,$params);
		return $id;
//        return new RedirectResponse(self::URL_PAIE.$id);
        // redirect(self::URL_PAIE.$id);
    }

    /**
     * @param $idpaiement
     * @return bool|int|string
     */
    public function resultPaie($idpaiement){
        $util = new Util();
        $idpaiement=$util->decrypter($this->private_key,$idpaiement);
        $params=array(
            "idpaiement"=>$idpaiement
        );
        $res=$this->send(self::URL_RESULT_PAIE,$params);
        return json_decode($res);
    }

    function decryptWithFixBug($key, $input){
		$target = new Util();
		$output = $target->decrypter($key, $input);
		if(!$output){
			$fixedValue = fixBug($input);
			$output = $target->decrypter($key,$fixedValue);
		}
		return $output;
	}

	function fixBug($cryptedValue){
		$hexData = bin2Hex($cryptedValue);
		$tempvalue = $hexData;
		$tempvalue = str_replace('5c5c', "ZZ", $tempvalue); 
		$tempvalue = str_replace('5c', "", $tempvalue); 
		$tempvalue = str_replace('ZZ', "5c", $tempvalue); 
		return hex2Bin($tempvalue);
	}

}