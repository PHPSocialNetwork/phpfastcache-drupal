<?php
namespace Drupal\phpfastcache\Utils;

class StringUtil {

  /**
   * @param      $str
   * @param bool $capitalise_first_char
   *
   * @return null|string|string[]
   */
  public static function toCamelCase($str, $capitalise_first_char = FALSE) {
    if ($capitalise_first_char) {
      $str[ 0 ] = strtoupper($str[ 0 ]);
    }
    return preg_replace_callback(
      '/_([a-z])/',
      function ($c) {
        return strtoupper($c[ 1 ]);
      },
      $str
    );
  }

  /**
   * @param array $replace
   * @param       $subject
   *
   * @return mixed
   */
  public static function strReplaceAssoc(array $replace, $subject) {
    return \str_replace(\array_keys($replace), \array_values($replace), $subject);
  }
}