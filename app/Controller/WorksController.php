<?php

namespace App\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use App\Repository\WorkRepository;

class WorksController
{
  private Twig $view;
  private WorkRepository $works;

  public function __construct(
    Twig $view,
    WorkRepository $works
  ) {
    $this->view = $view;
    $this->works = $works;
  }

  /**
   * 制作事例一覧
   */
  public function index(Request $request, Response $response)
  {
    $publishedWorks = $this->works->publishedWorks();
    $featuredWorks = $this->works->featuredWorks();

    // 公開中の実績からカテゴリーのみを抽出
    $workCategories = array_values(
      array_unique(
        array_filter(
          array_column($publishedWorks, 'category')
        )
      )
    );

    return $this->view->render(
      $response,
      'works/index.twig',
      [
        'publishedWorks' => $publishedWorks,
        'featuredWorks' => $featuredWorks,
        'workCategories' => $workCategories
      ]
    );
  }

  /**
   * 詳細画面
   */
  public function show(Request $request, Response $response, $args)
  {
    $slug = $args['slug'];
    $work = $this->works->findPublishedSlug($slug);

    return $this->view->render(
      $response,
      'works/show.twig',
      [
        'work' => $work
      ]
    );
  }
}
