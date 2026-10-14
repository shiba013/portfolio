<?php

namespace App\Controller\Admin;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use App\Requests\SkillFormRequest;
use App\Repository\SkillRepository;
use App\Service\CsrfService;

class SkillsController
{
  private Twig $view;
  private SkillRepository $skills;

  public function __construct(
    Twig $view,
    SkillRepository $skills
  ) {
    $this->view = $view;
    $this->skills = $skills;
  }

  public function index(Request $request, Response $response)
  {
    $success = $_SESSION['admin-skill-success'] ?? null;
    unset($_SESSION['admin-skill-success']);

    return $this->view->render(
      $response,
      'admin/skills/index.twig',
      [
        'skills' => $this->skills->allSkills(),
        'skillCounts' => $this->skills->skillCounts(),
        'success' => $success,
      ]
    );
  }

  /**
   * 新規スキルフォーム
   */
  public function create(Request $request, Response $response)
  {
    $form = $_SESSION['admin-skill-form'] ?? [];
    $errors = $_SESSION['admin-skill-errors'] ?? [];
    unset($_SESSION['admin-skill-form'], $_SESSION['admin-skill-errors']);

    return $this->view->render(
      $response,
      'admin/skills/form.twig',
      [
        'form' => $form,
        'errors' => $errors,
        'categories' => $this->skills->categories(),
        'csrf_token' => CsrfService::generate(),
      ]
    );
  }

  /**
   * スキル保存処理
   */
  public function store(Request $request, Response $response)
  {
    $rawForm = $request->getParsedBody();
    $rawForm = is_array($rawForm) ? $rawForm : [];

    if (!CsrfService::validate($rawForm)) {
      $_SESSION['admin-skill-form'] = $rawForm;
      $_SESSION['admin-skill-errors'] = [
        'csrf' => '不正な送信です。もう一度お試しください',
      ];
      return $this->redirect($response, '/admin/skills/create');
    }

    $errors = SkillFormRequest::validate($rawForm);
    $form = $this->normalizeForm($rawForm);

    if ($form['slug'] !== '' && $this->skills->slugExists($form['slug'])) {
      $errors['slug'] = 'このURLスラッグはすでに使用されています';
    }

    if ($errors !== []) {
      $_SESSION['admin-skill-form'] = $form;
      $_SESSION['admin-skill-errors'] = $errors;
      return $this->redirect($response, '/admin/skills/create');
    }

    try {
      $this->skills->create($form);
    } catch (\Throwable $error) {
      $_SESSION['admin-skill-form'] = $form;
      $_SESSION['admin-skill-errors'] = [
        'common' => 'スキルを保存できませんでした。時間をおいて再度お試しください',
      ];
      return $this->redirect($response, '/admin/skills/create');
    }

    unset($_SESSION['admin-skill-form'], $_SESSION['admin-skill-errors']);
    CsrfService::clear();
    $_SESSION['admin-skill-success'] = 'スキルを保存しました';

    return $this->redirect($response, '/admin/skills');
  }

  /**
   * スキル編集フォーム
   */
  public function edit(Request $request, Response $response, array $args)
  {
    $id = (int) ($args['id'] ?? 0);
    $skill = $this->skills->findAdminById($id);

    if ($skill === null) {
      $response->getBody()->write('スキルが見つかりません');
      return $response->withStatus(404);
    }

    $formKey = 'admin-skill-edit-form-' . $id;
    $errorKey = 'admin-skill-edit-errors-' . $id;
    $form = $_SESSION[$formKey] ?? $skill;
    $errors = $_SESSION[$errorKey] ?? [];
    unset($_SESSION[$formKey], $_SESSION[$errorKey]);
    $form['id'] = $id;

    return $this->view->render(
      $response,
      'admin/skills/form.twig',
      [
        'form' => $form,
        'errors' => $errors,
        'categories' => $this->skills->categories(),
        'csrf_token' => CsrfService::generate(),
        'is_edit' => true,
      ]
    );
  }

