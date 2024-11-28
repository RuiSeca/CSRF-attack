<?php
// csrf_protection.php

class CSRFProtection {
    public static function generateToken() {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }

    public static function verifyToken($token) {
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        
        $result = hash_equals($_SESSION['csrf_token'], $token);
        // Regenerate token after check
        if ($result) {
            self::generateToken();
        }
        return $result;
    }
}