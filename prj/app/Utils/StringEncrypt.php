<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Utils;

/**
 * Description of StringEncrypt
 *
 * @author k-noguchi
 */
class StringEncrypt
{
    private $td;
    private $iv_size;
    private $algorithm;
    private $mode;
    private $init = false;
     
    // function __construct($algorithm="blowfish", $mode="ecb"){
    //     $this->algorithm = $algorithm;
    //     $this->mode = $mode;
    //     $this->td = mcrypt_module_open($this->algorithm, '', $this->mode, '');
    //     $this->iv_size  = mcrypt_enc_get_iv_size($this->td);
    // }
     
    // function __destruct(){
    //     if($this->init) mcrypt_generic_deinit($this->td);
    //     mcrypt_module_close($this->td);
    // }
     
    // private function init($pass, $iv=null){
    //     if(is_null($iv)){
    //         $iv = mcrypt_create_iv($this->iv_size, MCRYPT_DEV_RANDOM);
    //     } else {
    //         $iv = base64_decode($iv);
    //     }
         
    //     if($this->iv_size !== strlen($iv)){
    //         throw new Exception("Incorrect IV size.");
    //     };
         
    //     $key = substr( md5($pass), 0, mcrypt_enc_get_key_size($this->td));
    //     mcrypt_generic_init($this->td, $key, $iv);
    //     $this->init = true;
    // }
    
    // public function deinit(){
    //     if($this->init) mcrypt_generic_deinit($this->td);
    //     $this->init = false;
    // }
    
    public function encrypt($str, $pass, $iv=null){
        // $iv = $this->init($pass, $iv);
        // $encrypted = mcrypt_generic($this->td, $str);
        // return base64_encode($encrypted);

        $salt = openssl_random_pseudo_bytes(16);
        $salted = '';
        $dx = '';

        while(strlen($salted) < 48) {
            $dx = hash('sha256', $dx.$pass.$salt, true);
            $salted .= $dx;
        }

        $key = substr($salted, 0, 32);
        $iv = substr($salted, 32, 16);

        $encrypted_data = openssl_encrypt($str, 'AES-256-CBC', $key, true, $iv);
        return base64_encode($salt.$encrypted_data);
    }
 
    public function decrypt($str, $pass, $iv=null){
        // $iv = $this->init($pass, $iv);
        // $str = base64_decode($str);
        // $decrypted = mdecrypt_generic($this->td, $str);
        // return $decrypted;

        $data = base64_decode($str);
        $salt = substr($data, 0, 16);
        $ct = substr($data, 16);

        $rounds = 3;
        $data00 = $pass.$salt;
        $hash = [];
        $hash[0] = hash('sha256', $data00, true);
        $result = $hash[0];
        for ($i = 1; $i < $rounds; $i++) {
            $hash[$i] = hash('sha256', $hash[$i - 1].$data00, true);
            $result .= $hash[$i];
        }
        $key = substr($result, 0, 32);
        $iv = substr($result, 32, 16);

        return openssl_decrypt($ct, 'AES-256-CBC', $key, true, $iv);
    }
     
    // public function create_iv(){
    //     $iv = mcrypt_create_iv($this->iv_size, MCRYPT_DEV_RANDOM);
    //     return base64_encode($iv);
    // }
}