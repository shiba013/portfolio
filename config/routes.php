<?php

use App\Controller\HomeController;
use App\Controller\AboutController;
use App\Controller\CasesController;
use App\Controller\ContactController;
use App\Controller\WorksController;

// 管理画面側
use App\Controller\Admin\HomeController as AdminHomeController;
use App\Controller\Admin\CasesController as AdminCasesController;
use App\Controller\Admin\ContactsController as AdminContactsController;
use App\Controller\Admin\SkillsController as AdminSkillsController;
use App\Controller\Admin\WorksController as AdminWorksController;

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
$app->get('/admin/contacts', [AdminContactsController::class, 'index']);
$app->get('/admin/contacts/{id:[0-9]+}', [AdminContactsController::class, 'show']);
$app->post('/admin/contacts/{id:[0-9]+}', [AdminContactsController::class, 'update']);
$app->get('/admin/cases', [AdminCasesController::class, 'index']);
$app->get('/admin/cases/create', [AdminCasesController::class, 'create']);
$app->get('/admin/cases/{id:[0-9]+}/edit', [AdminCasesController::class, 'edit']);
$app->post('/admin/cases', [AdminCasesController::class, 'store']);
$app->post('/admin/cases/{id:[0-9]+}', [AdminCasesController::class, 'update']);
$app->post('/admin/cases/{id:[0-9]+}/delete', [AdminCasesController::class, 'delete']);
$app->post('/admin/cases/preview/{view}', [AdminCasesController::class, 'preview']);
$app->get('/admin/skills', [AdminSkillsController::class, 'index']);
$app->get('/admin/skills/create', [AdminSkillsController::class, 'create']);
$app->get('/admin/skills/{id:[0-9]+}/edit', [AdminSkillsController::class, 'edit']);
$app->post('/admin/skills', [AdminSkillsController::class, 'store']);
$app->post('/admin/skills/{id:[0-9]+}', [AdminSkillsController::class, 'update']);
$app->post('/admin/skills/{id:[0-9]+}/delete', [AdminSkillsController::class, 'delete']);
$app->get('/admin/works', [AdminWorksController::class, 'index']);
$app->get('/admin/works/create', [AdminWorksController::class, 'create']);
$app->get('/admin/works/{id:[0-9]+}/edit', [AdminWorksController::class, 'edit']);
$app->post('/admin/works', [AdminWorksController::class, 'store']);
$app->post('/admin/works/{id:[0-9]+}', [AdminWorksController::class, 'update']);
$app->post('/admin/works/{id:[0-9]+}/delete', [AdminWorksController::class, 'delete']);
$app->post('/admin/works/preview/{view}', [AdminWorksController::class, 'preview']);
