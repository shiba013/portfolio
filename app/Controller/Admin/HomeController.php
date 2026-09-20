<?php

namespace App\Controller\Admin;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;

class HomeController
{
  private Twig $view;

  public function __construct(
    Twig $view
  ) {
    $this->view = $view;
  }

  public function index(Request $request, Response $response)
  {
    return $this->view->render(
      $response,
      'admin/index.twig',
    );
  }
}
