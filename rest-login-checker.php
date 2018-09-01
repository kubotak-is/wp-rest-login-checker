<?php
/*
Plugin Name: REST-API Login Checker
Plugin URI: none
Description: REST-APIでログインチェックできるプラグイン
Author: kubotak
Version: 1.0
Author URI: https://github.com/kubotak-is
*/
const COOKIE_NAME = 'wp-rest-login-checker';
const ENCRYPT_KEY = 'default-pass'; // 任意の文字列
const ALGO        = 'AES-256-CBC';

add_action('rest_api_init', function() {
    register_rest_route('v1', '/login-check', [
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'login_check_func',
    ]);
});

function login_check_func(WP_REST_Request $request) {
    $cookie   = $_COOKIE[COOKIE_NAME];
    $response = new WP_REST_Response();
    $user     = surviveUserId($cookie);
    $userData = new WP_User($user);
    $token    = wp_get_session_token();
    $i        = wp_nonce_tick();
    $nonce    = substr(wp_hash("{$i}|wp_rest|{$userData->ID}|{$token}", 'nonce'), -12, 10);
    $response->set_data([
        "result" => $userData->ID !== 0,
        "user"   => $userData,
        "nonce"  => $userData->ID !== 0 ? $nonce : '',
    ]);
    $response->set_status($userData->ID !== 0 ? 200 : 401);
    return $response;
}

add_action('set_current_user', function() {
    if (is_admin() && is_user_logged_in()) {
        if (isset($_COOKIE[COOKIE_NAME])) {
            // すでにセット済みの場合はスルー
            return;
        }
        $time = time()+60*60*24*30;
        $user = wp_get_current_user();
        if (!empty($user) && $user->ID !== 0) {
            setcookie(
                COOKIE_NAME,
                encrypt("{$user->ID}.{$time}"),
                $time,
                '/'
            );
        }
    }
});

add_action('wp_logout', function() {
    if (isset($_COOKIE[COOKIE_NAME])) {
        unset($_COOKIE[COOKIE_NAME]);
        setcookie(
            COOKIE_NAME,
            '',
            time()-60,
            '/'
        );
    }
});

/**
 * @param string $cookie
 * @return int
 */
function surviveUserId($cookie) {
	$decrypt = decrypt($cookie);
	list($user, $time) = explode(".", $decrypt);
	if (time() > (int) $time) {
		return 0;
	}
	return (int) $user;
}

/**
 * @param int $length
 * @return string
 */
function getRandomStr($length){
    $chars = implode('', array_merge(range('a', 'z'), range('A', 'Z'), range('0', '9')));
    $str   = '';
    for ($i = 0; $i < $length; ++$i) {
        $str .= $chars[mt_rand(0, 61)];
    }
    return $str;
}

/**
 * @param string $value
 * @return string
 */
function encrypt($value) {
    $iv_size   = openssl_cipher_iv_length(ALGO);
    $iv        = getRandomStr($iv_size);
    $encrypted = openssl_encrypt(
        $value,
        ALGO,
        ENCRYPT_KEY,
        OPENSSL_RAW_DATA,
        $iv
    );

    return $iv . $encrypted;
}

/**
 * @param string $value
 * @return string
 */
function decrypt($value) {
    $iv_size   = openssl_cipher_iv_length(ALGO);
    $iv        = substr($value, 0, $iv_size);
    $encrypted = substr($value, $iv_size);

    if (strlen($iv) !== $iv_size) {
        return '';
    }
    
    return openssl_decrypt(
        $encrypted,
        ALGO,
        ENCRYPT_KEY,
        OPENSSL_RAW_DATA,
        $iv
    );
}
