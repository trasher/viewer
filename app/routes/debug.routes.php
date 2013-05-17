<?php
/**
 * Bach's viewer images routes
 *
 * PHP version 5
 *
 * @category Routes
 * @package  Viewer
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  Unknown http://unknown.com
 * @link     http://anaphore.eu
 */

use \Bach\Viewer\Picture;

$app->get(
    '/dbg/show/:uri',
    function ($uri) use ($app, $conf) {
        $picture = new Picture(base64_decode($uri), null, $conf->getFormats());
        var_dump($picture);
    }
);

