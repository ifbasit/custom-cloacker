<?php
/*
* Title: A Custom Cloacker Which Use Fingerprint and IPQualityScore APIs (NON WORDPRESS)
* Author: Abdul Basit (ifbasit@gmail.com)
*/
// ==== BASIC SETTINGS ====

$settings = array(
    'switch'            => 'ON', // ON|OFF
    'use_ipqs'          => true,
    'use_fingerprint'   => true,
    'target_country'    => 'PK',
    'mode'              => 'prod', // dev|prod
    'the_money_page'    => 'money-page.php',
    'the_safe_page'     => 'safe-page.php',
    'source_camp_id'    => '21516269021',
    'campaign_filter'   => false,
    'gclid'             => false, // validate gclid?
    'one_visit_per_ip'  => false
);

// ==========================

// ==== CLOAKER VARIABLES (DO NOT UPDATE) ====

$cloacker = array(
    'valid_ipqs'    => false,
    'valid_fp'      => false,
    'use_services'  => true,
    'country_check' => true,
    'ip_address_check' => true,
    'check_gclid' => get_custom_settings('gclid'),
    'log_error' => array(
        'error' => array()
    ),
    'log_visit' => array(
        'meta' => array(),
        'ipqs' => array(),
        'fingerprint' => array()
    ),
    'ip_address'    => '',
    'user_language' => '',
    'query_params'  => '',
    'domain'        => '',
    'country'       => '',
    'referrer'      => '',
    'credentials'   => array(
        'ipinfo'        => 'bc8e414a4c2162a',
        'ipqs'          => 'hu8RQvvWL9JEqruGoyKr57slx8gD05p7b',
        'fingerprint'   => 'mbwEohL5ir6sSlrrQbni1'
    )
);
// ==========================


set_cloacker('ip_address', get_client_ip());
set_cloacker('country', get_user_country( get_cloacker('ip_address') ));
set_cloacker('user_language', get_user_language());
set_cloacker('query_params', http_build_query($_GET) );
set_cloacker('referrer', get_referrer() );
set_cloacker('domain', $_SERVER['HTTP_HOST']);



// ==========================

if(get_custom_settings('switch') == 'OFF'){
    set_cloacker('use_services', false);
    set_cloacker('ip_address_check', false);
    render_safe_page(true, false);
}

if(get_custom_settings('mode') == 'dev'){
    set_cloacker('use_services', false);
    set_cloacker('ip_address_check', false);
    set_cloacker('country_check', false);
    array_push($cloacker['log_visit']['meta'], ['mode' => 'dev']);
}


if(get_cloacker('ip_address_check')){
    if( is_ip_blocked(get_cloacker('ip_address')) ) {
        set_meta('blocked_exists', 'blocked');
        set_cloacker('use_services', false);
        set_cloacker('country_check', false);
        set_custom_settings('one_visit_per_ip', false);
    }
}

if(get_custom_settings('one_visit_per_ip')){
    if( is_ip_exists(get_cloacker('ip_address')) ) {
        set_meta('ip_exists', 'blocked');
        set_cloacker('use_services', false);
        set_cloacker('country_check', false);
    }
}

if (get_custom_settings('campaign_filter')) {
    $source_id = get_custom_settings('source_camp_id');

    $campaignIdValid = isset($_GET['CampaignId']) && $_GET['CampaignId'] === $source_id;
    $campaignidValid = isset($_GET['campaignid']) && $_GET['campaignid'] === $source_id;

    if (!$campaignIdValid && !$campaignidValid) {
        set_meta('invalid_campaign_id', 'blocked');
        set_cloacker('use_services', false);
        set_cloacker('country_check', false);
        set_cloacker('ip_address_check', false);
        set_cloacker('check_gclid', false);
    }
}

if (get_custom_settings('gclid') && get_cloacker('check_gclid')) {
    if (!isset($_GET['gclid'])) {
        set_meta('missing_gclid', 'blocked');
        set_cloacker('use_services', false);
        set_cloacker('country_check', false);
        set_cloacker('ip_address_check', false);
    }
}


if(get_cloacker('country_check')){
    if(get_cloacker('country') !== get_custom_settings('target_country')){
        set_cloacker('use_services', false);
        set_meta('invalid_country', 'blocked');
    } 

    if(get_cloacker('country') == get_custom_settings('target_country')){
        show_loading_gif();
    }
}



