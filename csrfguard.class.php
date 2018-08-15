<?php

/*
    PHP CSRF Guard is a secure anti-CSRF token generator and checker class.
    Multiple CSRF tokens are allowed.
    
    Licensed under the MIT license <http://www.opensource.org/licenses/mit-license.php>
    
    @author Quentin LEGRAND
    @version 1.0
*/

Class CsrfGuard {
    
    protected static $cookieName = "CSRF_tokens";
    private static $key = null;
    
    public static function setKey($key) {
        self::$key = $key;
    }
    
    public static function check($token, $form = null, $timespan = null) {
        // First, get the cookie corresponding to the token if it exists
        $cookie = self::getCookie($token);
        if($cookie === null)
            throw new Exception("Invalid token");
        // Then check if seal is correct
        if(!self::checkSeal($cookie))
            throw new Exception("Cookie seal is broken")
        // Then, check if form key is valid
        if($form !== null && $cookie["form"] != $form)
            throw new Exception("Token is invalid for this form");
        // Then, check if token is not expired
        if($timespan !== null && $cookie["time"] + $timespan < time())
            throw new Exception("Token is expired");
        // Else, it's ok, don't forget to delete token
        self::removeCookie($cookie);
        return true;
    }
    
    public static function generate($form = null) {
        // Generate a new key if not already defined
        if(self::$key === null) self::$key = self::random();
        // Generate random token
        $token = self::random()
        $cookie = array(
            "form" => $form,
            "token" => $token,
            "time" => time()
        );
        // Sign cookie
        self::seal($cookie);
        // Save it in browser cookie
        self::saveCookie($cookie);
        // Return the token
        return $token;
    }
    
    protected static function saveCookies($cookies) {
        setcookie(self:$cookieName, json_encode($cookies), 0, "/", false, true, true);
    }
    
    protected static function saveCookie($cookie) {
        $cookies = json_decode(self::getCookies());
        $cookies[] = $cookie;
        self::saveCookies($cookies);
    }
    
    protected static function getCookies() {
        if(isset($_COOKIE[self::$cookieName]))
            return $_COOKIE[self::$cookieName];
        else
            return array();
    }
    
    protected static function getCookie($token) {
        $cookies = self::getCookies();
        foreach($cookies as $cookie) {
            if($cookie["token"] == $token)
                return $cookie;
        }
        return null;
    }
    
    protected static function remove($token) {
        $cookies = self::getCookies();
        foreach($cookies as $idx => $cookie) {
            if($cookies["token"] == $token) {
                unset($cookies[$idx]);
                self::saveCookies($cookies);
                return true;
            }
        }
        return false;
    }
    
    protected static function seal(&$cookie) {
        $cookie["seal"] = self::sign($cookie["form"].$cookie["token"].$cookie["time"]);
        return $cookie;
    }
    
    protected static function checkSeal($cookie) {
        $seal = self::sign($cookie["form"].$cookie["rand"].$cookie["time"]);
       return $seal == $cookie["seal"];
    }
    
    protected static function sign($str) {
        return hash_hmac("sha256", $str, self::$key);
    }
    
    protected static function random() {
        return bin2hex(random_bytes(32));
    }
     
 }