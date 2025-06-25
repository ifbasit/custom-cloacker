<?php
/*
* Title: A Custom Cloacker Which Use Fingerprint and IPQualityScore APIs
* Author: Abdul Basit
*/
// ==== BASIC SETTINGS ====

$settings = array(
    'switch'            => 'ON', // ON|OFF
    'use_ipqs'          => true,
    'use_fingerprint'   => true,
    'target_country'    => 'GB',
    'mode'              => 'prod', // dev|prod
    'the_money_page'    => 'index.php',
    'is_money_page_wp'  => true,
    'source_camp_id'    => '21516269021',
    'campaign_filter'   => true,
    'gclid'             => true, // validate gclid?
);

// ==========================

// ==== CLOAKER VARIABLES (DO NOT UPDATE) ====

$cloacker = array(
    'money_page'    => false,
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

if (get_custom_settings('campaign_filter')) {
    $source_id = get_custom_settings('source_camp_id');

    $campaignIdValid = isset($_GET['CampaignId']) && $_GET['CampaignId'] === $source_id;
    $campaignidValid = isset($_GET['campaignid']) && $_GET['campaignid'] === $source_id;

    if (!$campaignIdValid && !$campaignidValid) {
        array_push($cloacker['log_visit']['meta'], [
            'mode'        => get_custom_settings('mode'),
            'reason'      => 'invalid_campaign_id',
            'status'      => 'blocked',
            'datetime'    => date('c'),
            'ip'          => get_cloacker('ip_address'),
            'geo'         => get_cloacker('country'),
            'language'    => get_cloacker('user_language'),
            'query_params'=> get_cloacker('query_params'),
            'referrer'    => get_cloacker('referrer'),
            'uniqid'      => _uniqid()
        ]);

        set_settings('use_ipqs', false);
        set_settings('use_fingerprint', false);
        set_cloacker('money_page', false);
        set_cloacker('country_check', false);
        set_cloacker('ip_address_check', false);
        set_cloacker('check_gclid', false);
    }
}

if (get_custom_settings('gclid') && get_cloacker('check_gclid')) {
    if (!isset($_GET['gclid'])) {
        array_push($cloacker['log_visit']['meta'], [
            'mode'         => get_custom_settings('mode'),
            'reason'       => 'missing_gclid',
            'status'       => 'blocked',
            'datetime'     => date('c'),
            'ip'           => get_cloacker('ip_address'),
            'geo'          => get_cloacker('country'),
            'language'     => get_cloacker('user_language'),
            'query_params' => get_cloacker('query_params'),
            'referrer'     => get_cloacker('referrer'),
            'uniqid'       => _uniqid()
        ]);

        set_settings('use_ipqs', false);
        set_settings('use_fingerprint', false);
        set_cloacker('money_page', false);
        set_cloacker('country_check', false);
        set_cloacker('ip_address_check', false);
    }
}



if(get_custom_settings('mode') == 'dev'){
    set_settings('use_ipqs', false);
    set_settings('use_fingerprint', false);
    set_cloacker('ip_address_check', false);
    set_cloacker('money_page', true);
    set_cloacker('country_check', false);
    array_push($cloacker['log_visit']['meta'], ['mode' => 'dev']);
}

if(get_custom_settings('switch') == 'OFF'){
    set_settings('use_ipqs', false);
    set_settings('use_fingerprint', false);
    set_cloacker('money_page', false);
    set_cloacker('ip_address_check', false);
    render_wp_page($_POST['path'] ?? '/', true, false);
}


if(get_cloacker('ip_address_check')){
    if( is_ip_blocked(get_cloacker('ip_address')) ) {
        array_push($cloacker['log_visit']['meta'], [
                        'mode'   => get_custom_settings('mode'),
                        'reason' => 'exists',
                        'status' => 'blocked',
                        'datetime' => date('c'),
                        'ip' => get_cloacker('ip_address'),
                        'geo' => get_cloacker('country'),
                        'language' => get_cloacker('user_language'),
                        'query_params' => get_cloacker('query_params'),
                        'referrer' => get_cloacker('referrer'),
                        'uniqid' => _uniqid()
                    ]);
        set_settings('use_ipqs', false);
        set_settings('use_fingerprint', false);
        set_cloacker('money_page', false);
        set_cloacker('country_check', false);
    }
}

if(get_cloacker('country_check')){
    if(get_cloacker('country') !== get_custom_settings('target_country')){
        set_settings('use_ipqs', false);
        set_settings('use_fingerprint', false);
        set_cloacker('money_page', false);

        array_push($cloacker['log_visit']['meta'], [
                        'mode'   => get_custom_settings('mode'),
                        'reason' => 'invalid_country',
                        'status' => 'blocked',
                        'datetime' => date('c'),
                        'ip' => get_cloacker('ip_address'),
                        'geo' => get_cloacker('country'),
                        'language' => get_cloacker('user_language'),
                        'query_params' => get_cloacker('query_params'),
                        'referrer' => get_cloacker('referrer'),
                        'uniqid' => _uniqid()
                    ]);
        set_settings('use_ipqs', false);
        set_settings('use_fingerprint', false);
        set_cloacker('money_page', false);
    } 

    if(get_cloacker('country') == get_custom_settings('target_country')){
        show_loading_gif();
    }
}



// ==== IPQUALITYSCORE SECTION ====
if (get_custom_settings('use_ipqs')) {
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

            $reason = '';
            if($result['fraud_score'] > 50){
                $reason = 'fraud_score';               
            } else if($result['proxy']){
                $reason = 'proxy';
            } else if($result['vpn']) {
                $reason = 'vpn';
            } else if($result['tor']){
                $reason = 'tor';
            } else if($result['bot_status']){
                $reason = 'bot_status';
            } else if($result['is_crawler']){
                $reason = 'is_crawler';                
            }

            array_push($cloacker['log_visit']['meta'], [
                    'mode'   => get_custom_settings('mode'),
                    'reason' => $reason,
                    'status' => 'blocked',
                    'datetime' => date('c'),
                    'ip' => get_cloacker('ip_address'),
                    'geo' => get_cloacker('country'),
                    'language' => get_cloacker('user_language'),
                    'query_params' => get_cloacker('query_params'),
                    'referrer' => get_cloacker('referrer'),
                    'uniqid' => _uniqid()
                ]);


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

            set_settings('use_fingerprint', false);
            set_cloacker('money_page', false);

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

            set_cloacker('money_page', true);
        }
    } else {
        $cloacker['log_error']['meta'][] = [
            'source' => 'ipqs',
            'datetime' => date('c'),
            'error' => $result ?: 'Empty or invalid response',
            'query_params' => $query_params
        ];
        log_error($log_error);
    }
}

