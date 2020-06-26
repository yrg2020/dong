<?php
/**
 * restful relation controller
 *
 * PHP Version 7.2
 *
 * @author    v.k
 * @copyright 2019 ec3s.com
 */

namespace DONG2020\Controller;


use Illuminate\Http\Request;
use DONG2020\Contracts\Document;
use DONG2020\Contracts\IResourceRepository;


/**
 * restful relation controller
 */
class RestfulRelController extends RestfulController
{
    /**
     * @var IResourceRepository
     */
    private $_parentRepository;

    /**
     * @var string
     */
    private $_rel;

    /**
     * @var Document
     */
    private $_parentDocument;

    /**
     * @var mixed|null
     */
    private $_resourceId;


    /**
     * RestfulRelController constructor.
     * @param Request             $request
     * @param IResourceRepository $parentRepository
     * @param string              $rel
     * @param Document            $parentDocument
     * @param mixed               $resourceId
     */
    public function __construct(
        Request $request,
        IResourceRepository $parentRepository,
        string $rel,
        Document $parentDocument,
        $resourceId = null
    ) {
        $this->_parentRepository = $parentRepository;
        $this->_rel = $rel;
        $this->_parentDocument = $parentDocument;
        $this->_resourceId = $resourceId;
        parent::__construct($request);
    }

    /**
     * prepare
     */
    protected function prepare()
    {
        parent::prepare();
        $this->restfulRequest->resourceId = $this->_resourceId;
    }


    /**
     * @return IResourceRepository
     */
    public function getRepository()
    {
        $repo = $this->_parentRepository->getChildRepository($this->_rel, $this->_parentDocument);
        $repo->setConstraints($this->_parentRepository->getConstraints());
        return $repo;
    }

}