$ipqs_block_reason = '';
$fp_block_reason = '';
// ==== IPQUALITYSCORE SECTION ====
if (get_cloacker('use_services')) {
    $key = get_cloacker('credentials')['ipqs'];
    $ip = get_cloacker('ip_address');
    $user_agent = $_SERVER['HTTP_USER_AGENT'];

    $parameters = [
        'user_agent' => $user_agent,
        'user_language' => get_user_language(),
        'strictness' => 0,
        'allow_public_access_points' => 'true',
        'lighter_penalties' => 'false'
    ];

    $formatted_parameters = http_build_query($parameters);
    $url = "https://www.ipqualityscore.com/api/json/ip/{$key}/{$ip}?{$formatted_parameters}";

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_FOLLOWLOCATION => 1,
        CURLOPT_CONNECTTIMEOUT => 5
    ]);
    $json = curl_exec($curl);
    curl_close($curl);

    $result = json_decode($json, true);

    if (isset($result['success']) && $result['success'] === true) {
        if (
            $result['fraud_score'] > 50 ||
            $result['proxy'] !== false ||
            $result['vpn'] !== false ||
            $result['tor'] !== false  ||
            $result['bot_status'] !== false ||
            $result['is_crawler'] !== false 
        ) {

            if($result['fraud_score'] > 50){
                $ipqs_block_reason = 'fraud_score';               
            } else if($result['proxy']){
                $ipqs_block_reason = 'proxy';
            } else if($result['vpn']) {
                $ipqs_block_reason = 'vpn';
            } else if($result['tor']){
                $ipqs_block_reason = 'tor';
            } else if($result['bot_status']){
                $ipqs_block_reason = 'bot_status';
            } else if($result['is_crawler']){
                $ipqs_block_reason = 'is_crawler';                
            }

            $cloacker['log_visit']['ipqs'] =  [
                    'fraud_score' => $result['fraud_score'] ?? null,
                    'proxy' => $result['proxy'] ?? null,
                    'vpn' => $result['vpn'] ?? null,
                    'tor' => $result['tor'] ?? null,
                    'bot_status' => $result['bot_status'] ?? null,
                    'is_crawler' => $result['is_crawler'] ?? null,
                    'browser' => $result['browser'] ?? null,
                    'country_code' => $result['country_code'] ?? null,
                    'message' => $result['message'] ?? null
                ];

            set_cloacker('valid_ipqs', false);

        } else {
            $cloacker['log_visit']['ipqs'] =  [
                    'fraud_score' => $result['fraud_score'] ?? null,
                    'proxy' => $result['proxy'] ?? null,
                    'vpn' => $result['vpn'] ?? null,
                    'tor' => $result['tor'] ?? null,
                    'bot_status' => $result['bot_status'] ?? null,
                    'is_crawler' => $result['is_crawler'] ?? null,
                    'browser' => $result['browser'] ?? null,
                    'country_code' => $result['country_code'] ?? null,
                    'message' => $result['message'] ?? null
                ];
            set_cloacker('valid_ipqs', true);

        }
    } else {
        $cloacker['log_error']['meta'][] = [
            'source' => 'ipqs',
            'datetime' => date('c'),
            'error' => $result ?: 'Empty or invalid response',
            'query_params' => $query_params
        ];
        set_cloacker('valid_ipqs', false);
        log_error($log_error);
    }

    // ==== FINGERPRINTJS SECTION ====
