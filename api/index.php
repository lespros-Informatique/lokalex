<?php

require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Response.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/Shop.php';
require_once __DIR__ . '/models/Categorie.php';
require_once __DIR__ . '/models/Article.php';
require_once __DIR__ . '/models/Client.php';
require_once __DIR__ . '/models/Location.php';
require_once __DIR__ . '/models/LigneLocation.php';
require_once __DIR__ . '/models/Paiement.php';
require_once __DIR__ . '/models/Forfait.php';
require_once __DIR__ . '/models/Abonnement.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/DashboardController.php';
require_once __DIR__ . '/controllers/CategorieController.php';
require_once __DIR__ . '/controllers/ArticleController.php';
require_once __DIR__ . '/controllers/ClientController.php';
require_once __DIR__ . '/controllers/LocationController.php';
require_once __DIR__ . '/controllers/HistoryController.php';
require_once __DIR__ . '/controllers/ReportController.php';
require_once __DIR__ . '/controllers/SearchController.php';
require_once __DIR__ . '/controllers/DeveloperController.php';
require_once __DIR__ . '/controllers/SubscriptionController.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$parentDir = dirname($scriptDir);

if ($parentDir !== '/' && strpos($uri, $parentDir) === 0) {
    $uri = substr($uri, strlen($parentDir));
}

if ($uri === '' || $uri[0] !== '/') {
    $uri = '/' . $uri;
}

$routes = [
    'POST' => [
        '/api/auth/login' => [AuthController::class, 'login'],
        '/api/auth/logout' => [AuthController::class, 'logout'],
        '/api/categories' => [CategorieController::class, 'store'],
        '/api/articles' => [ArticleController::class, 'store'],
        '/api/articles/update' => [ArticleController::class, 'update'],
        '/api/articles/desactiver' => [ArticleController::class, 'desactiver'],
        '/api/clients' => [ClientController::class, 'store'],
        '/api/locations' => [LocationController::class, 'store'],
        '/api/locations/retour' => [LocationController::class, 'retour'],
        '/api/locations/paiement' => [LocationController::class, 'addPaiement'],
        '/api/abonnements' => [SubscriptionController::class, 'subscribe'],
        '/api/dev/users' => [DeveloperController::class, 'createUser'],
        '/api/dev/shops' => [DeveloperController::class, 'createShop'],
        '/api/dev/forfaits' => [DeveloperController::class, 'createForfait'],
        '/api/dev/abonnement/statut' => [DeveloperController::class, 'setAbonnementStatut'],
        '/api/dev/abonnements' => [DeveloperController::class, 'createAbonnement'],
    ],
    'GET' => [
        '/api/auth/me' => [AuthController::class, 'me'],
        '/api/dashboard' => [DashboardController::class, 'index'],
        '/api/history' => [HistoryController::class, 'index'],
        '/api/reports' => [ReportController::class, 'index'],
        '/api/search' => [SearchController::class, 'search'],
        '/api/forfaits' => [SubscriptionController::class, 'listForfaits'],
        '/api/articles' => [ArticleController::class, 'index'],
        '/api/categories' => [CategorieController::class, 'index'],
        '/api/clients' => [ClientController::class, 'index'],
        '/api/clients/search' => [ClientController::class, 'search'],
        '/api/clients/historique' => [ClientController::class, 'historique'],
        '/api/locations' => [LocationController::class, 'index'],
        '/api/locations/show' => [LocationController::class, 'show'],
        '/api/dev/users' => [DeveloperController::class, 'listUsers'],
        '/api/dev/user-detail' => [DeveloperController::class, 'userDetail'],
        '/api/dev/shops' => [DeveloperController::class, 'listShops'],
        '/api/dev/shop-detail' => [DeveloperController::class, 'shopDetail'],
        '/api/dev/forfaits' => [DeveloperController::class, 'listForfaitsDev'],
        '/api/dev/abonnements' => [DeveloperController::class, 'listAbonnements'],
    ],
];

if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$handler = $routes[$method][$uri] ?? null;

if (!$handler) {
    Response::error('Endpoint non trouvé', [], 404);
}

[$controllerClass, $action] = $handler;
$controller = new $controllerClass();
$controller->$action();
