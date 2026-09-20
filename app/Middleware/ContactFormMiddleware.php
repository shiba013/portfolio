<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use App\Service\CsrfService;

class ContactFormMiddleware implements MiddlewareInterface
{
  public function process(Request $request, Handler $handler): Response
  {
    // URLからパスを取得する
    $path = $request->getUri()->getPath();

    // session対象のパス
    $formPath = ['/contact', '/confirm', '/back', '/complete'];

    // 別ページへ移動した時だけsessionを破棄する
    if ($request->getMethod() === 'GET' && !in_array($path, $formPath, true) && !str_contains($path, '.')) {
      unset($_SESSION['contact-form'], $_SESSION['contact-errors']);
      CsrfService::clear();
    }

    return $handler->handle($request);
  }
}
