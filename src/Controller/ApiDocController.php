<?php
/**
 * route map
 *
 * PHP Version 7.2
 *
 * @author    v.k <string@ec3s.com>
 * @copyright 2018 Xingchangxinda Inc.
 */

namespace DONG2020\Controller;

use Illuminate\Support\Str;
use DONG2020\Contracts\RestfulErrorMessage;
use DONG2020\Contracts\RestfulException;


/**
 * route map
 */
class ApiDocController extends MethController
{
    /**
     * @var \DONG2020\Router
     */
    protected $router;

    /**
     * @return array
     */
    public static function doc(): array
    {
        return [
            'desc' => 'api doc present by swagger ui'
        ];
    }

    /**
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     * @throws \DONG2020\Contracts\RestfulException
     */
    public function get()
    {
        if (env('APP_ENV') === 'prod') {
            throw new RestfulException(RestfulErrorMessage::NotFound);
        }

        $presentFile = $this->request->query->get('file', 'index.html');

        if ($presentFile == 'json') {
            if (!file_exists(\config('DONG2020.apiJsonFile'))) {
                throw new RestfulException(RestfulErrorMessage::NotFound, 'api json file not exists');
            }
            $presentFile = \config('DONG2020.apiJsonFile');
        } else {
            $presentFile = sprintf('%s/view/swagger-ui/%s', dirname(__DIR__), $presentFile);
        }

        if (!file_exists($presentFile)) {
            throw new RestfulException(RestfulErrorMessage::NotFound);
        }

        $content = file_get_contents($presentFile);
        $response = \response($content);
        $response->headers->set('content-type', $this->getContentType($presentFile));
        return $response;
    }

    /**
     * @param $file
     * @return mixed
     */
    protected function getContentType($file)
    {
        $metaMap = [
            'html' => 'text/html;charset=utf-8',
            'json' => 'application/json',
            'css'  => 'text/css',
            'js'   => 'application/javascript'
        ];
        $ext = (new \SplFileInfo($file))->getExtension();
        return $metaMap[$ext];
    }

}
