<?php

/**
 * resource repository interface
 *
 * PHP Version 7.2
 *
 * @author    v.k
 * @copyright 2019 ec3s.com
 */

namespace CXD2020\Contracts;


use Symfony\Component\HttpFoundation\ParameterBag;


/**
 * resource repository interface
 */
interface IResourceRepository
{

    public function getConstraints(): array;

    /**
     * @param array $constraints
     * @return void
     */
    public function setConstraints(array $constraints);

    /**
     * find collection
     *
     * @param CollectionFindOption $findOption
     * @param array                $withRels
     * @param ParameterBag|null    $relsQuery
     *
     * @return Collection
     */
    public function findCollection(
        CollectionFindOption $findOption,
        array $withRels = [],
        ?ParameterBag $relsQuery = null
    );

    /**
     * find document
     *
     * @param mixed $resourceId
     * @param array $withRels
     *
     * @return Document|null
     */
    public function find($resourceId, array $withRels = []);

    /**
     * create document
     *
     * @param array $attributes
     * @param array $withRels
     *
     * @return Document created document
     */
    public function create(array $attributes, array $withRels = []);

    /**
     * batch create document
     *
     * @param array $attributesSet
     * @param array $withRels
     *
     * @return Collection created documents in collection
     */
    public function batchCreate(array $attributesSet, array $withRels = []);

    /**
     * replace document
     *
     * @param \CXD2020\Contracts\Document $document
     * @param mixed                     $key
     * @param array                     $attributes
     * @param array                     $withRels
     *
     * @return Document replaced document
     */
    public function replace(?Document $document, $key, array $attributes, array $withRels = []);

    /**
     * batch replace document
     *
     * @param \CXD2020\Contracts\CollectionFindOption $findOption
     * @param array                                 $attributes
     * @param array                                 $withRels
     *
     * @return Collection replaced documents in collection
     */
    public function batchReplace(CollectionFindOption $findOption, array $attributes, array $withRels = []);

    /**
     * update document
     *
     * @param Document $document
     * @param array    $attributes data for update
     * @param array    $withRels
     *
     * @return Document|null
     */
    public function update(Document $document, array $attributes, array $withRels = []);

    /**
     * batch update document
     *
     * @param CollectionFindOption $findOption
     * @param array                $attributes
     * @param array                $withRels
     *
     * @return Collection
     */
    public function batchUpdate(CollectionFindOption $findOption, array $attributes, array $withRels = []);

    /**
     * destroy document
     *
     * @param mixed $resourceId
     * @return bool
     */
    public function destroy($resourceId);

    /**
     * batch destroy documents
     *
     * @param CollectionFindOption $findOption
     *
     * @return bool
     */
    public function batchDestroy(CollectionFindOption $findOption);

    /**
     * get resource relationships
     *
     * @return array
     */
    public function getRelationships();

    /**
     * @param string   $rel relation name
     * @param Document $relatedDocument
     *
     * @return IResourceRepository|null
     */
    public function getChildRepository(string $rel, Document $relatedDocument);

}