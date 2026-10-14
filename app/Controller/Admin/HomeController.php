<?php

namespace App\Controller\Admin;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use App\Repository\CaseRepository;
use App\Repository\ContactRepository;
use App\Repository\SkillRepository;
use App\Repository\WorkRepository;

class HomeController
{
  private Twig $view;
  private CaseRepository $cases;
  private ContactRepository $contacts;
  private SkillRepository $skills;
  private WorkRepository $works;

  public function __construct(
    Twig $view,
    CaseRepository $cases,
    ContactRepository $contacts,
    SkillRepository $skills,
    WorkRepository $works
  ) {
    $this->view = $view;
    $this->cases = $cases;
    $this->contacts = $contacts;
    $this->skills = $skills;
    $this->works = $works;
  }

  /**
   * ダッシュボード
   */
  public function index(Request $request, Response $response)
  {
    // 実績件数取得
    $workCounts = $this->works->workCounts();

    // 事例件数取得
    $caseCounts = $this->cases->caseCounts();

    // スキル件数取得
    $skillCounts = $this->skills->skillCounts();

    // 未対応問い合わせ件数取得
    $findStatusUnhandled = $this->contacts->findStatusUnhandled();

    // 最近の更新
    return $this->view->render(
      $response,
      'admin/index.twig',
      [
        'workCounts' => $workCounts,
        'caseCounts' => $caseCounts,
        'skillCounts' => $skillCounts,
        'findStatusUnhandled' => $findStatusUnhandled
      ]
    );
  }
}
