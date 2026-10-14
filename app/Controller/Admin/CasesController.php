<?php

namespace App\Controller\Admin;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use App\Requests\CaseFormRequest;
use App\Repository\CaseRepository;
use App\Repository\SkillRepository;
use App\Service\CsrfService;

class CasesController
{
  private Twig $view;
  private CaseRepository $cases;
  private SkillRepository $skills;

  public function __construct(
    Twig $view,
    CaseRepository $cases,
    SkillRepository $skills
  ) {
    $this->view = $view;
    $this->cases = $cases;
    $this->skills = $skills;
  }

  public function index(Request $request, Response $response)
  {
    $success = $_SESSION['admin-case-success'] ?? null;
    unset($_SESSION['admin-case-success']);

    return $this->view->render(
      $response,
      'admin/cases/index.twig',
      [
        'cases' => $this->cases->allCases(),
        'caseCounts' => $this->cases->caseCounts(),
        'success' => $success,
      ]
    );
  }

  /**
   * 新規事例フォーム
   */
  public function create(Request $request, Response $response)
  {
    $form = $_SESSION['admin-case-form'] ?? [];
    $errors = $_SESSION['admin-case-errors'] ?? [];
    unset($_SESSION['admin-case-form'], $_SESSION['admin-case-errors']);

    return $this->view->render(
      $response,
      'admin/cases/form.twig',
      [
        'form' => $form,
        'errors' => $errors,
        'skills' => $this->skills->allSkills(),
        'csrf_token' => CsrfService::generate(),
      ]
    );
  }

  /**
   * 事例保存処理
   */
  public function store(Request $request, Response $response)
  {
    $rawForm = $request->getParsedBody();
    $rawForm = is_array($rawForm) ? $rawForm : [];

    if (!CsrfService::validate($rawForm)) {
      $_SESSION['admin-case-form'] = $rawForm;
      $_SESSION['admin-case-errors'] = [
        'csrf' => '不正な送信です。もう一度お試しください',
      ];
      return $this->redirect($response, '/admin/cases/create');
    }

    $errors = CaseFormRequest::validate($rawForm);
    $form = $this->normalizeForm($rawForm);

    if ($form['slug'] !== '' && $this->cases->slugExists($form['slug'])) {
      $errors['slug'] = 'このURLスラッグはすでに使用されています';
    }

    if ($errors !== []) {
      $_SESSION['admin-case-form'] = $form;
      $_SESSION['admin-case-errors'] = $errors;
      return $this->redirect($response, '/admin/cases/create');
    }

    try {
      $this->cases->create($form, $this->validSkillIds($form['skill_ids']));
    } catch (\Throwable $error) {
      $_SESSION['admin-case-form'] = $form;
      $_SESSION['admin-case-errors'] = [
        'common' => '事例を保存できませんでした。時間をおいて再度お試しください',
      ];
      return $this->redirect($response, '/admin/cases/create');
    }

    unset($_SESSION['admin-case-form'], $_SESSION['admin-case-errors']);
    CsrfService::clear();
    $_SESSION['admin-case-success'] = '事例を保存しました';

    return $this->redirect($response, '/admin/cases');
  }

  /**
   * 編集フォーム
   */
  public function edit(Request $request, Response $response, array $args)
  {
    $id = (int) ($args['id'] ?? 0);
    $case = $this->cases->findAdminById($id);

    if ($case === null) {
      $response->getBody()->write('事例が見つかりません');
      return $response->withStatus(404);
    }

    $formKey = 'admin-case-edit-form-' . $id;
    $errorKey = 'admin-case-edit-errors-' . $id;
    $form = $_SESSION[$formKey] ?? $case;
    $errors = $_SESSION[$errorKey] ?? [];
    unset($_SESSION[$formKey], $_SESSION[$errorKey]);
    $form['id'] = $id;

    return $this->view->render(
      $response,
      'admin/cases/form.twig',
      [
        'form' => $form,
        'errors' => $errors,
        'skills' => $this->skills->allSkills(),
        'csrf_token' => CsrfService::generate(),
        'is_edit' => true,
      ]
    );
  }

