<?php

namespace App\Service;

class CsrfService
{
  private const SESSION_KEY = 'csrf-token';
  private const FIELD_NAME = 'csrf_token';

  /**
   * CSRFトークンを生成してsessionに保存
   */
  public static function generate()
  {
    $token = bin2hex(random_bytes(32));
    $_SESSION[self::SESSION_KEY] = $token;

    return $token;
  }

  /**
   * POSTされたCSRFトークンとsessionのトークンを検証
   */
  public static function validate($data)
  {
    $sessionToken = $_SESSION[self::SESSION_KEY] ?? '';
    $postedToken = is_array($data) ? ($data[self::FIELD_NAME] ?? '') : '';

    return is_string($postedToken)
      && is_string($sessionToken)
      && $postedToken !== ''
      && hash_equals($sessionToken, $postedToken);
  }

  /**
   * sessionに保存したCSRFトークンを削除
   */
  public static function clear()
  {
    unset($_SESSION[self::SESSION_KEY]);
  }
}
