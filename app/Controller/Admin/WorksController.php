<?php

namespace App\Controller\Admin;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\UploadedFileInterface;
use Slim\Views\Twig;
use App\Requests\WorkFormRequest;
use App\Repository\SkillRepository;
use App\Repository\WorkRepository;
use App\Service\CsrfService;

class WorksController
{
  private Twig $view;
  private WorkRepository $works;
  private SkillRepository $skills;

  public function __construct(
    Twig $view,
    WorkRepository $works,
    SkillRepository $skills
  ) {
    $this->view = $view;
    $this->works = $works;
    $this->skills = $skills;
  }

  public function index(Request $request, Response $response)
  {
    $allWorks = $this->works->allWorks();
    $workCounts = $this->works->workCounts();

    $success = $_SESSION['admin-work-success'] ?? null;
    unset($_SESSION['admin-work-success']);

    return $this->view->render(
      $response,
      'admin/works/index.twig',
      [
        'works' => $allWorks,
        'workCounts' => $workCounts,
        'success' => $success,
      ]
    );
  }

  /**
   * 新規実績フォーム
   */
  public function create(Request $request, Response $response)
  {
    $form = $_SESSION['admin-work-form'] ?? [];
    $errors = $_SESSION['admin-work-errors'] ?? [];
    unset($_SESSION['admin-work-form'], $_SESSION['admin-work-errors']);

    return $this->view->render(
      $response,
      'admin/works/form.twig',
      [
        'form' => $form,
        'errors' => $errors,
        'skills' => $this->skills->allSkills(),
        'csrf_token' => CsrfService::generate(),
      ]
    );
  }

  /**
   * 実績保存処理
   */
  public function store(Request $request, Response $response)
  {
    $rawForm = $request->getParsedBody();
    $rawForm = is_array($rawForm) ? $rawForm : [];

    // CSRFエラー
    if (!CsrfService::validate($rawForm)) {
      $_SESSION['admin-work-form'] = $rawForm;
      $_SESSION['admin-work-errors'] = [
        'csrf' => '不正な送信です。もう一度お試しください',
      ];
      return $this->redirect($response, '/admin/works/create');
    }

    // バリデーション定義
    $errors = WorkFormRequest::validate($rawForm);
    $form = $this->normalizeForm($rawForm);

    // slug重複エラー
    if ($form['slug'] !== '' && $this->works->slugExists($form['slug'])) {
      $errors['slug'] = 'このURLスラッグはすでに使用されています';
    }

    // 画像アップロードエラー
    $thumbnail = $request->getUploadedFiles()['thumbnail'] ?? null;
    $thumbnailError = $this->validateThumbnail($thumbnail);
    if ($thumbnailError !== null) {
      $errors['thumbnail'] = $thumbnailError;
    }

    if ($errors !== []) {
      $_SESSION['admin-work-form'] = $form;
      $_SESSION['admin-work-errors'] = $errors;
      return $this->redirect($response, '/admin/works/create');
    }

    $thumbnailPath = null;

    try {
      $thumbnailPath = $this->storeThumbnail($thumbnail);
      $form['thumbnail'] = $thumbnailPath;
      $this->works->create($form, $this->validSkillIds($form['skill_ids']));
    } catch (\Throwable $error) {
      if ($thumbnailPath !== null) {
        $absolutePath = dirname(__DIR__, 3) . '/public/uploads/' . $thumbnailPath;
        if (is_file($absolutePath)) {
          unlink($absolutePath);
        }
      }

      $_SESSION['admin-work-form'] = $form;
      $_SESSION['admin-work-errors'] = [
        'common' => '実績を保存できませんでした。時間をおいて再度お試しください',
      ];
      return $this->redirect($response, '/admin/works/create');
    }

    unset($_SESSION['admin-work-form'], $_SESSION['admin-work-errors']);
    CsrfService::clear();
    $_SESSION['admin-work-success'] = '実績を保存しました';

    return $this->redirect($response, '/admin/works');
  }

