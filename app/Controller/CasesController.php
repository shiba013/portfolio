<?php

namespace App\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use App\Repository\CaseRepository;

class CasesController
{
  private Twig $view;
  private CaseRepository $cases;

  public function __construct(
    Twig $view,
    CaseRepository $cases
  ) {
    $this->view = $view;
    $this->cases = $cases;
  }

  /**
   * 事例一覧
   */
  public function index(Request $request, Response $response)
  {
    $publishedCases = $this->cases->publishedCases();
    $featuredCases = $this->cases->featuredCases();

    // 公開中の事例に紐づくスキルのみを抽出
    $caseSkills = [];
    foreach ($publishedCases as $case) {
      foreach ($case['skills'] as $skill) {
        $caseSkills[$skill['id']] = $skill;
      }
    }
    $caseSkills = array_values($caseSkills);

    return $this->view->render(
      $response,
      'cases/index.twig',
      [
        'publishedCases' => $publishedCases,
        'featuredCases' => $featuredCases,
        'caseSkills' => $caseSkills
      ]
    );
  }


  /**
   * 詳細画面
   */
  public function show(Request $request, Response $response, $args)
  {
    $slug = $args['slug'];
    $case = $this->cases->findPublishedSlug($slug);

    return $this->view->render(
      $response,
      'cases/show.twig',
      [
        'case' => $case
      ]
    );
  }
}
