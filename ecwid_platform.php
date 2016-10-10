<?php

class EcwidPlatform {

	static public function esc_attr($value)
	{
		return htmlspecialchars($value, ENT_COMPAT | ENT_HTML401, 'UTF-8');
	}

	static public function esc_html($value)
	{
		return htmlspecialchars($value, ENT_COMPAT | ENT_HTML401, 'UTF-8');
	}

	static public function get_price_label()
	{
		return 'Price';
	}

	static public function fetch_url($url)
	{
        $timeout = 90;
        if (!function_exists('curl_init')) {
            return array(
                'code' => '0',
                'data' => 'The libcurl module isn\'t installed on your server. Please contact  your hosting or server administrator to have it installed.'
            );
        }

        $headers[] = 'Content-Type: application/json';
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HTTPGET, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $body  = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $result = array();
        if ($error) {
            return array(
                'code' => '0',
                'data' => "libcurl error($errno): $error"
            );
        }

        return array(
            'code' => $httpcode, 
            'data' => $body
        );
	}
}
