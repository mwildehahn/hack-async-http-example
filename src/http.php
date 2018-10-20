<?hh // strict

use namespace \HH\Lib\{Str, Vec, Dict, C};

type http_options_t = shape(
  ?'connect_timeout' => int,
  ?'connect_timeout_ms' => int,
  ?'follow_redirects' => int,
  ?'http_timeout' => int,
  ?'http_timeout_ms' => int,
  ?'outfile' => string,
);

type http_response_t = shape(
  'code' => int,
  'url' => string,
  'info' => http_curl_info_t,
  'body' => string,
  'rsp_len' => int,
);

type http_curl_info_t = shape(
  'url' => string,

  'connect_time' => float,
  'content_type' => string,
  'http_code' => int,
  'header_size' => int,
  'request_size' => int,
  'request_header' => string,
  'redirect_count' => int,
  'size_download' => int,
  'curl_error_code' => int,
  'curl_error_msg' => string,
);

async function http_request(
  string $url,
  dict<string, string> $headers = dict[],
  http_options_t $options = shape(),
): Awaitable<http_response_t> {
  $ch = _http_get_curl_handle($url, $headers, $options);
  echo "fetching: $url\n";
  $results = await _http_curl_multi(vec[$ch], $options);
  echo "done fetching: $url\n";
  return $results[$url];
}

/**
* Return a cURL handle, supporting various options.
*/
function _http_get_curl_handle(
  string $url,
  dict<string, string> $headers = dict[],
  http_options_t $options = shape(),
): resource {
  $default_timeout = 10;
  $default_follow_redirects = true;

  $headers_prepped = _http_prepare_outgoing_headers($headers);
  $ch = curl_init();

  curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
  curl_setopt($ch, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);

  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers_prepped);
  curl_setopt($ch, CURLOPT_URL, $url);

  $connect_timeout_ms = $options['connect_timeout_ms'] ?? null;
  if (!is_null($connect_timeout_ms)) {
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $connect_timeout_ms);
  } else {
    curl_setopt(
      $ch,
      CURLOPT_CONNECTTIMEOUT,
      $options['connect_timeout'] ?? $default_timeout,
    );
  }

  $http_timeout_ms = $options['http_timeout_ms'] ?? null;
  if (!is_null($http_timeout_ms)) {
    curl_setopt($ch, CURLOPT_TIMEOUT_MS, $http_timeout_ms);
  } else {
    curl_setopt(
      $ch,
      CURLOPT_TIMEOUT,
      $options['http_timeout'] ?? $default_timeout,
    );
  }

  if (!is_null($connect_timeout_ms) || !is_null($http_timeout_ms)) {
    curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
  }

  curl_setopt($ch, CURLINFO_HEADER_OUT, true);
  curl_setopt($ch, CURLOPT_HEADER, false);
  curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');

  if ($options['follow_redirects'] ?? $default_follow_redirects) {
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt(
      $ch,
      CURLOPT_MAXREDIRS,
      intval($options['follow_redirects'] ?? $default_follow_redirects),
    );
  }

  $outfile = $options['outfile'] ?? null;
  if (!is_null($outfile)) {
    curl_setopt($ch, CURLOPT_FILE, fopen($outfile, 'w'));
  }

  return $ch;
}

function _http_parse_response(
  string $body,
  http_curl_info_t $info,
): http_response_t {
  if ($info['curl_error_code']) throw new Exception('curl_error');

  return shape(
    'code' => $info['http_code'],
    'url' => $info['url'],
    'info' => $info,
    'body' => $body,
    'rsp_len' => strlen($body),
  );
}

#
# Format an associative array of request headers into request
# header syntax, "some_header_key: some_header_value"
#
function _http_prepare_outgoing_headers(
  dict<string, string> $headers = dict[],
): vec<string> {
  $prepped = vec[];

  if (!C\contains_key($headers, 'Expect')) {
    $headers['Expect'] = ''; # Get around error 417
  }

  foreach ($headers as $key => $value) {
    $prepped[] = "{$key}: {$value}";
  }

  return $prepped;
}

#
# Return a typed subset of curl_getinfo, plus curl_errno and curl_error
#

function _http_curl_getinfo(resource $ch): http_curl_info_t {
  $info = curl_getinfo($ch);

  return shape(
    'url' => $info['url'],
    'connect_time' => $info['connect_time'],
    'content_type' => $info['content_type'] ?? '',
    'http_code' => $info['http_code'],
    'header_size' => $info['header_size'],
    'request_size' => $info['request_size'],
    'redirect_count' => $info['redirect_count'],
    'request_header' => $info['request_header'],

    # number of bytes transferred is returned as a float
    # which doesn't make sense
    'size_download' => intval($info['size_download']),

    'curl_error_code' => curl_errno($ch),
    'curl_error_msg' => curl_error($ch),
  );
}

async function _http_curl_multi(
  vec<resource> $chs,
  http_options_t $options = shape(),
  float $timeout = 1.0,
): Awaitable<dict<string, http_response_t>> {
  $mh = curl_multi_init();

  foreach ($chs as $ch) {
    curl_multi_add_handle($mh, $ch);
  }

  $sleep_ms = 10;
  do {
    $active = 1;
    do {
      $status = curl_multi_exec($mh, &$active);
    } while ($status == CURLM_CALL_MULTI_PERFORM);

    if (!$active) break;

    $select = await curl_multi_await($mh);
    /* If cURL is built without ares support, DNS queries don't have a socket
    * to wait on, so curl_multi_await() (and curl_select() in PHP5) will return
    * -1, and polling is required.
    */
    if ($select == -1) {
      await SleepWaitHandle::create($sleep_ms * 1000);
      if ($sleep_ms < 1000) {
        $sleep_ms *= 2;
      }
    } else {
      $sleep_ms = 10;
    }
  } while ($status === CURLM_OK);

  $results = dict[];
  foreach ($chs as $ch) {
    $url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $content = (string)curl_multi_getcontent($ch);
    $info = _http_curl_getinfo($ch);

    $response = _http_parse_response($content, $info);
    $results[$url] = $response;
    curl_multi_remove_handle($mh, $ch);
  }

  curl_multi_close($mh);
  return $results;
}

final class HttpMultiLimitClient {
  private static int $active_requests = 0;
  private static int $limit = 4;

  public static async function request(
    string $url,
    dict<string, string> $headers = dict[],
    http_options_t $options = shape(),
  ): Awaitable<http_response_t> {
    while (self::$active_requests > self::$limit) {
      await \HH\Asio\later();
    }

    self::$active_requests += 1;
    $result = await http_request($url, $headers, $options);
    self::$active_requests -= 1;
    return $result;
  }
}
