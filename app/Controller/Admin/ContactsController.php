<?php

namespace App\Controller\Admin;

use App\Repository\ContactRepository;
use DateTimeImmutable;
use DateTimeZone;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use App\Service\CsrfService;

class ContactsController
{
  private Twig $view;
  private ContactRepository $contacts;

  public function __construct(Twig $view, ContactRepository $contacts)
  {
    $this->view = $view;
    $this->contacts = $contacts;
  }

  /**
   * 問い合わせ管理ダッシュボード
   */
  public function index(Request $request, Response $response)
  {
    $today = new DateTimeImmutable('now', new DateTimeZone('Asia/Tokyo'));
    $weekdays = ['日曜日', '月曜日', '火曜日', '水曜日', '木曜日', '金曜日', '土曜日'];

    return $this->view->render(
      $response,
      'admin/contacts/index.twig',
      [
        'contacts' => $this->contacts->allContacts(),
        'contactCounts' => $this->contacts->contactCounts(),
        'needsReply' => $this->contacts->needsReply(),
        'currentDate' => $today->format('Y年n月j日'),
        'currentWeekday' => $weekdays[(int) $today->format('w')],
      ]
    );
  }

  /**
   * 問い合わせ詳細・対応管理画面
   */
  public function show(Request $request, Response $response, array $args)
  {
    $id = (int) ($args['id'] ?? 0);
    $contact = $this->contacts->findAdminById($id);

    if ($contact === null) {
      $response->getBody()->write('問い合わせが見つかりません');
      return $response->withStatus(404);
    }

    $formKey = 'admin-contact-form-' . $id;
    $errorKey = 'admin-contact-errors-' . $id;
    $successKey = 'admin-contact-success-' . $id;
    $form = $_SESSION[$formKey] ?? [
      'status' => (int) $contact['status'],
      'memo' => $contact['memo'] ?? '',
    ];
    $errors = $_SESSION[$errorKey] ?? [];
    $success = $_SESSION[$successKey] ?? null;

    unset($_SESSION[$formKey], $_SESSION[$errorKey], $_SESSION[$successKey]);

    return $this->view->render(
      $response,
      'admin/contacts/show.twig',
      [
        'contact' => $contact,
        'contactHistory' => $this->buildContactHistory($contact),
        'form' => $form,
        'errors' => $errors,
        'success' => $success,
        'csrf_token' => CsrfService::generate(),
      ]
    );
  }

  /**
   * 問い合わせの対応状況と管理メモを更新
   */
  public function update(Request $request, Response $response, array $args)
  {
    $id = (int) ($args['id'] ?? 0);
    $contact = $this->contacts->findAdminById($id);

    if ($contact === null) {
      $response->getBody()->write('問い合わせが見つかりません');
      return $response->withStatus(404);
    }

    $rawForm = $request->getParsedBody();
    $rawForm = is_array($rawForm) ? $rawForm : [];
    $formKey = 'admin-contact-form-' . $id;
    $errorKey = 'admin-contact-errors-' . $id;
    $form = $this->normalizeForm($rawForm);

    if (($rawForm['action'] ?? '') === 'complete') {
      $form['status'] = 2;
    }

    if (!CsrfService::validate($rawForm)) {
      $_SESSION[$formKey] = $form;
      $_SESSION[$errorKey] = [
        'csrf' => '不正な送信です。もう一度お試しください',
      ];
      return $this->redirect($response, '/admin/contacts/' . $id);
    }

    $errors = [];

    if (!in_array($form['status'], [0, 1, 2], true)) {
      $errors['status'] = '対応状況を選択してください';
    }

    if ($errors !== []) {
      $_SESSION[$formKey] = $form;
      $_SESSION[$errorKey] = $errors;
      return $this->redirect($response, '/admin/contacts/' . $id);
    }

    try {
      $this->contacts->updateManagement(
        $id,
        $form['status'],
        $form['memo'] !== '' ? $form['memo'] : null
      );
    } catch (\Throwable $error) {
      $_SESSION[$formKey] = $form;
      $_SESSION[$errorKey] = [
        'common' => '対応情報を更新できませんでした。時間をおいて再度お試しください',
      ];
      return $this->redirect($response, '/admin/contacts/' . $id);
    }

    unset($_SESSION[$formKey], $_SESSION[$errorKey]);
    CsrfService::clear();
    $_SESSION['admin-contact-success-' . $id] = $form['status'] === 2
      ? '問い合わせを対応済みにしました'
      : '対応情報を保存しました';

    return $this->redirect($response, '/admin/contacts/' . $id);
  }

  /**
   * 対応管理フォームの入力値を整形
   */
  private function normalizeForm(array $data)
  {
    $status = filter_var(
      $data['status'] ?? null,
      FILTER_VALIDATE_INT,
      FILTER_NULL_ON_FAILURE
    );
    $memo = $data['memo'] ?? '';

    return [
      'status' => $status ?? -1,
      'memo' => is_string($memo) ? trim($memo) : '',
    ];
  }

  /**
   * created_at・updated_atをもとに表示用の対応履歴を生成
   */
  private function buildContactHistory(array $contact)
  {
    $history = [
      [
        'label' => '問い合わせを受信しました',
        'occurred_at' => $contact['created_at'],
        'icon' => '/assets/images/common/envelope-exclamation-fill.svg',
      ],
    ];

    if ($contact['updated_at'] > $contact['created_at']) {
      array_unshift($history, [
        'label' => '対応情報を更新しました',
        'occurred_at' => $contact['updated_at'],
        'icon' => '/assets/images/common/check-lg.svg',
      ]);
    }

    return $history;
  }

  private function redirect(Response $response, string $path)
  {
    return $response
      ->withHeader('Location', $path)
      ->withStatus(302);
  }
}
