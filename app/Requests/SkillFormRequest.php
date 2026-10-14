<?php

namespace App\Requests;

class SkillFormRequest
{
  public static function validate(array $data): array
  {
    $errors = [];
    $name = trim((string) ($data['name'] ?? ''));
    $slug = trim((string) ($data['slug'] ?? ''));
    $category = trim((string) ($data['category'] ?? ''));
    $sortOrder = $data['sort_order'] ?? '0';

    if ($name === '') {
      $errors['name'] = 'スキル名を入力してください';
    } elseif (mb_strlen($name) > 100) {
      $errors['name'] = 'スキル名は100文字以内で入力してください';
    }

    if ($slug === '') {
      $errors['slug'] = 'URLスラッグを入力してください';
    } elseif (mb_strlen($slug) > 100) {
      $errors['slug'] = 'URLスラッグは100文字以内で入力してください';
    } elseif (!preg_match('/^[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*$/', $slug)) {
      $errors['slug'] = 'URLスラッグは半角英数字とハイフンで入力してください';
    }

    if ($category === '') {
      $errors['category'] = 'カテゴリーを入力してください';
    } elseif (mb_strlen($category) > 50) {
      $errors['category'] = 'カテゴリーは50文字以内で入力してください';
    }

    if (filter_var($sortOrder, FILTER_VALIDATE_INT) === false) {
      $errors['sort_order'] = '表示順は整数で入力してください';
    }

    return $errors;
  }
}
