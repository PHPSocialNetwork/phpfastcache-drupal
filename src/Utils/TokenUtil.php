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
    /**
     * Feel free to propose your own
     * by making a pull request
     */
    return [
      '%DRUPAL_ROOT%' => \DRUPAL_ROOT,
      '%TIMESTAMP%' => time(),
      '%DATE_W3C%' => (new \DateTime())->format(\DateTime::W3C),
      '%DAY%' => date('d'),
      '%MONTH%' => date('m'),
      '%YEAR%' => date('Y'),
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