?>
    <script>
    const currentPath = window.location.pathname;
    const domain = "<?php echo get_cloacker('domain'); ?>";

    import(`https://metrics.${domain}/web/v3/hqfUFhcCESRwuA2uuzQR`)
    .then(FingerprintJS => FingerprintJS.load({
        endpoint: [
        `https://metrics.${domain}`,
        FingerprintJS.defaultEndpoint
        ],
        region: "eu"
    }))
    .then(fp => fp.get({ extendedResult: true }))
    .then(result => {
        const formData = new FormData();
        formData.append('request_id', result.requestId);
        formData.append('visitor_id', result.visitorId);
        formData.append('path', currentPath);

        fetch(window.location.href, {
        method: 'POST',
        body: formData
        })
        .then(response => response.text())
        .then(html => {
        document.open();
        document.write(html);
        document.close();
        });
    });
    </script>
    <?php

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $request_id = $_POST['request_id'] ?? null;
        $visitor_id = $_POST['visitor_id'] ?? null;
        $path = $_POST['path'] ?? '/';

        if (!$request_id || !$visitor_id) {
            set_meta('fingerprint_visitor_request_id_none', 'blocked');
            set_cloacker('valid_fp', false);
        }

        $url = "https://eu.api.fpjs.io/events/{$request_id}";
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Auth-API-Key: '. get_cloacker('credentials')['fingerprint'],
                'Accept: application/json'
            ]
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);

        if (isset($data['error'])) {

            $cloacker['log_error']['meta'][] = [
                'source' => 'fingerprint',
                'datetime' => date('c'),
                'error' => $data['error']['message'],
                'query_params' => get_cloacker('query_params')
            ];
            set_cloacker('valid_fp', false);
            log_error($log_error);
        } else {
            $bot_result = $data['products']['botd']['data']['bot']['result'] ?? 'notDetected';
            $suspect_score = $data['products']['suspectScore']['data']['result'] ?? 0;
            $devtools_result = $data['products']['developerTools']['data']['result'] ?? false;
            $confidence_score = $data['products']['identification']['data']['confidence']['score'] ?? false;
            $incognito = $data['products']['identification']['data']['incognito']['score'] ?? false;
            $anomaly_score = $data['products']['tampering']['data']['anomalyScore'] ?? false;
            $anti_detect_browser = $data['products']['tampering']['data']['antiDetectBrowser'] ?? false;

            if (
                in_array($bot_result, ['goodBot', 'badBot']) ||
                $suspect_score > 3 ||
                $devtools_result === true
            ) {

                if($suspect_score > 3){
                    $fp_block_reason = 'suspect_score';
                } else if($devtools_result === true){
                    $fp_block_reason = 'devtools_result';
                }
                $cloacker['log_visit']['fingerprint'] =  [
                        'ip' => $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'],
                        'timestamp' => date('c'),
                        'source' => 'fpjs',
                        'visitor_id' => $visitor_id,
                        'bot_result' => $bot_result,
                        'suspect_score' => $suspect_score,
                        'devtools' => $devtools_result,
                        'confidence_score' => $confidence_score,
                        'incognito' => $incognito,
                        'anomaly_score' => $anomaly_score,
                        'anti_detect_browser' => $anti_detect_browser
                    ];
                set_cloacker('valid_fp', false);
            } else {
            $cloacker['log_visit']['fingerprint'] =  [
                    'ip' => $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'],
                    'timestamp' => date('c'),
                    'source' => 'fpjs',
                    'visitor_id' => $visitor_id,
                    'bot_result' => $bot_result,
                    'suspect_score' => $suspect_score,
                    'devtools' => $devtools_result,
                    'confidence_score' => $confidence_score,
                    'incognito' => $incognito,
                    'anomaly_score' => $anomaly_score,
                    'anti_detect_browser' => $anti_detect_browser
                ];
                set_cloacker('valid_fp', true);
                
            }
        }

        $use_fingerprint = get_custom_settings('use_fingerprint');
        $use_ipqs = get_custom_settings('use_ipqs');

        $valid_fp = get_cloacker('valid_fp');
        $valid_ipqs = get_cloacker('valid_ipqs');

        if ($use_fingerprint && !$valid_fp) {
            $reason = !empty($fp_block_reason) ? $fp_block_reason : 'Fingerprint validation failed';
            set_meta($reason, 'blocked');
        } elseif ($use_ipqs && !$valid_ipqs) {
            $reason = !empty($ipqs_block_reason) ? $ipqs_block_reason : 'IPQS validation failed';
            set_meta($reason, 'blocked');
        } else {
            set_meta('OK', 'Success');
        }
        
        log_visit($cloacker['log_visit']);


        if ($use_fingerprint && $use_ipqs) {
            if (!$valid_fp || !$valid_ipqs) {
                render_safe_page(false, false);
            } else {
                render_money_page();
            }

        } elseif ($use_fingerprint && !$use_ipqs) {
            if ($valid_fp) {
                render_money_page();
            } else {
                render_safe_page(false, false);
            }

        } elseif ($use_ipqs && !$use_fingerprint) {
            if ($valid_ipqs) {
                render_money_page();
            } else {
                render_safe_page(false, false);
            }

        } else {
            render_safe_page(false, false);
        }
    }
}
 else {
    log_visit($cloacker['log_visit']);
    render_safe_page(false, false);
}