  /**
   * 事例更新処理
   */
  public function update(Request $request, Response $response, array $args)
  {
    $id = (int) ($args['id'] ?? 0);
    $currentCase = $this->cases->findAdminById($id);

    if ($currentCase === null) {
      $response->getBody()->write('事例が見つかりません');
      return $response->withStatus(404);
    }

    $rawForm = $request->getParsedBody();
    $rawForm = is_array($rawForm) ? $rawForm : [];
    $formKey = 'admin-case-edit-form-' . $id;
    $errorKey = 'admin-case-edit-errors-' . $id;

    if (!CsrfService::validate($rawForm)) {
      $_SESSION[$formKey] = $rawForm;
      $_SESSION[$errorKey] = [
        'csrf' => '不正な送信です。もう一度お試しください',
      ];
      return $this->redirect($response, '/admin/cases/' . $id . '/edit');
    }

    $errors = CaseFormRequest::validate($rawForm);
    $form = $this->normalizeForm($rawForm);
    $form['id'] = $id;

    if ($form['slug'] !== '' && $this->cases->slugExists($form['slug'], $id)) {
      $errors['slug'] = 'このURLスラッグはすでに使用されています';
    }

    if ($errors !== []) {
      $_SESSION[$formKey] = $form;
      $_SESSION[$errorKey] = $errors;
      return $this->redirect($response, '/admin/cases/' . $id . '/edit');
    }

    try {
      $this->cases->update($id, $form, $this->validSkillIds($form['skill_ids']));
    } catch (\Throwable $error) {
      $_SESSION[$formKey] = $form;
      $_SESSION[$errorKey] = [
        'common' => '事例を更新できませんでした。時間をおいて再度お試しください',
      ];
      return $this->redirect($response, '/admin/cases/' . $id . '/edit');
    }

    unset($_SESSION[$formKey], $_SESSION[$errorKey]);
    CsrfService::clear();
    $_SESSION['admin-case-success'] = '事例を更新しました';

    return $this->redirect($response, '/admin/cases');
  }

  /**
   * 事例削除処理
   */
  public function delete(Request $request, Response $response, array $args)
  {
    $id = (int) ($args['id'] ?? 0);
    $case = $this->cases->findAdminById($id);

    if ($case === null) {
      $response->getBody()->write('事例が見つかりません');
      return $response->withStatus(404);
    }

    $rawForm = $request->getParsedBody();
    $rawForm = is_array($rawForm) ? $rawForm : [];
    $errorKey = 'admin-case-edit-errors-' . $id;

    if (!CsrfService::validate($rawForm)) {
      $_SESSION[$errorKey] = [
        'csrf' => '不正な送信です。もう一度お試しください',
      ];
      return $this->redirect($response, '/admin/cases/' . $id . '/edit');
    }

    try {
      $deleted = $this->cases->delete($id);
    } catch (\Throwable $error) {
      $_SESSION[$errorKey] = [
        'common' => '事例を削除できませんでした。時間をおいて再度お試しください',
      ];
      return $this->redirect($response, '/admin/cases/' . $id . '/edit');
    }

    if (!$deleted) {
      $response->getBody()->write('事例が見つかりません');
      return $response->withStatus(404);
    }

    unset(
      $_SESSION['admin-case-edit-form-' . $id],
      $_SESSION[$errorKey]
    );
    CsrfService::clear();
    $_SESSION['admin-case-success'] = '事例を削除しました';

    return $this->redirect($response, '/admin/cases');
  }


  /**
   * 入力中の事例を公開画面の構成でプレビュー
   */
  public function preview(Request $request, Response $response, array $args)
  {
    $rawForm = $request->getParsedBody();
    $rawForm = is_array($rawForm) ? $rawForm : [];

    if (!CsrfService::validate($rawForm)) {
      $response->getBody()->write('プレビューの有効期限が切れました。画面を再読み込みしてください。');
      return $response->withStatus(419);
    }

    $view = $args['view'] ?? 'list';
    if (!in_array($view, ['list', 'detail'], true)) {
      return $response->withStatus(404);
    }

    $form = $this->normalizeForm($rawForm);
    $editingCase = null;
    $caseId = filter_var(
      $rawForm['case_id'] ?? null,
      FILTER_VALIDATE_INT,
      FILTER_NULL_ON_FAILURE
    );

    if ($caseId !== null && $caseId > 0) {
      $editingCase = $this->cases->findAdminById($caseId);
    }

    $case = $this->previewCase($form);
    if ($editingCase !== null) {
      $case['id'] = (int) $editingCase['id'];
    }

    if ($view === 'detail') {
      return $this->view->render(
        $response,
        'cases/show.twig',
        ['case' => $case]
      );
    }

    $publishedCases = $this->cases->publishedCases();
    if ($editingCase !== null) {
      $publishedCases = array_values(
        array_filter(
          $publishedCases,
          static fn(array $publishedCase): bool =>
          (int) $publishedCase['id'] !== (int) $editingCase['id']
        )
      );
    }

    array_unshift($publishedCases, $case);

    return $this->view->render(
      $response,
      'cases/index.twig',
      [
        'publishedCases' => $publishedCases,
        'featuredCases' => $this->cases->featuredCases(),
        'caseSkills' => $this->caseSkills($publishedCases),
      ]
    );
  }


