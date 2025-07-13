<?php
/*
* Title: A Custom Cloacker Which Use Fingerprint and IPQualityScore APIs (NON WORDPRESS)
* Author: Abdul Basit (ifbasit@gmail.com)
*/
// ==== BASIC SETTINGS ====

$settings = array(
    'switch'            => 'ON', // ON|OFF
    'target_country'    => 'PK',
    'mode'              => 'prod', // dev|prod
    'the_money_page'    => '89479830-0dd0-43e4-aced-448ee425516f.php',
    'the_safe_page'     => 'white-index.php',
    'source_camp_id'    => '21516269021',
    'campaign_filter'   => false,
    'gclid'             => false, // validate gclid?
    'one_visit_per_ip'  => false
);

// ==========================

// ==== CLOAKER VARIABLES (DO NOT UPDATE) ====

$cloacker = array(
    'fp'  => array(
        'valid'     => false,
        'response'  => array()
    ),
    'ipqs'  => array(
        'valid'     => false,
        'response'  => array()
    ),
    'use_services'  => true,
    'country_check' => true,
    'ip_address_check' => true,
    'check_gclid' => get_custom_settings('gclid'),
    'log_error' => array(
        'meta' => array()
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

if (isset($_GET['ipfp_check']) && $_GET['ipfp_check'] === 'true2206') {
    set_custom_settings('mode', 'dev');
}
// ==========================

if(get_custom_settings('switch') == 'OFF'){
    set_cloacker('ip_address_check', false);
    set_meta('OK', 'OFF');
    log_visit($cloacker['log_visit']);
    render_safe_page();
    exit;
}

if(get_custom_settings('mode') == 'dev'){
    set_cloacker('ip_address_check', false);
    set_cloacker('country_check', false);
    set_meta('OK', 'dev');
    log_visit($cloacker['log_visit']);
    render_money_page();
    exit;
}

if(get_cloacker('ip_address_check')){
    if( is_ip_blocked(get_cloacker('ip_address')) ) {
        set_meta('blocked_exists', 'blocked');
        set_cloacker('country_check', false);
        set_custom_settings('one_visit_per_ip', false);
        log_visit($cloacker['log_visit']);
        render_safe_page();
        exit;
    }
}

if(get_custom_settings('one_visit_per_ip')){
    if( is_ip_exists(get_cloacker('ip_address')) ) {
        set_meta('ip_exists', 'blocked');
        set_cloacker('country_check', false);
        log_visit($cloacker['log_visit']);
        render_safe_page();
        exit;
    }
}

if (get_custom_settings('campaign_filter')) {
    $source_id = get_custom_settings('source_camp_id');

    $campaignIdValid = isset($_GET['CampaignId']) && $_GET['CampaignId'] === $source_id;
    $campaignidValid = isset($_GET['campaignid']) && $_GET['campaignid'] === $source_id;

    if (!$campaignIdValid && !$campaignidValid) {
        set_meta('invalid_campaign_id', 'blocked');
        set_cloacker('country_check', false);
        set_cloacker('ip_address_check', false);
        set_cloacker('check_gclid', false);
        log_visit($cloacker['log_visit']);
        render_safe_page();
        exit;
    }
}

if (get_custom_settings('gclid') && get_cloacker('check_gclid')) {
    if (!isset($_GET['gclid'])) {
        set_meta('missing_gclid', 'blocked');
        set_cloacker('country_check', false);
        set_cloacker('ip_address_check', false);
        log_visit($cloacker['log_visit']);
        render_safe_page();
        exit;
    }
}


if(get_cloacker('country_check')){
    if(get_cloacker('country') !== get_custom_settings('target_country')){
        set_meta('invalid_country', 'blocked');
        log_visit($cloacker['log_visit']);
        render_safe_page();
        exit;
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

    array_push($cloacker['ipqs']['response'], $result);


    // ==== FINGERPRINTJS SECTION ====
?>
    <script>
    var the_path = window.location.pathname;
    var domain = "<?php echo get_cloacker('domain'); ?>";
    var referrer = "<?php echo get_cloacker('referrer'); ?>";
        
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
        formData.append('path', the_path);
        formData.append('referrer', referrer);

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
        $referrer = $_POST['referrer'] ?? null;
        $path = $_POST['path'] ?? '/';

        if (!$request_id || !$visitor_id) {
            set_meta('fingerprint_visitor_request_id_none', 'blocked');
            log_error($cloacker['log_error']);
                if (ob_get_level()) {
                                ob_end_clean();
                            }

                render_safe_page();
                exit;
        }
        set_cloacker('referrer', $referrer);
        
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
        array_push($cloacker['fp']['response'], $data);

        $ipqs_data = $cloacker['ipqs']['response'][0];
        $fp_data = $cloacker['fp']['response'][0];


        if (isset($ipqs_data['success']) && $ipqs_data['success'] === true) { // checks any error from ipqs
            if (isset($fp_data['error'])) { // checks any error from fingeprint
                $cloacker['log_error']['meta'][] = [
                    'source' => 'fingerprint',
                    'datetime' => date('c'),
                    'error' => $data['error']['message'],
                    'query_params' => get_cloacker('query_params')
                ];
                log_error($cloacker['log_error']);
                if (ob_get_level()) {
                                ob_end_clean();
                            }

                render_safe_page();
                exit;
            } else {
                $ipqs_proxy = $ipqs_data['proxy'];
                $ipqs_vpn = $ipqs_data['vpn'];
                $ipqs_tor = $ipqs_data['tor'];
                $ipqs_fraud_score = $ipqs_data['fraud_score'];
                $ipqs_bot_status = $ipqs_data['bot_status'];
                $ipqs_crawler = $ipqs_data['is_crawler'];
                $ipqs_browser = $ipqs_data['ipqs_browser'];
                $ipqs_country_code = $ipqs_data['ipqs_country_code'];
                $ipqs_message = $ipqs_data['ipqs_message'];


                $fp_bot_result = $fp_data['products']['botd']['data']['bot']['result'] ?? 'notDetected';
                $fp_devtools = $fp_data['products']['developerTools']['data']['result'] ?? false;
                $fp_suspect_score = $fp_data['products']['suspectScore']['data']['result'] ?? 0;
                $fp_confidence_score = $fp_data['products']['identification']['data']['confidence']['score'] ?? false;
                $fp_incognito = $fp_data['products']['identification']['data']['incognito']['score'] ?? false;
                $fp_anomaly_score = $fp_data['products']['tampering']['data']['anomalyScore'] ?? false;
                $fp_anti_detect_browser = $fp_data['products']['tampering']['data']['antiDetectBrowser'] ?? false;


                $cloacker['log_visit']['ipqs'] =  [
                    'fraud_score' => $ipqs_fraud_score,
                    'proxy' => $ipqs_proxy ?? null,
                    'vpn' => $ipqs_vpn ?? null,
                    'tor' => $ipqs_tor ?? null,
                    'bot_status' => $ipqs_bot_status ?? null,
                    'is_crawler' => $ipqs_crawler ?? null,
                    'browser' => $ipqs_browser ?? null,
                    'country_code' => $ipqs_country_code ?? null,
                    'message' => $ipqs_message ?? null
                ];

                $cloacker['log_visit']['fingerprint'] =  [
                        'ip' => $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'],
                        'timestamp' => date('c'),
                        'source' => 'fpjs',
                        'visitor_id' => $visitor_id,
                        'bot_result' => $fp_bot_result,
                        'suspect_score' => $fp_suspect_score,
                        'devtools' => $fp_devtools,
                        'confidence_score' => $fp_confidence_score,
                        'incognito' => $fp_incognito,
                        'anomaly_score' => $fp_anomaly_score,
                        'anti_detect_browser' => $fp_anti_detect_browser
                    ];

                // Rule A: proxy/vpn/tor = true, fraud_score > 90, and bot/crawler = true
                $A = ($ipqs_proxy || $ipqs_vpn || $ipqs_tor) && $ipqs_fraud_score > 90 && ($ipqs_bot_status || $ipqs_crawler);

                // Rule B: FingerprintJS suspect score > 7
                $B = $fp_suspect_score > 7;

                // Step 1: Bot or DevTools detected
                if ($fp_bot_result !== "notDetected" || $fp_devtools === true) {
                    set_meta('Bot or DevTools detected', 'blocked');
                    log_visit($cloacker['log_visit']);
                    if (ob_get_level()) {
                                ob_end_clean();
                            }

                    render_safe_page();
                    exit;
                }

                // Step 2: Evaluate based on A and B
                if ($A || $B) {
                    if ($A) {
                        if ($fp_suspect_score <= 4) {
                            set_meta('OK', 'Success');
                            log_visit($cloacker['log_visit']);
                            if (ob_get_level()) {
                                ob_end_clean();
                            }

                            render_money_page();
                            exit;
                        } else {
                            set_meta('FP Suspect Score', 'blocked');
                            log_visit($cloacker['log_visit']);
                            if (ob_get_level()) {
                                ob_end_clean();
                            }

                            render_safe_page();
                            exit;
                        }
                    }

                    if ($B) {
                        if (
                            $fp_suspect_score <= 14 &&
                            !$ipqs_proxy &&
                            !$ipqs_vpn &&
                            !$ipqs_tor &&
                            !$ipqs_bot_status &&
                            !$ipqs_crawler
                        ) {
                            set_meta('OK', 'Success');
                            log_visit($cloacker['log_visit']);
                            if (ob_get_level()) {
                                ob_end_clean();
                            }

                            render_money_page();
                            exit;
                        } else {
                            set_meta('FP Suspect Score, Proxy, VPN, TOR, Bot or Crawler', 'blocked');
                            log_visit($cloacker['log_visit']);
                            if (ob_get_level()) {
                                ob_end_clean();
                            }

                            render_safe_page();
                            exit;
                        }
                    }
                } else {
                    set_meta('OK', 'Success');
                    log_visit($cloacker['log_visit']);
                    if (ob_get_level()) {
                        ob_end_clean();
                    }
                    render_money_page();
                    exit;
                }

            }


        } else {
             $cloacker['log_error']['meta'][] = [
                'source' => 'ipqs',
                'datetime' => date('c'),
                'error' => $ipqs_data ?: 'Empty or invalid response',
                'query_params' => get_cloacker('query_params')
            ];
            log_error($cloacker['log_error']);
            if (ob_get_level()) {
                ob_end_clean();
            }
            render_safe_page();
            exit;
        }

    }
} else {
    log_visit($cloacker['log_visit']);
    if (ob_get_level()) {
        ob_end_clean();
    }
    render_safe_page();
    exit;
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

function render_safe_page() {
    require __DIR__ . '/'.get_custom_settings('the_safe_page');
}

function render_money_page(){
    require __DIR__ . '/'.get_custom_settings('the_money_page');
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
