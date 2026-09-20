<?php

namespace App\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use App\Repository\SkillRepository;
use App\Repository\WorkRepository;
use App\Repository\CaseRepository;

class HomeController
{
  private Twig $view;
  private SkillRepository $skills;
  private WorkRepository $works;
  private CaseRepository $cases;

  public function __construct(
    Twig $view,
    SkillRepository $skills,
    WorkRepository $works,
    CaseRepository $cases
  ) {
    $this->view = $view;
    $this->skills = $skills;
    $this->works = $works;
    $this->cases = $cases;
  }

  /**
   * トップページ
   */
  public function index(Request $request, Response $response)
  {
    // スキル
    $publishedSkills = $this->skills->publishedSkills();
    $publishedSkillsCategory = $this->skills->publishedSkillsCategory();

    // 実績
    $publishedWorks = $this->works->publishedWorks();
    $featuredWorks = $this->works->featuredWorks();

    // 事例
    $publishedCases = $this->cases->publishedCases();
    $featuredCases = $this->cases->featuredCases();

    return $this->view->render(
      $response,
      'home/index.twig',
      [
        'publishedSkills' => $publishedSkills,
        'publishedSkillsCategory' => $publishedSkillsCategory,
        'publishedWorks' => $publishedWorks,
        'featuredWorks' => $featuredWorks,
        'publishedCases' => $publishedCases,
        'featuredCases' => $featuredCases
      ]
    );
  }
}
