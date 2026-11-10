<?php

namespace Hubleto\App\External\WaiBlue\ServerReport\Services;

/**
 * Heuristics that detect text which looks like it contains a password or another secret.
 * It cannot be perfect: it only prevents the most common mistakes.
 */
class SecretGuard extends \Hubleto\Erp\Core
{
  private const TEXT_PATTERNS = [
    '/\b(password|passwd|pwd|passphrase|secret|token|api[_-]?key)\b\s*[:=]\s*[^\s,;]{3,}/i',
    '/-----BEGIN [A-Z ]*PRIVATE KEY-----/',
    '/\bsshpass\s+-p\s*\S+/i',
    '/[a-z][a-z0-9+.\-]*:\/\/[^\s:@\/]+:[^\s@\/]+@/i',
  ];

  private const KEY_PATTERN = '/(pass(word|wd)?|pwd|secret|token|api[_-]?key|private[_-]?key)/i';

  /**
   * Does the text look like it contains a secret?
   *
   * @param string $text
   *
   * @return bool
   *
   */
  public function containsSecret(string $text): bool
  {
    if (trim($text) === '') return false;
    foreach (self::TEXT_PATTERNS as $pattern) {
      if (preg_match($pattern, $text)) return true;
    }
    return false;
  }

  /**
   * Does the (decoded JSON) array contain a secret? Checks names of keys and values.
   *
   * @param array $data
   *
   * @return bool
   *
   */
  public function arrayContainsSecret(array $data): bool
  {
    foreach ($data as $key => $value) {
      if (is_array($value)) {
        if ($this->arrayContainsSecret($value)) return true;
        continue;
      }
      $value = trim((string) $value);
      if ($value === '') continue;
      if (is_string($key) && preg_match(self::KEY_PATTERN, $key)) return true;
      if ($this->containsSecret($value)) return true;
    }
    return false;
  }
}