function get_client_ip() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    }

    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip_list = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ip_list[0]);
    }

    return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
}


function show_loading_gif() {
    echo '
    <div id="loader-wrapper">
      <div id="the-gif" style="
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100vh;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: white;
        z-index: 9999;
      ">
        <img src="/loading_2.gif" style="height: 70px;" />
      </div>
    </div>
    <script>
      document.body.style.overflow = "hidden";
      setTimeout(() => {
        const wrapper = document.getElementById("loader-wrapper");
        if (wrapper) wrapper.remove();
        document.body.style.overflow = "";
      }, 4000);
    </script>';
}

function get_user_language(){
    if (empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) return 'unknown';
    
    $langs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
    if (count($langs) === 0) return 'unknown';
    
    $primary = explode(';', $langs[0])[0];
    $primary = trim($primary);
    
    if (preg_match('/^[a-z]{1,8}(-[a-z]{1,8})?$/i', $primary)) return strtolower($primary);
    return 'unknown';
}

function get_user_country($ip_address){
    global $cloacker;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://ipinfo.io/{$ip_address}?token={$cloacker['credentials']['ipinfo']}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $response = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($response, true);
    return $data['country'];
}

function get_referrer(){
   return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
}

function render_safe_page($exit = false, $log = false) {
    global $cloacker;
    require __DIR__ . '/'.get_custom_settings('the_safe_page');
    if($log){
        array_push($cloacker['log_visit'], $log);
    }
    if($exit) exit;
}

function render_money_page($exit = false){
    require __DIR__ . '/'.get_custom_settings('the_money_page');
    if($exit) exit;
}

function _uniqid($length = 6) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $uniqueId = '';
    for ($i = 0; $i < $length; $i++) {
        $uniqueId .= $characters[random_int(0, $charactersLength - 1)];
    }
    return $uniqueId;
}



function set_cloacker($key, $value){
    global $cloacker;
    $cloacker[$key] = $value;
}

function set_custom_settings($key, $value){
    global $settings;
    $settings[$key] = $value;
}

function get_cloacker($key){
    global $cloacker;
    return $cloacker[$key];
}

function get_custom_settings($key){
    global $settings;
    return $settings[$key];
}

function set_meta($reason, $status){
    global $cloacker;
    $cloacker['log_visit']['meta'] =  [
                    'mode'   => get_custom_settings('mode'),
                    'reason' => $reason,
                    'status' => $status,
                    'datetime' => date('c'),
                    'ip' => get_cloacker('ip_address'),
                    'geo' => get_cloacker('country'),
                    'language' => get_cloacker('user_language'),
                    'query_params' => get_cloacker('query_params'),
                    'referrer' => get_cloacker('referrer'),
                    'uniqid' => _uniqid()
                ];
}

function log_visit($data) {
    log_json('visit.log', $data);
}

function log_error($data) {
    log_json('error.log', $data);
}

function log_json($filename, $data) {
    $log_dir = __DIR__ . '/logs';
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0777, true);
    }

    $filepath = "$log_dir/$filename";
    $entries = [];

    if (file_exists($filepath)) {
        $json = file_get_contents($filepath);
        $decoded = json_decode($json, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $entries = $decoded;
        }
    }

    $entries[] = $data;
    file_put_contents($filepath, json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function is_ip_blocked($ip) {
    $log_file = __DIR__ . '/logs/visit.log';
    if (!file_exists($log_file)) {
        return false;
    }

    $json = file_get_contents($log_file);
    $entries = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($entries)) {
        return false;
    }

    foreach ($entries as $entry) {
        if (!empty($entry['meta']['ip']) && trim($entry['meta']['ip']) == trim($ip)) {
            if ($entry['meta']['reason'] !== 'OK') {
                return $entry;
            }
        }
    }

    return false;
}

function is_ip_exists($ip) {
    $log_file = __DIR__ . '/logs/visit.log';
    if (!file_exists($log_file)) {
        return false;
    }

    $json = file_get_contents($log_file);
    $entries = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($entries)) {
        return false;
    }

    foreach ($entries as $entry) {
        if (!empty($entry['meta']['ip']) && trim($entry['meta']['ip']) == trim($ip)) {
            return $entry;
        }
    }

    return false;
}
?>
