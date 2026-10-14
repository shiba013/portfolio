<?php

namespace App\Requests;

class CaseFormRequest
{
  public static function validate(array $data)
  {
    $errors = [];
    $title = trim((string) ($data['title'] ?? ''));
    $slug = trim((string) ($data['slug'] ?? ''));
    $visualType = trim((string) ($data['visual_type'] ?? ''));
    $visualBody = trim((string) ($data['visual_body'] ?? ''));
    $sortOrder = $data['sort_order'] ?? '0';

    if ($title === '') {
      $errors['title'] = '事例名を入力してください';
    } elseif (mb_strlen($title) > 255) {
      $errors['title'] = '事例名は255文字以内で入力してください';
    }

    if ($slug === '') {
      $errors['slug'] = 'URLスラッグを入力してください';
    } elseif (mb_strlen($slug) > 255) {
      $errors['slug'] = 'URLスラッグは255文字以内で入力してください';
    } elseif (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
      $errors['slug'] = 'URLスラッグは半角英数字とハイフンで入力してください';
    }

    if ($visualType === '') {
      $errors['visual_type'] = '表示タイプを入力してください';
    } elseif (mb_strlen($visualType) > 50) {
      $errors['visual_type'] = '表示タイプは50文字以内で入力してください';
    } elseif (!preg_match('/^[a-z0-9]+(?:[-_][a-z0-9]+)*$/', $visualType)) {
      $errors['visual_type'] = '表示タイプは半角英数字、ハイフン、アンダースコアで入力してください';
    }

    if ($visualBody === '') {
      $errors['visual_body'] = '表示内容を入力してください';
    }

    if (filter_var($sortOrder, FILTER_VALIDATE_INT) === false) {
      $errors['sort_order'] = '表示順は整数で入力してください';
    }

    return $errors;
  }
}