  /**
   * フォームの入力値を返す
   */
  private function normalizeForm(array $data)
  {
    $stringFields = [
      'title',
      'slug',
      'summary',
      'problem',
      'investigation',
      'solution',
      'result',
      'visual_type',
      'visual_body',
    ];
    $form = [];

    foreach ($stringFields as $field) {
      $form[$field] = trim((string) ($data[$field] ?? ''));
    }

    $form['is_public'] = isset($data['is_public']) ? 1 : 0;
    $form['is_featured'] = isset($data['is_featured']) ? 1 : 0;
    $form['sort_order'] = filter_var(
      $data['sort_order'] ?? 0,
      FILTER_VALIDATE_INT,
      FILTER_NULL_ON_FAILURE
    );
    $form['sort_order'] ??= 0;
    $form['skill_ids'] = is_array($data['skill_ids'] ?? null)
      ? array_values(array_map('intval', $data['skill_ids']))
      : [];

    return $form;
  }


  /**
   * プレビュー表示用
   *
   */
  private function previewCase(array $form): array
  {
    return [
      'id' => 0,
      'is_preview' => true,
      'title' => $form['title'] !== '' ? $form['title'] : '事例名を入力',
      'slug' => $form['slug'] !== '' ? $form['slug'] : 'preview',
      'summary' => $form['summary'] !== ''
        ? $form['summary']
        : '概要を入力すると、ここにプレビュー表示されます。',
      'problem' => $form['problem'] !== '' ? $form['problem'] : '発生した問題を入力',
      'investigation' => $form['investigation'] !== ''
        ? $form['investigation']
        : '原因調査の内容を入力',
      'solution' => $form['solution'] !== '' ? $form['solution'] : '解決方法を入力',
      'result' => $form['result'] !== '' ? $form['result'] : '対応結果を入力',
      'visual_type' => $form['visual_type'] !== '' ? $form['visual_type'] : 'terminal',
      'visual_body' => $form['visual_body'] !== ''
        ? $form['visual_body']
        : '表示内容を入力すると、ここにプレビュー表示されます。',
      'skills' => $this->selectedSkills($form['skill_ids']),
    ];
  }


  /**
   * 正しいスキルデータを取得
   * プレビュー表示に使用
   */
  private function selectedSkills(array $selectedIds): array
  {
    $selectedIds = array_map('intval', $selectedIds);
    $selectedSkills = [];

    foreach ($this->skills->allSkills() as $skill) {
      if (in_array((int) $skill['id'], $selectedIds, true)) {
        $selectedSkills[] = $skill;
      }
    }

    return $selectedSkills;
  }


  /**
   * selectedSkillsからスキルIDだけ取得
   * 中間テーブル保存用
   *
   * 存在しないスキルIDが登録されないように
   * 全件取得時のidと比較する
   */
  private function validSkillIds(array $selectedIds): array
  {
    return array_map('intval', array_column($this->selectedSkills($selectedIds), 'id'));
  }


  /**
   * 事例に紐づくスキルを取得
   */
  private function caseSkills(array $cases)
  {
    $skills = [];

    foreach ($cases as $case) {
      foreach ($case['skills'] as $skill) {
        $skills[$skill['id']] = $skill;
      }
    }

    return array_values($skills);
  }


  /**
   * リダイレクト
   */
  private function redirect(Response $response, string $path)
  {
    return $response
      ->withHeader('Location', $path)
      ->withStatus(302);
  }
}