  /**
   * 未保存の入力内容を公開画面用Twigでプレビュー表示
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

    // 入力してある値を反映
    $form = $this->normalizeForm($rawForm);
    $editingWork = null;
    $workId = filter_var(
      $rawForm['work_id'] ?? null,
      FILTER_VALIDATE_INT,
      FILTER_NULL_ON_FAILURE
    );

    if ($workId !== null && $workId > 0) {
      $editingWork = $this->works->findAdminById($workId);
    }

    $form['thumbnail'] = $editingWork['thumbnail'] ?? null;
    $work = $this->previewWork($form);

    if ($editingWork !== null) {
      $work['id'] = (int) $editingWork['id'];
    }

    if ($view === 'detail') {
      return $this->view->render(
        $response,
        'works/show.twig',
        [
          'work' => $work,
          'active_nav' => 'works',
        ]
      );
    }

    $publishedWorks = $this->works->publishedWorks();

    if ($editingWork !== null) {
      $publishedWorks = array_values(
        array_filter(
          $publishedWorks,
          static fn(array $publishedWork): bool =>
          (int) $publishedWork['id'] !== (int) $editingWork['id']
        )
      );
    }

    array_unshift($publishedWorks, $work);
    $workCategories = array_values(
      array_unique(
        array_filter(array_column($publishedWorks, 'category'))
      )
    );

    return $this->view->render(
      $response,
      'works/index.twig',
      [
        'publishedWorks' => $publishedWorks,
        'featuredWorks' => $this->works->featuredWorks(),
        'workCategories' => $workCategories,
        'active_nav' => 'works',
      ]
    );
  }

  /**
   * 編集
   */
  public function edit(Request $request, Response $response, array $args)
  {
    $id = (int) ($args['id'] ?? 0);
    $work = $this->works->findAdminById($id);

    if ($work === null) {
      $response->getBody()->write('実績が見つかりません');
      return $response->withStatus(404);
    }

    $formKey = 'admin-work-edit-form-' . $id;
    $errorKey = 'admin-work-edit-errors-' . $id;
    $form = $_SESSION[$formKey] ?? $work;
    $errors = $_SESSION[$errorKey] ?? [];
    unset($_SESSION[$formKey], $_SESSION[$errorKey]);

    $form['id'] = $id;
    $form['thumbnail'] ??= $work['thumbnail'];

    return $this->view->render(
      $response,
      'admin/works/form.twig',
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
   * 更新
   */
  public function update(Request $request, Response $response, array $args)
  {
    $id = (int) ($args['id'] ?? 0);
    $currentWork = $this->works->findAdminById($id);

    if ($currentWork === null) {
      $response->getBody()->write('実績が見つかりません');
      return $response->withStatus(404);
    }

    $rawForm = $request->getParsedBody();
    $rawForm = is_array($rawForm) ? $rawForm : [];
    $formKey = 'admin-work-edit-form-' . $id;
    $errorKey = 'admin-work-edit-errors-' . $id;
    $form = $this->normalizeForm($rawForm);
    $form['id'] = $id;
    $form['thumbnail'] = $currentWork['thumbnail'];

    if (!CsrfService::validate($rawForm)) {
      $_SESSION[$formKey] = $form;
      $_SESSION[$errorKey] = [
        'csrf' => '不正な送信です。もう一度お試しください',
      ];
      return $this->redirect($response, '/admin/works/' . $id . '/edit');
    }

    $errors = WorkFormRequest::validate($rawForm);

    if (
      $form['slug'] !== ''
      && $this->works->slugExists($form['slug'], $id)
    ) {
      $errors['slug'] = 'このURLスラッグはすでに使用されています';
    }

    $thumbnail = $request->getUploadedFiles()['thumbnail'] ?? null;
    $thumbnailError = $this->validateThumbnail($thumbnail);
    if ($thumbnailError !== null) {
      $errors['thumbnail'] = $thumbnailError;
    }

    if ($errors !== []) {
      $_SESSION[$formKey] = $form;
      $_SESSION[$errorKey] = $errors;
      return $this->redirect($response, '/admin/works/' . $id . '/edit');
    }

    $newThumbnailPath = null;

    try {
      $newThumbnailPath = $this->storeThumbnail($thumbnail);
      $form['thumbnail'] = $newThumbnailPath ?? $currentWork['thumbnail'];
      $this->works->update(
        $id,
        $form,
        $this->validSkillIds($form['skill_ids'])
      );
    } catch (\Throwable $error) {
      $this->deleteThumbnail($newThumbnailPath);
      $form['thumbnail'] = $currentWork['thumbnail'];
      $_SESSION[$formKey] = $form;
      $_SESSION[$errorKey] = [
        'common' => '実績を更新できませんでした。時間をおいて再度お試しください',
      ];
      return $this->redirect($response, '/admin/works/' . $id . '/edit');
    }

    if ($newThumbnailPath !== null) {
      $this->deleteThumbnail($currentWork['thumbnail']);
    }

    unset($_SESSION[$formKey], $_SESSION[$errorKey]);
    CsrfService::clear();
    $_SESSION['admin-work-success'] = '実績を更新しました';

    return $this->redirect($response, '/admin/works');
  }

  /**
   * 削除
   */
  public function delete(Request $request, Response $response, array $args)
  {
    $id = (int) ($args['id'] ?? 0);
    $work = $this->works->findAdminById($id);

    if ($work === null) {
      $response->getBody()->write('実績が見つかりません');
      return $response->withStatus(404);
    }

    $rawForm = $request->getParsedBody();
    $rawForm = is_array($rawForm) ? $rawForm : [];
    $errorKey = 'admin-work-edit-errors-' . $id;

    if (!CsrfService::validate($rawForm)) {
      $_SESSION[$errorKey] = [
        'csrf' => '不正な送信です。もう一度お試しください',
      ];
      return $this->redirect($response, '/admin/works/' . $id . '/edit');
    }

    try {
      $deleted = $this->works->delete($id);
    } catch (\Throwable $error) {
      $_SESSION[$errorKey] = [
        'common' => '実績を削除できませんでした。時間をおいて再度お試しください',
      ];
      return $this->redirect($response, '/admin/works/' . $id . '/edit');
    }

    if (!$deleted) {
      $response->getBody()->write('実績が見つかりません');
      return $response->withStatus(404);
    }

    $this->deleteThumbnail($work['thumbnail']);
    unset(
      $_SESSION['admin-work-edit-form-' . $id],
      $_SESSION[$errorKey]
    );
    CsrfService::clear();
    $_SESSION['admin-work-success'] = '実績を削除しました';

    return $this->redirect($response, '/admin/works');
  }

  /**
   * フォームの入力値を返す
   */
  private function normalizeForm(array $data)
  {
    $stringFields = [
      'title',
      'slug',
      'category',
      'summary',
      'description',
      'period',
      'github_url',
      'site_url',
    ];

    $form = [];

    // 文字列系は空白を除去して登録
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
    // 未入力の時は０を入れる
    $form['sort_order'] ??= 0;
    // 整数として保存
    $form['skill_ids'] = is_array($data['skill_ids'] ?? null)
      ? array_values(array_map('intval', $data['skill_ids']))
      : [];

    return $form;
  }

  /**
   * プレビュー表示用
   */
  private function previewWork(array $form)
  {
    return [
      'id' => 0,
      'is_preview' => true,
      'title' => $form['title'] !== '' ? $form['title'] : '実績名を入力',
      'slug' => $form['slug'] !== '' ? $form['slug'] : 'preview',
      'category' => $form['category'] !== '' ? $form['category'] : 'Category',
      'summary' => $form['summary'] !== ''
        ? $form['summary']
        : '概要を入力すると、ここにプレビュー表示されます。',
      'description' => $form['description'] !== ''
        ? $form['description']
        : '詳細説明を入力すると、ここにプレビュー表示されます。',
      'thumbnail' => $form['thumbnail'] ?? null,
      'period' => $form['period'] !== '' ? $form['period'] : '未設定',
      'github_url' => $form['github_url'] !== '' ? $form['github_url'] : '#',
      'site_url' => $form['site_url'] !== '' ? $form['site_url'] : '#',
      'skills' => $this->selectedSkills($form['skill_ids']),
    ];
  }

  /**
   * 正しいスキルデータを取得
   * プレビュー表示に使用
   */
  private function selectedSkills($selectedIds)
  {
    // スキルidを整数にする
    $selectedIds = array_map('intval', $selectedIds);
    $selectedSkills = [];

    // すでに登録済みのスキルidを取得
    foreach ($this->skills->allSkills() as $skill) {
      $skillId = (int) $skill['id'];

      // 合致すればスキルとして登録
      if (in_array($skillId, $selectedIds, true)) {
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
  private function validSkillIds(array $selectedIds)
  {
    $skills = $this->selectedSkills($selectedIds);
    $skillIds = array_column($skills, 'id');

    // スキルIDを整数として取得
    return array_map('intval', $skillIds);
  }

  /**
   * 画像を検証
   * アップロードファイルまたはnullを受け取る
   */
  private function validateThumbnail(?UploadedFileInterface $thumbnail)
  {
    // 画像がアップロードされていない場合
    if ($thumbnail === null || $thumbnail->getError() === UPLOAD_ERR_NO_FILE) {
      return null;
    }

    // 通信関係でエラーになった場合
    if ($thumbnail->getError() !== UPLOAD_ERR_OK) {
      return '画像をアップロードできませんでした';
    }

    // ファイルサイズ制限
    if (($thumbnail->getSize() ?? 0) > 5 * 1024 * 1024) {
      return '画像は5MB以内にしてください';
    }

    // ストリームとして取得
    $stream = $thumbnail->getStream();
    $contents = $stream->getContents();

    // 画像の読み取り位置を先頭に戻す
    $stream->rewind();

    // アップロード画像がちゃんと画像か検証
    // 拡張子は正しいが、中身がPDFという事態を避ける
    $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents);

    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
      return 'JPEG、PNG、WebP形式の画像を選択してください';
    }
    return null;
  }

  /**
   * 画像を保存（画像パスを保存）
   */
  private function storeThumbnail(?UploadedFileInterface $thumbnail)
  {
    // 画像がなかったらnull
    if ($thumbnail === null || $thumbnail->getError() === UPLOAD_ERR_NO_FILE) {
      return null;
    }

    // ストリームとして取得
    $stream = $thumbnail->getStream();
    $contents = $stream->getContents();

    // 画像の読み取り位置を先頭に戻す
    $stream->rewind();
    $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents);
    $extensions = [
      'image/jpeg' => 'jpg',
      'image/png' => 'png',
      'image/webp' => 'webp',
    ];
    $extension = $extensions[$mime];

    // 保存先
    $directory = dirname(__DIR__, 3) . '/public/uploads/works';

    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
      throw new \RuntimeException('アップロード先を作成できませんでした');
    }

    // 元のファイル名から、保存に使えない文字とパス情報を除去
    $clientFilename = $thumbnail->getClientFilename() ?? 'image';
    $clientFilename = basename(str_replace('\\', '/', $clientFilename));

    // ファイル名を定義
    $baseName = pathinfo($clientFilename, PATHINFO_FILENAME);
    // ファイル名として使えない文字を_に変換
    $baseName = preg_replace('/[<>:"\/\\|?*\x00-\x1F\x7F]/u', '_', $baseName);
    // 空白とpピリオドは削除
    $baseName = trim((string) $baseName, " .");
    // ファイル名が長すぎないように制限（180バイトまで）
    $baseName = mb_strcut($baseName, 0, 180, 'UTF-8');

    // ファイル名がないときはimageと命名
    if ($baseName === '') {
      $baseName = 'image';
    }

    // 同名ファイルがある場合は、拡張子の前に1、2…を付ける
    $filename = $baseName . '.' . $extension;
    $suffix = 1;

    while (file_exists($directory . '/' . $filename)) {
      $filename = $baseName . $suffix . '.' . $extension;
      $suffix++;
    }

    $thumbnail->moveTo($directory . '/' . $filename);

    return 'works/' . $filename;
  }

  /**
   * 保存済みの実績画像を削除
   */
  private function deleteThumbnail(?string $thumbnailPath): void
  {
    if ($thumbnailPath === null || !str_starts_with($thumbnailPath, 'works/')) {
      return;
    }

    $filename = basename($thumbnailPath);
    $absolutePath = dirname(__DIR__, 3) . '/public/uploads/works/' . $filename;

    if (is_file($absolutePath)) {
      unlink($absolutePath);
    }
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
