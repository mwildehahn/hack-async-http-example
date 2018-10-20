<?hh // strict

function microtime_ms(): int {
  $gtod = gettimeofday();
  return ($gtod['sec'] * 1000) + ((int)($gtod['usec'] / 1000));
}
