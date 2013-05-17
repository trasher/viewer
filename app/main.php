<?php
/**
 * Bach's viewer app
 *
 * PHP version 5
 *
 * @category Main
 * @package  Viewer
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  Unknown http://unknown.com
 * @link     http://anaphore.eu
 */

use \Slim\Slim;
use \Slim\Route;
use \Slim\Extras\Views\Twig;
use \Bach\Viewer\Conf;
use \Analog\Analog;

session_start();
$session = &$_SESSION['bachviewer'];

require APP_DIR . '/../vendor/autoload.php';
$logger = null;
if ( defined('APP_TESTS') ) {
    $viewer_log = '';
    $logger = \Analog\Handler\Variable::init($viewer_log);
} else {
    $log_file = APP_DIR . '/logs/viewer.log';
    $logger = \Analog\Handler\File::init($log_file);
}
Analog::handler($logger);

$conf = new Conf();

/** I18n stuff */
// Set language to French
// FIXME: use user language
putenv('LC_ALL=fr_FR.utf8');
setlocale(LC_ALL, 'fr_FR.utf8');

//Specify the location of the translation tables
bindtextdomain('BachViewer', APP_DIR . '/locale');
bind_textdomain_codeset('BachViewer', 'UTF-8');

//Choose domain
textdomain('BachViewer');
/** /I18n stuff */

$app = new Slim(
    array(
        'debug'             => APP_DEBUG,
        'view'              => new Twig(),
        'templates.path'    => APP_DIR . '/views'
    )
);

Twig::$twigExtensions = array(
    new Twig_Extensions_Extension_I18n()
);

if ( defined('APP_CACHE') && APP_CACHE !== false ) {
    Twig::$twigOptions = array(
        'cache'         => APP_CACHE,
        'auto_reload'   => true
    );
}

//TODO: parametize
define('DEFAULT_PICTURE', 'main.jpg');

$app->hook(
    'slim.before.dispatch',
    function () use ($app, $conf) {
        //let's send view parameters before dispatching
        $v = $app->view();
        $v->setData(
            'enable_right_click',
            $conf->getUI()['enable_right_click']
        );
    }
);

//set default conditions
Route::setDefaultConditions(
    array(
        'image' => '.+\.[a-zA-Z]{3,4}'
    )
);

$app->notFound(
    function () use ($app) {
        $app->render('404.html.twig');
    }
);

//main route
$app->get(
    '/',
    function () use ($app) {
        $app->redirect('/viewer/' . DEFAULT_PICTURE);
    }
);

//include routes files
require_once 'routes/images.routes.php';
require_once 'routes/series.routes.php';
require_once 'routes/ajax.routes.php';
if ( APP_DEBUG === true ) {
    include_once 'routes/debug.routes.php';
}

if ( !defined('APP_TESTS') ) {
    $app->run();
}
