<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use App\Controllers\AnalyticsController;
use App\Controllers\BlogController;
use App\Controllers\HomeController;
use App\Controllers\ProductController;
use App\Controllers\SitemapController;
use App\Support\Router;

/**
 * MUNG CMS V1
 * File: index.php
 * Author: Firmanda Himawan
 * Description: Front controller for MUNG CMS routing user-facing requests.
 */

$router = new Router();

$router->get('/', [HomeController::class, 'index']);
$router->get('/blog', [BlogController::class, 'index']);
$router->get('/blog/{slug}', [BlogController::class, 'show']);
$router->get('/produk', [ProductController::class, 'index']);
$router->get('/produk/{slug}', [ProductController::class, 'show']);
$router->get('/sitemap.xml', [SitemapController::class, 'index']);
$router->post('/api/track/product-click', [AnalyticsController::class, 'logProductClick']);
$router->post('/api/track/marketplace-click', [AnalyticsController::class, 'logMarketplaceClick']);
$router->post('/api/track/lead', [AnalyticsController::class, 'logLead']);

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
