<?php
/**
 * model repository
 *
 * PHP Version 7.2
 *
 * @author    v.k
 * @copyright 2019 ec3s.com
 */

namespace DONG2020\Repository\Eloquent;

use Illuminate\Container\Container;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Schema;

use Illuminate\Database\Eloquent\{
    Model,
    Collection as ModelCollection,
    Relations\BelongsTo,
    Relations\HasOneOrMany,
    Relations\HasMany,
    Relations\HasOne
};

use Symfony\Component\HttpFoundation\ParameterBag;
use DONG2020\Contracts\{
    DocumentLinkMeta,
    IResourceRepository,
    ICollectionFindOptionParser,
    Collection,
    CollectionMeta,
    CollectionFindOption,
    CollectionPaginationMeta,
    Document,
    DocumentMeta
};


/**
 * model repository
 */
class ModelRepository implements IResourceRepository
{

    /**
     * @var string
     */
    public $modelClass;

    /**
     * @var Container
     */
    protected $container;

    /** @var Model
     */
    private $_model;

    /**
     * @var string
     */
    protected $href;

    /**
     * @var ICollectionFindOptionParser
     */
    protected $parser;

    /**
     * @var string
     */
    protected $idKey;

    /**
     * @var bool
     */
    protected $isChild;

    /**
     * @var array
     */
    protected $modelsPropsMap = [];

    /**
     * @var array
     */
    protected $constraints = [];

    /**
     * @param mixed                       $modelClass
     * @param string                      $href
     * @param ICollectionFindOptionParser $parser
     * @param null                        $idKey
     * @param bool                        $childRepo
     *
     * @pram  array
     */
    public function __construct(
        $modelClass,
        string $href,
        ICollectionFindOptionParser $parser = null,
        $idKey = null,
        $childRepo = false
    ) {
        $this->modelClass = $modelClass;
        $this->href = $href;
        $this->parser = $parser ?: new CollectionFindOptionParser();
        $this->idKey = $idKey ? $idKey : \config('DONG2020.idKey', 'id');
        $this->isChild = $childRepo;
    }

    /**
     * @param $container
     */
    public function setContainer($container)
    {
        $this->container = $container;
    }

    /**
     * @return array
     */
    public function getConstraints(): array
    {
        return $this->constraints;
    }

    /**
     * @param array $constraints
     */
    public function setConstraints(array $constraints)
    {
        $this->constraints = $constraints;
    }

    /**
     * find collection
     *
     * @param CollectionFindOption $findOption
     * @param array                $withRels
     *
     * @param null|ParameterBag    $relsQuery
     *
     * @return Collection
     * @throws \ReflectionException
     */
    public function findCollection(
        CollectionFindOption $findOption,
        array $withRels = [],
        ?ParameterBag $relsQuery = null
    ) {
        $this->applyConstraints($findOption);
        $model = $this->model();
        $query = $this->queryWithRels($withRels, $relsQuery);

        $this->parser->parse($findOption, [$model, $query]);

        if ($findOption->all) {
            $items = $query->get();
            $meta = new CollectionMeta(
                $this->href,
                $items->count(),
                new CollectionPaginationMeta($items->count(), 1, 1, false)
            );
            return $this->createCollection($items->toArray(), $meta);
        }

        $paginate = $query->paginate(
            $findOption->page->limit, ['*'], null, $findOption->page->page
        );

        $meta = new CollectionMeta(
            $this->href,
            $paginate->total(),
            new CollectionPaginationMeta(
                $findOption->page->limit,
                $findOption->page->page,
                $paginate->lastPage(),
                $findOption->page->page < $paginate->lastPage()
            ),
            $this->_getLinks($model),
            $model instanceof Model ? $model->getRelationships() : []
        );

        return $this->createCollection($paginate->items(), $meta);
    }

    /**
     * find document
     *
     * @param mixed $resourceId
     * @param array $withRels
     *
     * @return Document|null
     * @throws \ReflectionException
     */
    public function find($resourceId, array $withRels = [])
    {
        if ($this->isChild) {
            $model = $this->modelClass->first();
        } else {
            /** @var Model|ModelRelationTrait $model */
            $model = $this->queryWithRels($withRels)->whereKey($resourceId)
                ->where($this->constraints)
                ->first();
        }

        if (!$model) {
            return null;
        }

        $links = $this->_getLinks($model, $resourceId);
        $meta = new DocumentMeta($model->getKey(), $this->_etag($model), $this->href, $links);
        return new Document($model, $meta);
    }

