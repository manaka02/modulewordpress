<?php
/**
 * Created by PhpStorm.
 * User: Miorantsoa
 * Date: 06/05/2017
 * Time: 23:06
 */
// if ( ! defined('BASEPATH')) exit('No direct script access allowed');
ini_set('error_reporting', E_STRICT);
require_once "Crypt/TripleDES.php";

class Crypt extends Crypt_TripleDES {
    public function __construct() {
        parent::__construct();
    }
}