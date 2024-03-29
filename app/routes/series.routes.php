<?php
/**
 * Bach's viewer series routes
 *
 * PHP version 5
 *
 * Copyright (c) 2014, Anaphore
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are
 * met:
 *
 *     (1) Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *     (2) Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *     (3)The name of the author may not be used to
 *    endorse or promote products derived from this software without
 *    specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR
 * IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT,
 * INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT,
 * STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING
 * IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @category Routes
 * @package  Viewer
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */

use \Bach\Viewer\Series;
use \Bach\Viewer\Picture;

$app->get(
    '/series/:path+',
    function ($path) use ($app, $conf, $app_base_url) {
        $request = $app->request();
        $start = $request->params('s');
        if ( trim($start) === '' ) {
            $start = null;
        }
        $end = $request->params('e');
        if ( trim($end) === '' ) {
            $end = null;
        }

        if ( $start === null && $end !== null || $start !== null && $end === null ) {
            $start = null;
            $end = null;
            throw new \RuntimeException(
                _('Sub series cannot be instancied; missing one of start or end param!')
            );
        }

        $series = null;
        try {
            $series = new Series(
                $conf,
                implode('/', $path),
                $app_base_url,
                $start,
                $end
            );
        } catch ( \RuntimeException $e ) {
            Analog::log(
                _('Cannot load series: ') . $e->getMessage(),
                Analog::ERROR
            );
            $app->pass();
        }

        //check if series has content, throw an error if not
        if ( $series->getCount() === 0 ) {
            throw new \RuntimeException(
                str_replace(
                    '%s',
                    $series->getPath(),
                    _('Series "%s" is empty!')
                )
            );
        }

        $img = null;
        if ( $request->params('img') !== null ) {
            //get image from its name
            if ( $series->setImage($request->params('img')) ) {
                $img = $series->getImage();
            }
        } else if ( $request->get('num') !== null ) {
            //get image from its position
            if ( $series->setNumberedImage($request->params('num')) ) {
                $img = $series->getImage();
            }
        }

        if ( $img === null ) {
            $img = $series->getRepresentative();
        }

        $picture = new Picture(
            $conf,
            $img,
            $app_base_url,
            $series->getFullPath()
        );

        $args = array(
            'img'       => $img,
            'picture'   => $picture,
            'iip'       => $picture->isPyramidal(),
            'series'    => $series,
        );

        if ( $picture->isPyramidal() ) {
            $iip = $conf->getIIP();
            $args['iipserver'] = $iip['server'];
        } else {
            $args['image_format'] = 'default';
        }

        $app->render(
            'index.html.twig',
            $args
        );

    }
);