  /**
   * スキル更新処理
   */
  public function update(Request $request, Response $response, array $args)
  {
    $id = (int) ($args['id'] ?? 0);
    $currentSkill = $this->skills->findAdminById($id);

    if ($currentSkill === null) {
      $response->getBody()->write('スキルが見つかりません');
      return $response->withStatus(404);
    }

    $rawForm = $request->getParsedBody();
    $rawForm = is_array($rawForm) ? $rawForm : [];
    $formKey = 'admin-skill-edit-form-' . $id;
    $errorKey = 'admin-skill-edit-errors-' . $id;

    if (!CsrfService::validate($rawForm)) {
      $_SESSION[$formKey] = $rawForm;
      $_SESSION[$errorKey] = [
        'csrf' => '不正な送信です。もう一度お試しください',
      ];
      return $this->redirect($response, '/admin/skills/' . $id . '/edit');
    }

    $errors = SkillFormRequest::validate($rawForm);
    $form = $this->normalizeForm($rawForm);
    $form['id'] = $id;

    if ($form['slug'] !== '' && $this->skills->slugExists($form['slug'], $id)) {
      $errors['slug'] = 'このURLスラッグはすでに使用されています';
    }

    if ($errors !== []) {
      $_SESSION[$formKey] = $form;
      $_SESSION[$errorKey] = $errors;
      return $this->redirect($response, '/admin/skills/' . $id . '/edit');
    }

    try {
      $this->skills->update($id, $form);
    } catch (\Throwable $error) {
      $_SESSION[$formKey] = $form;
      $_SESSION[$errorKey] = [
        'common' => 'スキルを更新できませんでした。時間をおいて再度お試しください',
      ];
      return $this->redirect($response, '/admin/skills/' . $id . '/edit');
    }

    unset($_SESSION[$formKey], $_SESSION[$errorKey]);
    CsrfService::clear();
    $_SESSION['admin-skill-success'] = 'スキルを更新しました';

    return $this->redirect($response, '/admin/skills');
  }

  /**
   * スキル削除処理
   */
  public function delete(Request $request, Response $response, array $args)
  {
    $id = (int) ($args['id'] ?? 0);
    $skill = $this->skills->findAdminById($id);

    if ($skill === null) {
      $response->getBody()->write('スキルが見つかりません');
      return $response->withStatus(404);
    }

    $rawForm = $request->getParsedBody();
    $rawForm = is_array($rawForm) ? $rawForm : [];
    $errorKey = 'admin-skill-edit-errors-' . $id;

    if (!CsrfService::validate($rawForm)) {
      $_SESSION[$errorKey] = [
        'csrf' => '不正な送信です。もう一度お試しください',
      ];
      return $this->redirect($response, '/admin/skills/' . $id . '/edit');
    }

    try {
      $deleted = $this->skills->delete($id);
    } catch (\Throwable $error) {
      $_SESSION[$errorKey] = [
        'common' => 'スキルを削除できませんでした。時間をおいて再度お試しください',
      ];
      return $this->redirect($response, '/admin/skills/' . $id . '/edit');
    }

    if (!$deleted) {
      $response->getBody()->write('スキルが見つかりません');
      return $response->withStatus(404);
    }

    unset(
      $_SESSION['admin-skill-edit-form-' . $id],
      $_SESSION[$errorKey]
    );
    CsrfService::clear();
    $_SESSION['admin-skill-success'] = 'スキルを削除しました';

    return $this->redirect($response, '/admin/skills');
  }


  /**
   * フォームの入力値を返す
   */
  private function normalizeForm(array $data)
  {
    $sortOrder = filter_var(
      $data['sort_order'] ?? 0,
      FILTER_VALIDATE_INT,
      FILTER_NULL_ON_FAILURE
    );

    return [
      'name' => trim((string) ($data['name'] ?? '')),
      'slug' => trim((string) ($data['slug'] ?? '')),
      'category' => trim((string) ($data['category'] ?? '')),
      'sort_order' => $sortOrder ?? 0,
      'is_public' => isset($data['is_public']) ? 1 : 0,
    ];
  }


  /**
   * リダイレクトを定義
   */
  private function redirect(Response $response, string $path)
  {
    return $response
      ->withHeader('Location', $path)
      ->withStatus(302);
  }
}
