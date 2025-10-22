<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if (!function_exists('get_jwt_payload')) {
    /**
     * Fungsi untuk mendapatkan payload data dari token JWT
     * 
     * @param string $token Token JWT dari header Authorization
     * @return object|FALSE Payload data jika valid, false jika token invalid
     */
    function get_jwt_payload($token)
    {
        try {
            $decoded = JWT::decode($token, new Key($_ENV['SECRET_KEY'], 'HS256'));

            return $decoded;
        } catch (Exception $e) {
            return FALSE;
        }
    }
}

if (!function_exists('get_authorization_token')) {
    /**
     * Fungsi untuk mengambil token dari header Authorization
     * 
     * @return string|FALSE Token JWT jika ditemukan, false jika tidak ada
     */
    function get_authorization_token()
    {
        $CI = &get_instance();
        $headers = $CI->input->get_request_header('Authorization', TRUE);

        if ($headers && preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }

        return FALSE;
    }
}
