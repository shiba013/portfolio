<?php

namespace App\Requests;

class ContactFormRequest
{
  public static function validate($data)
  {
    $errors = [];

    // 先にそれぞれの値を定義
    $name = trim($data['name'] ?? '');
    $email = trim($data['email'] ?? '');
    $subject = trim($data['subject'] ?? '');
    $message = trim($data['message'] ?? '');

    // 名前のバリデーション
    if ($name === '') {
      $errors['name'] = 'お名前を入力してください';
    } elseif (mb_strlen($name) > 20) {
      $errors['name'] = 'お名前は20文字以内で入力してください';
    }

    // メールアドレスのバリデーション
    if ($email === '') {
      $errors['email'] = 'メールアドレスを入力してください';
    } elseif (mb_strlen($email) > 255) {
      $errors['email'] = 'メールアドレスは255文字以内で入力してください';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $errors['email'] = 'メールアドレスの形式で入力してください';
    }

    // 件名のバリデーション
    if ($subject === '') {
      $errors['subject'] = '件名を入力してください';
    } elseif (mb_strlen($subject) > 50) {
      $errors['subject'] = '件名は50文字以内で入力してください';
    }

    // お問い合わせ内容のバリデーション
    if ($message === '') {
      $errors['message'] = 'お問い合わせ内容を入力してください';
    } elseif (mb_strlen($message) > 1000) {
      $errors['message'] = 'お問い合わせ内容は1000文字以内で入力してください';
    }
    return $errors;
  }
}
