<?php
    function encrypt($str, $pass, $iv=null){
        // $iv = $this->init($pass, $iv);
        // $encrypted = mcrypt_generic($this->td, $str);
        // return base64_encode($encrypted);

        // set a random salt
        $salt = openssl_random_pseudo_bytes(16);
        $salted = '';
        $dx = '';

        // set the key(32) and iv(16) = 48
        while(strlen($salted) < 48) {
            $dx = hash('sha256', $dx.$pass.$salt, true);
            $salted .= $dx;
        }

        $key = substr($salted, 0, 32);
        $iv = substr($salted, 32, 16);

        $encrypted_data = openssl_encrypt($str, 'AES-256-CBC', $key, true, $iv);
        return base64_encode($salt . $encrypted_data);
    }
 
    function decrypt($str, $pass, $iv=null){
        // $iv = $this->init($pass, $iv);
        // $str = base64_decode($str);
        // $decrypted = mdecrypt_generic($this->td, $str);
        // return $decrypted;

        $data = base64_decode($str);
        $salt = substr($data, 0, 16);
        $ct = substr($data, 16);

        $rounds = 3;
        $data00 = $pass.$salt;
        $hash = array();
        $hash[0] = hash('sha256', $data00, true);
        $result = $hash[0];

        for ($i = 1; $i < $rounds; $i++) {
            $hash[$i] = hash('sha256', $hash[$i -1].$data00, true);
            $result .= $hash[$i];
        }
        $key = substr($result, 0, 32);
        $iv = substr($result, 32, 16);

        return openssl_decrypt($ct, 'AES-256-CBC', $key, true, $iv);
    }

$enc=encrypt('hichewL0chew', 'hichewL0chew');
echo $enc . "\n";
$dec=decrypt($enc, 'hichewL0chew');
echo $dec . "\n";