// ==== FINGERPRINTJS SECTION ====
if (get_custom_settings('use_fingerprint')): ?>
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
<?php endif; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && get_custom_settings('use_fingerprint')) {
    $request_id = $_POST['request_id'] ?? null;
    $visitor_id = $_POST['visitor_id'] ?? null;
    $path = $_POST['path'] ?? '/';

    if (!$request_id || !$visitor_id) {
        array_push($cloacker['log_visit']['meta'], [
            'mode'   => get_custom_settings('mode'),
            'reason' => 'fingerprint_visitor_request_id_none',
            'status' => 'blocked',
            'datetime' => date('c'),
            'ip' => get_cloacker('ip_address'),
            'geo' => get_cloacker('country'),
            'language' => get_cloacker('user_language'),
            'query_params' => get_cloacker('query_params'),
            'referrer' => get_cloacker('referrer'),
            'uniqid' => _uniqid()
        ]);
        set_settings('use_ipqs', false);
        set_settings('use_fingerprint', false);
        set_cloacker('money_page', false);
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

            $reason = '';
            if($suspect_score > 3){
                $reason = 'suspect_score';
            } else if($devtools_result === true){
                $reason = 'devtools_result';
            }

            array_push($cloacker['log_visit']['meta'], [
                    'mode'   => get_custom_settings('mode'),
                    'reason' => $reason,
                    'status' => 'blocked',
                    'datetime' => date('c'),
                    'ip' => get_cloacker('ip_address'),
                    'geo' => get_cloacker('country'),
                    'language' => get_cloacker('user_language'),
                    'query_params' => get_cloacker('query_params'),
                    'referrer' => get_cloacker('referrer'),
                    'uniqid' => _uniqid()
                ]);


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
            set_cloacker('money_page', false);
           

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
            set_cloacker('money_page', true);
            
        }
    }

    if(get_cloacker('money_page')){
         array_push($cloacker['log_visit']['meta'], [
                        'mode'   => get_custom_settings('mode'),
                        'reason' => 'OK',
                        'status' => 'success',
                        'datetime' => date('c'),
                        'ip' => get_cloacker('ip_address'),
                        'geo' => get_cloacker('country'),
                        'language' => get_cloacker('user_language'),
                        'query_params' => get_cloacker('query_params'),
                        'referrer' => get_cloacker('referrer'),
                        'uniqid' => _uniqid()
                    ]);
        log_visit($cloacker['log_visit']);
        if(get_custom_settings('is_money_page_wp')){
                define('WP_USE_THEMES', true);
                $_SERVER['REQUEST_URI'] = '/';
                require __DIR__ . '/wp-blog-header.php';
            } else {
                render_money_page();
        }
        
    } else {
        log_visit($cloacker['log_visit']);
        render_wp_page($_POST['path'] ?? '/', false, false);
    }

    
}


