<?php
namespace Drupal\phpfastcache\Utils;

class TokenUtil {

  /**
   * @return array
   */
  public static function getTokens():array {
    return array_keys(self::getTokensPairs());
  }

  /**
   * @return array
   */
  public static function getTokensPairs():array {
    return [
      '%YEAR%' => date('Y'),
      '%MONTH%' => date('m'),
      '%DAY%' => date('d'),
      '%DRUPAL_ROOT%' => \DRUPAL_ROOT,
    ];
  }

  /**
   * @param string $string
   *
   * @return string
   */
  public static function parseTokens(string $string):string {
    return StringUtil::strReplaceAssoc(self::getTokensPairs(), $string);
  }
}