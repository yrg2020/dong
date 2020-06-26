<?php
/**
 * restful controller
 *
 * PHP Version 7.2
 *
 * @author    v.k
 * @copyright 2019 ec3s.com
 */

namespace DONG2020\Controller;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\ParameterBag;
use DONG2020\Contracts\CollectionFindOption;
use DONG2020\Contracts\IResourceRepository;
use DONG2020\Contracts\PaginationOption;
use DONG2020\Contracts\RestfulErrorMessage;
use DONG2020\Contracts\RestfulException;
use DONG2020\Contracts\RestfulRequest;


/**
 * restful controller
 */
abstract class RestfulController extends MethController
{

    /**
     * @var RestfulRequest
     */
    protected $restfulRequest;

    /**
     * @var string
     */
    protected $idKey;


    /**
     * @var IResourceRepository
     */
    private $_repository;

    /**
     * @return IResourceRepository
     */
    public abstract function getRepository();

    /**
     * controller names
     *
     * @var array
     */
    private static $_controllerNames = [];

    /**
     * @return string
     */
    public static function name()
    {
        if (!isset(self::$_controllerNames[get_called_class()])) {
            return '';
        }
        return self::$_controllerNames[get_called_class()];
    }

    /**
     * @param string $name
     */
    public static function setName(string $name)
    {
        self::$_controllerNames[get_called_class()] = $name;
    }

    /**
     * prepare for handle request
     */
    protected function prepare()
    {
        $this->idKey = \config('DONG2020.idKey', 'id');
        $this->restfulRequest = new RestfulRequest($this->request);
        $this->_repository = $this->getRepository();
    }

    /**
     * @param array ...$args
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Validation\ValidationException
     * @throws \DONG2020\Contracts\RestfulException
     */
    public function handle(...$args)
    {
        $this->prepare();
        try {
            return parent::handle($args);
        } catch (\InvalidArgumentException $e) {
            switch ($e->getCode()) {
                case 101:
                    throw new RestfulException(
                        RestfulErrorMessage::InvalidRequestPayload, $e->getMessage()
                    );
                    break;
            }
            throw $e;
        }
    }

    /**
     * @return null|\DONG2020\Contracts\Collection|\DONG2020\Contracts\Document
     * @throws \Throwable
     */
    private function _get()
    {
        if ($this->restfulRequest->resourceId) {
            $document = $this->_repository->find(
                $this->restfulRequest->resourceId,
                $this->restfulRequest->withRels
            );
            \throw_if(!$document, new RestfulException(RestfulErrorMessage::NotFound));
            return $document;
        }
        return $this->_repository->findCollection(
            $this->getFindOption(),
            $this->restfulRequest->withRels,
            $this->restfulRequest->relsQuery
        );
    }

    /**
     * get for get a document or query a collection
     *
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     * @throws \Throwable
     */
    protected function get()
    {
        return \response($this->_get());
    }


    /**
     * head for check document/collection exists
     *
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     * @throws \Throwable
     */
    protected function head()
    {
        $result = $this->_get();
        if ($this->restfulRequest->resourceId) {
            return \response(null, 200, ['ETag' => $result->meta->etag]);
        }
        return \response(null, 200, ['X-Total' => $result->meta->total]);
    }


    /**
     * put for update or batch update documents
     *
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     * @throws \Throwable
     */
    protected function put()
    {
        \throw_if(!$this->request->isJson(), new RestfulException(RestfulErrorMessage::InvalidJsonHeader));
        if ($this->restfulRequest->resourceId) {
            $document = null;
            try {
                $document = $this->_get();
            } catch (\Exception $e) {
                // ...
            }
            return \response(
                $this->_repository->replace($document, $this->restfulRequest->resourceId, $this->request->json()->all())
            );
        }
        if ($this->payloadIsAttributeSet()) {
            return \response(
                $this->_repository->batchReplace($this->getFindOption(), $this->request->json()->all())
            );
        }
        throw new RestfulException(RestfulErrorMessage::InvalidRequestPayload);
    }

    /**
     * post for create or batch create documents
     *
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     * @throws \Throwable
     */
    protected function post()
    {
        \throw_if($this->restfulRequest->resourceId, new RestfulException(RestfulErrorMessage::MethodNotAllowed));
        \throw_if(!$this->request->isJson(), new RestfulException(RestfulErrorMessage::InvalidJsonHeader));
        if ($this->payloadIsAttributeSet()) {
            return \response($this->_repository->batchCreate($this->request->json()->all()), 201);
        }
        return \response(
            $this->_repository->create(
                $this->request->json()->all(),
                $this->restfulRequest->withRels
            ),
            201
        );
    }


    /**
     * patch for update a document
     *
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     * @throws \Throwable
     */
    protected function patch()
    {
        \throw_if(!$this->request->isJson(), new RestfulException(RestfulErrorMessage::InvalidJsonHeader));
        if ($this->restfulRequest->resourceId) {
            $document = $this->_get();
            return \response(
                $this->_repository->update($document, $this->request->json()->all())
            );
        }
        return \response(
            $this->_repository->batchUpdate($this->getFindOption(), $this->request->json()->all())
        );
    }

    /**
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     * @throws RestfulException
     */
    protected function delete()
    {
        if ($this->restfulRequest->resourceId) {
            $ret = $this->_repository->destroy($this->restfulRequest->resourceId);
            if ($ret) {
                return \response(null, 204);
            }
            throw new RestfulException(RestfulErrorMessage::NotFound);
        }

        $keys = $this->request->get(
            $this->idKey,
            $this->restfulRequest->params->get($this->idKey)
        );
        if ($keys && !empty($keys)) {
            $keys = is_array($keys) ? json_encode($keys) : $keys;
            $this->_repository->batchDestroy(
                new CollectionFindOption(
                    new ParameterBag([$this->idKey => $keys]),
                    null, [], true
                )
            );
            return \response(null, 204);
        }

        throw new RestfulException(RestfulErrorMessage::UnsafeOperation);
    }

    /**
     * @return CollectionFindOption
     */
    protected function getFindOption()
    {
        return new CollectionFindOption(
            $this->restfulRequest->params,
            new PaginationOption($this->restfulRequest->page, $this->restfulRequest->limit),
            $this->restfulRequest->sorts,
            $this->restfulRequest->all
        );
    }


    /**
     * @param $method
     *
     * @throws \Illuminate\Validation\ValidationException
     * @throws RestfulException
     */
    protected function validateMethodWithRules($method)
    {
        $rules = $this->validationRules($method);
        if (!empty($rules)) {
            if (!$this->payloadIsAttributeSet()) {
                return parent::validateMethodWithRules($method);
            }
            foreach ($this->request->json() as $index => $payload) {
                $this->validate(
                    Request::create($this->request->getUri(), $method, $payload),
                    $rules
                );
            }
        }
    }


}