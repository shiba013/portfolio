<?php

namespace App\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use App\Requests\ContactFormRequest;
use App\Repository\ContactRepository;
use App\Service\CsrfService;

class ContactController
{
  private Twig $view;
  private ContactRepository $contact;

  public function __construct(
    Twig $view,
    ContactRepository $contact
  ) {
    $this->view = $view;
    $this->contact = $contact;
  }

  /**
   * 問い合わせフォーム表示
   */
  public function index(Request $request, Response $response)
  {
    return $this->view->render(
      $response,
      'contact/index.twig',
      [
        'form' => $_SESSION['contact-form'] ?? [],
        'errors' => $_SESSION['contact-errors'] ?? [],
        'csrf_token' => CsrfService::generate()
      ]
    );
  }

  /**
   * 入力確認画面
   */
  public function confirm(Request $request, Response $response)
  {
    // フォームの値を取得
    $form = $request->getParsedBody();

    // url手動操作防止
    if (!CsrfService::validate($form)) {
      $_SESSION['contact-form'] = $form;
      $_SESSION['contact-errors'] = [
        'csrf' => '不正な送信です。もう一度お試しください'
      ];
      return $this->redirect($response, '/contact');
    }
    // CSRFトークン破棄
    unset($form['csrf_token']);

    // バリデーション
    $errors = ContactFormRequest::validate($form);

    // 入力値にエラーがあったら入力値を保持した上で/contactへリダイレクト
    if ($errors) {
      $_SESSION['contact-form'] = $form;
      $_SESSION['contact-errors'] = $errors;

      return $this->redirect($response, '/contact');
    }

    // 不要なCSRFトークンを除外
    // トークン情報はDBに保存しないため
    unset($_SESSION['contact-errors']);

    // バリデーションを通過した値を保持
    $_SESSION['contact-form'] = $form;

    // 入力確認画面へ遷移
    return $this->view->render(
      $response,
      'contact/confirm.twig',
      [
        'form' => $form,
        'csrf_token' => CsrfService::generate()
      ]
    );
  }

  /**
   * 問い合わせフォームに戻る
   */
  public function back(Request $request, Response $response)
  {
    // url手動操作防止
    if (!CsrfService::validate($request->getParsedBody())) {
      $_SESSION['contact-errors'] = [
        'csrf' => '不正な送信です。もう一度お試しください'
      ];
    }
    return $this->redirect($response, '/contact');
  }

  /**
   * データ保存・メール送信処理
   */
  public function complete(Request $request, Response $response)
  {
    // url手動操作防止
    if (!CsrfService::validate($request->getParsedBody())) {
      $_SESSION['contact-errors'] = [
        'csrf' => '不正な送信です。もう一度お試しください'
      ];
      return $this->redirect($response, '/contact');
    }

    $form = $_SESSION['contact-form'] ?? [];

    // url手動操作防止（入力データがない場合）
    if (!$form) {
      $_SESSION['contact-errors'] = [
        'common' => '入力内容を確認できませんでした。もう一度入力してください'
      ];
      return $this->redirect($response, '/contact');
    }

    // 念のためもう一回バリデーション
    $errors = ContactFormRequest::validate($form);
    if ($errors) {
      $_SESSION['contact-form'] = $form;
      $_SESSION['contact-errors'] = $errors;

      return $this->redirect($response, '/contact');
    }

    // DB保存処理
    $this->contact->create($form);

    // メール送信処理

    // 保持していたsessionを破棄
    unset($_SESSION['contact-form'], $_SESSION['contact-errors']);
    CsrfService::clear();

    return $this->redirect($response, '/thanks');
  }

  /**
   * サンクスページ表示
   */
  public function thanks(Request $request, Response $response)
  {
    return $this->view->render($response, 'contact/thanks.twig');
  }

  /**
   * リダイレクトを定義
   */
  private function redirect(Response $response, $path)
  {
    return $response
      ->withHeader('Location', $path)
      ->withStatus(302);
  }
}
