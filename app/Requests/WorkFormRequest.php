<?php

namespace App\Requests;

class WorkFormRequest
{
  public static function validate(array $data): array
  {
    $errors = [];

    $title = trim((string) ($data['title'] ?? ''));
    $slug = trim((string) ($data['slug'] ?? ''));
    $category = trim((string) ($data['category'] ?? ''));
    $period = trim((string) ($data['period'] ?? ''));
    $siteUrl = trim((string) ($data['site_url'] ?? ''));
    $githubUrl = trim((string) ($data['github_url'] ?? ''));
    $sortOrder = $data['sort_order'] ?? '0';

    if ($title === '') {
      $errors['title'] = '実績名を入力してください';
    } elseif (mb_strlen($title) > 255) {
      $errors['title'] = '実績名は255文字以内で入力してください';
    }

    if ($slug === '') {
      $errors['slug'] = 'URLスラッグを入力してください';
    } elseif (mb_strlen($slug) > 255) {
      $errors['slug'] = 'URLスラッグは255文字以内で入力してください';
    } elseif (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
      $errors['slug'] = 'URLスラッグは半角英数字とハイフンで入力してください';
    }

    if (mb_strlen($category) > 100) {
      $errors['category'] = 'カテゴリーは100文字以内で入力してください';
    }

    if (mb_strlen($period) > 50) {
      $errors['period'] = '制作期間は50文字以内で入力してください';
    }

    foreach (['site_url' => $siteUrl, 'github_url' => $githubUrl] as $field => $url) {
      if ($url === '') {
        continue;
      }

      if (mb_strlen($url) > 500) {
        $errors[$field] = 'URLは500文字以内で入力してください';
      } elseif (!filter_var($url, FILTER_VALIDATE_URL)) {
        $errors[$field] = '正しいURLを入力してください';
      }
    }

    if (filter_var($sortOrder, FILTER_VALIDATE_INT) === false) {
      $errors['sort_order'] = '表示順は整数で入力してください';
    }

    return $errors;
  }
}
