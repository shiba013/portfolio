<?php

use App\Controller\HomeController;
use App\Controller\AboutController;
use App\Controller\CasesController;
use App\Controller\ContactController;
use App\Controller\WorksController;

// 管理画面側
use App\Controller\Admin\HomeController as AdminHomeController;

/**
 * public/index.phpから$appを取得しているため未定義
 * @var \Slim\App $app
 */
$app->get('/', [HomeController::class, 'index']);
$app->get('/about', [AboutController::class, 'index']);
$app->get('/cases', [CasesController::class, 'index']);
$app->get('/cases/{slug}', [CasesController::class, 'show']);
$app->get('/contact', [ContactController::class, 'index']);
$app->post('/confirm', [ContactController::class, 'confirm']);
$app->post('/back', [ContactController::class, 'back']);
$app->post('/complete', [ContactController::class, 'complete']);
$app->get('/thanks', [ContactController::class, 'thanks']);
$app->get('/works', [WorksController::class, 'index']);
$app->get('/works/{slug}', [WorksController::class, 'show']);

// 管理画面側
$app->get('/admin', [AdminHomeController::class, 'index']);