if(get_cloacker('money_page') && !get_custom_settings('use_fingerprint')){
     array_push($cloacker['log_visit']['meta'], [
                    'mode'   => get_custom_settings('mode'),
                    'reason' => 'OK',
                    'status' => 'success',
                    'datetime' => date('c'),
                    'ip' => get_cloacker('ip_address'),
                    'geo' => get_cloacker('country'),
                    'language' => get_cloacker('user_language'),
                    'query_params' => get_cloacker('query_params'),
                    'referrer' => get_cloacker('referrer'),
                    'uniqid' => _uniqid()
                ]);
    log_visit($cloacker['log_visit']);
    if(get_custom_settings('is_money_page_wp')){
            define('WP_USE_THEMES', true);
            $_SERVER['REQUEST_URI'] = '/';
            require __DIR__ . '/wp-blog-header.php';
        } else {
            render_money_page();
    }
    
}

if(!get_cloacker('money_page') && !get_custom_settings('use_fingerprint')){
    log_visit($cloacker['log_visit']);
    render_wp_page($_POST['path'] ?? '/', false, false);
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

function render_wp_page($path = '/', $exit = false, $log = false) {
    global $cloacker;
    define('WP_USE_THEMES', true);
    $_SERVER['REQUEST_URI'] = $path;
    require __DIR__ . '/wp-blog-header.php';
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

function set_settings($key, $value){
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

function log_visit($data) {
    $log_dir = __DIR__ . '/logs';
    if (!file_exists($log_dir)) mkdir($log_dir, 0777, true);
    file_put_contents("$log_dir/visit.log", json_encode($data) . PHP_EOL, FILE_APPEND);
}

function log_error($data) {
    $log_dir = __DIR__ . '/logs';
    if (!file_exists($log_dir)) mkdir($log_dir, 0777, true);
    file_put_contents("$log_dir/error.log", json_encode($data) . PHP_EOL, FILE_APPEND);
}

function is_ip_blocked($ip) {
    $log_file = __DIR__ . '/logs/visit.log';
    if (!file_exists($log_file)) return false;

    $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $entry = json_decode($line, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            if (!empty($entry['meta'][0]['ip']) && trim($entry['meta'][0]['ip']) == trim($ip)) {
                if ($entry['meta'][0]['reason'] !== 'OK') {
                    return $entry;
                }
            }
        }
    }
    return false;
}

?>