    /**
     * get resource links
     *
     * @param \DONG2020\Repository\Eloquent\ModelRelationTrait $model
     * @param string                                        $resourceId
     * @return array
     */
    private function _getLinks($model, $resourceId = ''): array
    {
        $links = [];
        if ($model instanceof Model) {
            $resourceId = $resourceId ? $resourceId : sprintf('{%s}', $this->idKey);
            foreach ($model->getRelationships() as $name) {
                $links[] = new DocumentLinkMeta($name, sprintf('%s/%s/%s', $this->href, $resourceId, $name));
            }
        }
        return $links;
    }


    /**
     * set attributes to model
     *
     * @param Model|ModelRelationTrait|HasTempAttributeTrait $model
     * @param array                                          $attributes
     * @param array                                          $withRels
     * @param bool                                           $isReplace
     *
     * @return Model|ModelRelationTrait
     */
    private function _touchModelAttributes($model, array $attributes, $withRels = [], $isReplace = false)
    {
        $isModelExists = $model->exists;
        $attributes = array_merge($attributes, $this->constraints);
        $relationAttributes = [];
        foreach ($attributes as $key => $value) {
            if (is_array($value) && $model->hasRelationship($key)) {
                if (!isset($relationAttributes[$key])) {
                    $relationAttributes[$key] = [];
                }
                $relationAttributes[$key][] = $value;
            } else {
                $this->setModelAttribute($model, $key, $value);
            }
        }

        foreach ($relationAttributes as $relName => $relValues) {
            $rel = $model->{$relName}();
            foreach ($relValues as $values) {
                if ($rel instanceof HasMany) {
                    $relModels = new \Illuminate\Database\Eloquent\Collection();
                    foreach ($values as $attributes) {
                        if (!is_array($attributes)) {
                            throw new \InvalidArgumentException(
                                sprintf('relation attribute <%s> should be array', $relName), 101
                            );
                        }
                        $relModel = $this->_touchRelationModelAttributes($rel, $attributes);
                        $relModels->add($relModel);
                    }
                    if ($isReplace) {
                        $idKeys = $relModels->map(function ($m) {
                            return $m[$this->idKey];
                        })->filter(function ($id) {
                            return $id > 0;
                        });
                        if (!empty($idKeys)) {
                            $rel->newQuery()->whereNotIn($this->idKey, $idKeys)->delete();
                        }
                    }
                    $model->setRelation($relName, $relModels);
                    continue;
                }
                if ($rel instanceof HasOne) {
                    $relModel = $this->_touchRelationModelAttributes($rel, $values);
                    $model->setRelation($relName, $relModel);
                    continue;
                }
                throw new \InvalidArgumentException(
                    sprintf('can not process relation "%s<%s>"', $relName, get_class($rel)), 101
                );
            }
        }

        $model->save();

        $relationUpdated = false;
        foreach ($model->getRelations() as $key => $relation) {
            if (!$relation) {
                continue;
            }
            /** @var \Illuminate\Database\Eloquent\Relations\Relation $rel */
            $rel = $model->{$key}();
            $relProps = $rel->make()->getAttributes();
            if ($relation instanceof \Illuminate\Database\Eloquent\Collection) {
                $idKeys = [];
                foreach ($relation as $relModel) {
                    foreach ($relProps as $k => $v) {
                        $this->setModelAttribute($relModel, $k, $v);
                    }
                    $relModel->save();
                    $relationUpdated = true;
                    $idKeys[] = $relModel->getKey();
                }
                if ($isReplace && !empty($idKeys)) {
                    $rel->newQuery()->whereNotIn($this->idKey, $idKeys)->delete();
                }
            } else {
                foreach ($relProps as $k => $v) {
                    /** @type $relation Model */
                    $this->setModelAttribute($relation, $k, $v);
                }
                $relation->save();
                $relationUpdated = true;
                if ($isReplace && $relation->getKey()) {
                    $rel->newQuery()->where($this->idKey, '!=', $relation->getKey())->delete();
                }
            }
        }

        if ($relationUpdated) {
            $model->fireRelationEvent();
        }

        $validRels = [];
        foreach ($withRels as $rel) {
            if ($model->hasRelationship($rel)) {
                $validRels[] = $rel;
            }
        }
        if (count($validRels) > 0) {
            $model->load($validRels);
        }

        if ($isModelExists) {
            return $model;
        }

        $relations = $model->getRelations();
        $model->refresh();
        return $model->setRelations($relations);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Relations\HasOneOrMany $rel
     * @param array                                                $attributes
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    private function _touchRelationModelAttributes($rel, array $attributes)
    {
        $relQuery = $rel->newModelInstance()->newQuery();
        $relModel = empty($attributes[$this->idKey]) ? $relQuery->make() : $relQuery->find($attributes[$this->idKey]);
        !$relModel && $relModel = $relQuery->make();

        foreach ($attributes as $key => $value) {
            $this->setModelAttribute($relModel, $key, $value);
        }

        return $relModel;
    }

    /**
     * @param Model  $model
     * @param string $key
     *
     * @return bool
     */
    private function _attributeCanSet($model, $key)
    {
        return $key != $model::CREATED_AT && $key != $model::UPDATED_AT;
    }

    /**
     * @param Model|\DONG2020\Repository\Eloquent\HasTempAttributeTrait $model
     * @param string                                                 $key
     * @param mixed                                                  $value
     *
     * @return Model
     */
    protected function setModelAttribute($model, $key, $value)
    {
        static $modelClassCache = [];
        $modelClass = get_class($model);
        if (!isset($modelClassCache[$modelClass])) {
            $modelClassCache[$modelClass] = method_exists($model, 'hasTempAttribute');
        }

        if ($modelClassCache[$modelClass] && $model->hasTempAttribute($key)) {
            return $model->setTempAttribute($key, $value);
        }

        if ($this->_attributeCanSet($model, $key) && $this->hasAttribute($model, $key)) {
            return $model->setAttribute($key, $value);
        }

        return $model;
    }

    /**
     * @return \DONG2020\Contracts\Collection
     */
    private function _createEmptyCollection()
    {
        $meta = new CollectionMeta(
            $this->href,
            0,
            new CollectionPaginationMeta(0, 1, 1, false),
            $this->_getLinks($this->createModel()),
            $this->getRelationships()
        );
        return $this->createCollection([], $meta);
    }

    /**
     * @param array $attributes
     * @return \DONG2020\Contracts\Collection
     */
    protected function updateByAttrs(array $attributes)
    {
        $models = [];
        foreach ($attributes as $attr) {
            if (!isset($attr[$this->idKey]) || !is_array($attr)) {
                continue;
            }
            $model = $this->newQuery()->find($attr[$this->idKey]);
            if (!$model) {
                continue;
            }
            foreach ($attr as $key => $value) {
                $model->setAttribute($key, $value);
            }
            $model->save();
            $models[] = $model;
        }
        if (empty($models)) {
            return $this->_createEmptyCollection();
        }
        $meta = $meta = new CollectionMeta(
            $this->href,
            count($models),
            new CollectionPaginationMeta(0, 1, 1, false),
            $this->_getLinks($this->createModel()),
            $this->getRelationships()
        );
        return $this->createCollection($models, $meta);
    }

    /**
     * @param Model|\DONG2020\Repository\Eloquent\ModelRelationTrait $model
     * @param array                                               $attributes
     * @return \Illuminate\Database\Eloquent\Model
     * @throws \Exception
     */
    private function _replaceModel($model, $attributes)
    {
        foreach (array_merge($model->getAttributes(), $attributes) as $key => $value) {
            if (!$model->hasRelationship($key)) {
                $this->setModelAttribute($model, $key, $value);
            }
        }
        return $model;
    }

    /**
     * create document
     *
     * @param array $attributes
     * @param array $withRels
     *
     * @return \DONG2020\Contracts\Document
     */
    public function create(array $attributes, array $withRels = [])
    {
        $model = $this->createModel();
        return $this->createDocument($this->_touchModelAttributes($model, $attributes, $withRels));
    }

    /**
     * batch create document
     *
     * @param array $attributesSet
     * @param array $withRels
     *
     * @return Collection created documents in collection
     */
    public function batchCreate(array $attributesSet, array $withRels = [])
    {
        $models = [];
        foreach ($attributesSet as $attributes) {
            $models[] = $this->_touchModelAttributes($this->createModel(), $attributes, $withRels);
        }
        return $this->createCollection($models, null);
    }

    /**
     * replace document
     *
     * @param \DONG2020\Contracts\Document $document
     * @param mixed                     $key
     * @param array                     $attributes
     * @param array                     $withRels
     *
     * @return Document replaced document
     * @throws \Exception
     */
    public function replace(?Document $document, $key, array $attributes, array $withRels = [])
    {
        /** @var Model $model */
        $model = null;
        if (!$document || !$document->document) {
            $model = $this->createModel();
        } else {
            $model = $this->_replaceModel($document->document, $attributes);
        }
        if ($this->isChild) {
            $attributes = array_merge($attributes, $this->constraints);
            foreach ($attributes as $key => $value) {
                $this->setModelAttribute($model, $key, $value);
            }
            $model->save();
        } else {
            $model->setAttribute($model->getKeyName(), $key);
            $model = $this->_touchModelAttributes($model, $attributes, $withRels, true);
        }
        return $this->createDocument($model);
    }

    /**
     * batch replace document
     *
     * @param \DONG2020\Contracts\CollectionFindOption $findOption
     * @param array                                 $attributes
     * @param array                                 $withRels
     *
     * @return Collection replaced documents in collection
     * @throws \Exception
     */
    public function batchReplace(CollectionFindOption $findOption, array $attributes, array $withRels = [])
    {
        if ($findOption->query->count() == 0 && empty($this->constraints)) {
            $meta = new CollectionMeta(
                $this->href,
                0,
                new CollectionPaginationMeta(0, 1, 1, false),
                $this->_getLinks($this->createModel()),
                $this->getRelationships()
            );
            return $this->createCollection([], $meta);
        }

        $this->applyConstraints($findOption);
        $this->batchDestroy($findOption);

        $newCollection = [];

        /** @var Model $model */
        foreach ($attributes as $attrs) {
            $newCollection[] = $this->_touchModelAttributes($this->createModel(), $attrs);
        }

        $meta = new CollectionMeta(
            $this->href,
            0,
            new CollectionPaginationMeta(0, 1, 1, false),
            $this->_getLinks($this->createModel()),
            $this->getRelationships()
        );

        return $this->createCollection($newCollection, $meta);
    }


    /**
     * update document
     *
     * @param Document $document
     * @param array    $attributes data for update
     * @param array    $withRels
     *
     * @return Document|null
     */
    public function update(Document $document, array $attributes, array $withRels = [])
    {
        /** @var Model $model */
        $model = $document->document instanceof Model ? $document->document : null;
        if (!$model) {
            return null;
        }
        return $this->createDocument($this->_touchModelAttributes($model, $attributes, $withRels, false));
    }

    /**
     * batch update document
     *
     * @param CollectionFindOption $findOption
     * @param array                $attributes
     * @param array                $withRels
     *
     * @return Collection updated documents in collection
     * @throws \ReflectionException
     */
    public function batchUpdate(CollectionFindOption $findOption, array $attributes, array $withRels = [])
    {
        $hasKey = !empty($attributes) && isset($attributes[0][$this->idKey]);
        if ($findOption->query->count() == 0 && !$hasKey) {
            $meta = new CollectionMeta(
                $this->href,
                0,
                new CollectionPaginationMeta(0, 1, 1, false),
                $this->_getLinks($this->createModel()),
                $this->getRelationships()
            );
            return $this->createCollection([], $meta);
        }

        if ($hasKey) {
            $collection = $this->updateByAttrs($attributes);
        } else {
            $collection = $this->findCollection($findOption);
            /** @var Model $model */
            foreach ($collection->collection as $model) {
                $this->_touchModelAttributes($model, $attributes, $withRels, false);
            }
        }

        return $collection;
    }

    /**
     * destroy document
     *
     * @param mixed $resourceId
     *
     * @return bool
     */
    public function destroy($resourceId)
    {
        $model = $this->newQuery()
            ->whereKey($resourceId)
            ->where($this->constraints)
            ->first();

        if (!$model) {
            return false;
        }
        return $model->delete();
    }

    /**
     * batch destroy documents
     *
     * @param CollectionFindOption $findOption
     *
     * @return bool
     * @throws \Exception
     */
    public function batchDestroy(CollectionFindOption $findOption)
    {
        if ($findOption->query->count() == 0) {
            throw new \InvalidArgumentException("batch destroy all collection was not supported");
        }

        $model = $this->model();
        $query = $this->newQuery($model);

        $this->parser->parse($findOption, [$model, $query]);

        $deleted = false;
        foreach ($query->get() as $model) {
            $model->delete();
            $deleted = true;
        }

        return $deleted;
    }

    /**
     * @return array
     */
    public function getRelationships()
    {
        return $this->model()->getRelationships();
    }

    /**
     * @param string   $rel
     * @param Document $relatedDocument
     *
     * @return IResourceRepository|null
     */
    public function getChildRepository(string $rel, Document $relatedDocument)
    {
        $relationship = $this->model()->getRelationship($rel);
        if (!$relationship) {
            return null;
        }
        $relatedModel = $relatedDocument->document instanceof Model ? $relatedDocument->document : null;
        if (!$relatedModel) {
            return null;
        }
        return new ModelRepository($relatedModel->{$rel}(), $rel, $this->parser, $this->idKey, true);
    }

    /**
     * @return Model|ModelRelationTrait
     */
    protected function model()
    {
        if (!$this->_model) {
            if (is_string($this->modelClass)) {
                if (!$this->container) {
                    $this->container = app();
                }
                $this->_model = $this->container->make($this->modelClass);
            } else {
                $this->_model = $this->modelClass;
            }
        }
        return $this->_model;
    }

    /**
     * @param Model $model
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function newQuery(Model $model = null)
    {
        return ($model ?: $this->model())->newQuery();
    }

    /**
     * @param array             $rels
     * @param null|ParameterBag $relsQuery
     *
     * @return \Illuminate\Database\Eloquent\Builder
     * @throws \ReflectionException
     */
    protected function queryWithRels(array $rels, ?ParameterBag $relsQuery = null)
    {
        $model = $this->model();
        $query = $model->newQuery();
        if ($relsQuery) {
            $rels = array_unique(array_merge($rels, $relsQuery->keys()));
        }

        foreach ($rels as $rel) {
            if (!$model->hasRelationship($rel)) {
                continue;
            }
            $query->with($rel);
            if ($relsQuery && $relsQuery->has($rel)) {
                $params = new ParameterBag($relsQuery->get($rel, []));
                if ($params->count() > 0) {
                    $findOption = new CollectionFindOption($params);
                    $relationship = $model->getRelationship($rel);
                    if ($relationship instanceof HasOneOrMany) {
                        $localKeyProp = new \ReflectionProperty($relationship, 'localKey');
                        $localKeyProp->setAccessible(true);
                        $localKey = $localKeyProp->getValue($relationship);
                        $query->whereIn(
                            $localKey ? $localKey : $model->getKeyName(),
                            function (Builder $query) use ($findOption, $relationship) {
                                $query->from($relationship->getModel()->getTable())->select($relationship->getForeignKeyName());
                                $this->parser->parse($findOption, [$relationship->getModel(), $query]);
                                return $query;
                            }
                        );
                    } elseif ($relationship instanceof BelongsTo) {
                        $query->whereIn(
                            $relationship->getForeignKeyName(),
                            function (Builder $query) use ($findOption, $relationship) {
                                $query->from($relationship->getModel()->getTable())->select($relationship->getOwnerKeyName());
                                $this->parser->parse($findOption, [$relationship->getModel(), $query]);
                                return $query;
                            }
                        );
                    }
                }
            }
        }
        return $query;
    }

    /**
     * @param Model|ModelRelationTrait $model
     *
     * @return Document
     */
    protected function createDocument(Model $model)
    {
        $meta = new DocumentMeta(
            $model->getKey(),
            $this->_etag($model),
            $this->href,
            $model->getRelationships()
        );
        return new Document($model, $meta);
    }

    /**
     * @param array          $models
     * @param CollectionMeta $collectionMeta
     *
     * @return Collection
     */
    protected function createCollection(array $models, ?CollectionMeta $collectionMeta)
    {
        return new Collection($models, $collectionMeta);
    }

    /**
     * @param Model|ModelRelationTrait $model
     * @param string                   $attribute
     *
     * @return bool
     */
    protected function hasAttribute($model, $attribute)
    {
        $modelClass = get_class($model);
        if (!isset($this->modelsPropsMap[$modelClass])) {
            $this->modelsPropsMap[$modelClass] = Schema::getColumnListing($model->getTable());
        }
        return in_array(
            strtolower($attribute), array_map('strtolower', $this->modelsPropsMap[$modelClass])
        );
    }

    /**
     * @return Model|ModelRelationTrait|\Illuminate\Database\Eloquent\Relations\HasOneOrMany
     */
    protected function createModel()
    {
        $model = $this->model();
        if ($model instanceof Model) {
            return $model->newInstance();
        }
        return $model->make();
    }

    /**
     * @param \DONG2020\Contracts\CollectionFindOption $findOption
     *
     * @return void
     */
    protected function applyConstraints(CollectionFindOption $findOption)
    {
        foreach ($this->constraints as $key => $value) {
            $findOption->query->set($key, $value);
        }
    }

    /**
     * @param Model $model
     *
     * @return string
     */
    private function _etag(Model $model)
    {
        $updatedAt = $model->getAttribute($model->getUpdatedAtColumn());
        return md5($updatedAt);
    }


}