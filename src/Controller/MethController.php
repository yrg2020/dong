<?php
/**
 * restful controller
 *
 * PHP Version 7.2
 *
 * @author    v.k
 * @copyright 2019 ec3s.com
 */

namespace CXD2020\Controller;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Lumen\Routing\Controller;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use CXD2020\Contracts\RestfulErrorMessage;
use CXD2020\Contracts\RestfulException;


/**
 * restful controller
 */
abstract class MethController extends Controller
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * MethController constructor.
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @var array
     */
    public static $allowedMethods = ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE'];


    /**
     * handle request
     *
     * @param array $args
     * @return Response
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function handle(...$args)
    {
        $method = $this->request->getMethod();
        if (!in_array($method, self::$allowedMethods) || !method_exists($this, strtolower($method))) {
            throw new MethodNotAllowedHttpException(self::$allowedMethods);
        }
        $this->validateMethodWithRules($method);

        /** @var Response $response */
        $response = call_user_func_array([$this, $method], $args);

        if ($response) {
            if (!$response->headers) {
                $response->headers = new ResponseHeaderBag();
            }
            if ($response instanceof Response) {
                if (!$response->headers->get('Content-Type')) {
                    $response->headers->set('Content-Type', 'application/json');
                }
            }
        }

        return $response;
    }

    /**
     * @param bool $validateJson
     * @param int  $minCount
     *
     * @return bool
     * @throws \CXD2020\Contracts\RestfulException
     */
    protected function payloadIsAttributeSet($validateJson = true, $minCount = 1)
    {
        if ($validateJson && $this->request->json()->count() < $minCount) {
            throw new RestfulException(RestfulErrorMessage::InvalidRequestPayload);
        }
        $content = $this->request->getContent();
        return $content && $this->request->isJson() && $content[0] === '[' ? true : false;
    }

    /**
     * @param $method
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateMethodWithRules($method)
    {
        $rules = $this->validationRules($method);
        if (!empty($rules)) {
            $this->validate($this->request, $rules);
        }
    }

    /**
     *  provider validation rules for request
     *
     * @param string $method
     *
     * @return array
     */
    protected function validationRules($method)
    {
        return [];
    }

    /**
     * api doc
     *
     * @return array
     */
    public static function doc(): array
    {
        return [];
    }
}