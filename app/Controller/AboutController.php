<?php

namespace App\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use App\Repository\SkillRepository;

class AboutController
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

  /**
   * 自己紹介表示
   */
  public function index(Request $request, Response $response)
  {
    $publishedSkills = $this->skills->publishedSkills();

    return $this->view->render(
      $response,
      'about/index.twig',
      [
        'publishedSkills' => $publishedSkills
      ]
    );
  }
}
