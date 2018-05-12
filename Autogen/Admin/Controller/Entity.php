<?php

namespace DevHelper\Autogen\Admin\Controller;

use XF\Admin\Controller\AbstractController;
use XF\Mvc\Entity\Entity as MvcEntity;
use XF\Mvc\FormAction;
use XF\Mvc\ParameterBag;

/**
 * @version 2018051301
 * @see \DevHelper\Autogen\Admin\Controller\Entity
 */
abstract class Entity extends AbstractController
{
    /**
     * @return \XF\Mvc\Reply\View
     */
    public function actionIndex()
    {
        $page = $this->filterPage();
        $perPage = $this->getPerPage();

        list($finder, $filters) = $this->entityListData();

        $finder->limitByPage($page, $perPage);
        $total = $finder->total();

        $viewParams = [
            'entities' => $finder->fetch(),

            'filters' => $filters,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total
        ];

        return $this->getViewReply('list', $viewParams);
    }

    /**
     * @return \XF\Mvc\Reply\View
     */
    public function actionAdd()
    {
        if (!$this->supportsAdding()) {
            return $this->noPermission();
        }

        return $this->entityAddEdit($this->createEntity());
    }

    /**
     * @param ParameterBag $params
     * @return \XF\Mvc\Reply\View|\XF\Mvc\Reply\Redirect
     * @throws \XF\Mvc\Reply\Exception
     * @throws \XF\PrintableException
     */
    public function actionDelete(ParameterBag $params)
    {
        if (!$this->supportsDeleting()) {
            return $this->noPermission();
        }

        $entityId = $this->getEntityIdFromParams($params);
        $entity = $this->assertEntityExists($entityId);

        if ($this->isPost()) {
            $entity->delete();

            return $this->redirect($this->buildLink($this->getRoutePrefix()));
        }

        $viewParams = [
            'entity' => $entity,
            'entityLabel' => $this->getEntityLabel($entity)
        ];

        return $this->getViewReply('delete', $viewParams);
    }

    /**
     * @param ParameterBag $params
     * @return \XF\Mvc\Reply\View
     * @throws \XF\Mvc\Reply\Exception
     */
    public function actionEdit(ParameterBag $params)
    {
        if (!$this->supportsEditing()) {
            return $this->noPermission();
        }

        $entityId = $this->getEntityIdFromParams($params);
        $entity = $this->assertEntityExists($entityId);
        return $this->entityAddEdit($entity);
    }

    /**
     * @return \XF\Mvc\Reply\Error|\XF\Mvc\Reply\Redirect
     * @throws \Exception
     * @throws \XF\Mvc\Reply\Exception
     * @throws \XF\PrintableException
     */
    public function actionSave()
    {
        $this->assertPostOnly();

        $entityId = $this->filter('entity_id', 'uint');
        if (!empty($entityId)) {
            $entity = $this->assertEntityExists($entityId);
        } else {
            $entity = $this->createEntity();
        }

        $this->entitySaveProcess($entity)->run();

        return $this->redirect($this->buildLink($this->getRoutePrefix()));
    }

    /**
     * @param MvcEntity $entity
     * @param string $columnName
     * @return string|null
     */
    public function getEntityColumnLabel($entity, $columnName)
    {
        $callback = [$entity, 'getEntityColumnLabel'];
        if (!is_callable($callback)) {
            $shortName = $entity->structure()->shortName;
            throw new \InvalidArgumentException("Entity {$shortName} does not implement {$callback[1]}");
        }

        return call_user_func($callback, $columnName);
    }

    /**
     * @param MvcEntity $entity
     * @return string
     */
    public function getEntityExplain($entity)
    {
        return '';
    }

    /**
     * @param MvcEntity $entity
     * @return string
     */
    public function getEntityHint($entity)
    {
        $structure = $entity->structure();
        if (!empty($structure->columns['display_order'])) {
            return sprintf('%s: %d', \XF::phrase('display_order'), $entity->get('display_order'));
        }

        return '';
    }

    /**
     * @param MvcEntity $entity
     * @return string|null
     */
    public function getEntityLabel($entity)
    {
        $callback = [$entity, 'getEntityLabel'];
        if (!is_callable($callback)) {
            $shortName = $entity->structure()->shortName;
            throw new \InvalidArgumentException("Entity {$shortName} does not implement {$callback[1]}");
        }

        return call_user_func($callback);
    }

    /**
     * @param int $entityId
     * @return MvcEntity
     * @throws \XF\Mvc\Reply\Exception
     */
    protected function assertEntityExists($entityId)
    {
        return $this->assertRecordExists($this->getShortName(), $entityId);
    }

    /**
     * @return MvcEntity
     */
    protected function createEntity()
    {
        return $this->em()->create($this->getShortName());
    }

    /**
     * @param MvcEntity $entity
     * @return \XF\Mvc\Reply\View
     */
    protected function entityAddEdit($entity)
    {
        $viewParams = [
            'entity' => $entity,
            'columns' => [],
        ];

        $structure = $entity->structure();
        $viewParams['columns'] = $this->entityGetMetadataForColumns($entity);

        foreach ($structure->relations as $relationKey => $relation) {
            if (empty($relation['entity']) ||
                empty($relation['type']) ||
                $relation['type'] !== MvcEntity::TO_ONE ||
                empty($relation['primary']) ||
                empty($relation['conditions'])) {
                continue;
            }

            $columnName = null;
            $relationConditions = $relation['conditions'];
            if (is_string($relationConditions)) {
                $columnName = $relationConditions;
            } elseif (is_array($relationConditions)) {
                if (count($relationConditions) === 1) {
                    $relationCondition = reset($relationConditions);
                    if (count($relationCondition) === 3 &&
                        $relationCondition[1] === '=' &&
                        preg_match('/\$(.+)$/', $relationCondition[2], $matches)) {
                        $columnName = $matches[1];
                    }
                }
            }
            if (empty($columnName) || !isset($viewParams['columns'][$columnName])) {
                continue;
            }
            $columnViewParamRef = &$viewParams['columns'][$columnName];
            list ($relationTag, $relationTagOptions) = $this->entityAddEditRelationColumn(
                $entity,
                $columnViewParamRef['_structureData'],
                $relationKey,
                $relation
            );

            if ($relationTag !== null) {
                $columnViewParamRef['tag'] = $relationTag;
                $columnViewParamRef['tagOptions'] = $relationTagOptions;
            }
        }

        return $this->getViewReply('edit', $viewParams);
    }

    /**
     * @param MvcEntity $entity
     * @param array $column
     * @param string $relationKey
     * @param array $relation
     * @return array
     */
    protected function entityAddEditRelationColumn($entity, array $column, $relationKey, array $relation)
    {
        $tag = null;
        $tagOptions = [];
        switch ($relation['entity']) {
            case 'XF:Forum':
                $tag = 'select';
                /** @var \XF\Repository\Node $nodeRepo */
                $nodeRepo = $entity->repository('XF:Node');
                $tagOptions['choices'] = $nodeRepo->getNodeOptionsData(false, ['Forum']);
                break;
            case 'XF:User':
                $tag = 'username';
                /** @var \XF\Entity\User $user */
                $user = $entity->getRelation($relationKey);
                $tagOptions['username'] = $user ? $user->username : '';
                break;
            default:
                if (strpos($relation['entity'], $this->getPrefixForClasses()) === 0) {
                    $choices = [];

                    /** @var MvcEntity $entity */
                    foreach ($this->finder($relation['entity'])->fetch() as $entity) {
                        $choices[] = [
                            'value' => $entity->getEntityId(),
                            'label' => $this->getEntityLabel($entity)
                        ];
                    }

                    $tag = 'select';
                    $tagOptions['choices'] = $choices;
                }
        }

        if ($tag === 'select') {
            if (isset($tagOptions['choices']) && empty($column['required'])) {
                array_unshift($tagOptions['choices'], [
                    'value' => 0,
                    'label' => '',
                ]);
            }
        }

        return [$tag, $tagOptions];
    }

    /**
     * @param \XF\Mvc\Entity\Entity $entity
     * @param string $columnName
     * @param array $column
     * @return array
     */
    protected function entityGetMetadataForColumn($entity, $columnName, array $column)
    {
        $columnTag = null;
        $columnTagOptions = [];
        $columnFilter = null;
        $requiresLabel = true;

        if (!$entity->exists()) {
            if (!empty($column['default'])) {
                $entity->set($columnName, $column['default']);
            }

            if ($this->request->exists($columnName)) {
                $input = $this->filter(['filters' => [$columnName => 'str']]);
                if (!empty($input['filters'][$columnName])) {
                    $entity->set($columnName, $this->filter($columnName, $input['filters'][$columnName]));
                    $requiresLabel = false;
                }
            }
        } else {
            if (!empty($column['writeOnce'])) {
                // do not render row for write once column, new value won't be accepted anyway
                return null;
            }
        }

        $columnLabel = $this->getEntityColumnLabel($entity, $columnName);
        if ($requiresLabel && empty($columnLabel)) {
            return null;
        }

        switch ($column['type']) {
            case MvcEntity::INT:
                $columnTag = 'number-box';
                $columnFilter = 'int';
                break;
            case MvcEntity::UINT:
                $columnTag = 'number-box';
                $columnTagOptions['min'] = 0;
                $columnFilter = 'uint';
                break;
            case MvcEntity::STR:
                if (!empty($column['allowedValues'])) {
                    $choices = [];
                    foreach ($column['allowedValues'] as $allowedValue) {
                        $label = $allowedValue;
                        if (is_object($columnLabel) && $columnLabel instanceof \XF\Phrase) {
                            $labelPhraseName = $columnLabel->getName() . '_' .
                                preg_replace('/[^a-z]+/i', '_', $allowedValue);
                            $label = \XF::phraseDeferred($labelPhraseName);
                        }

                        $choices[] = [
                            'value' => $allowedValue,
                            'label' => $label
                        ];
                    }

                    $columnTag = 'select';
                    $columnTagOptions = ['choices' => $choices];
                } elseif (!empty($column['maxLength']) && $column['maxLength'] <= 255) {
                    $columnTag = 'text-box';
                } else {
                    $columnTag = 'text-area';
                }
                $columnFilter = 'str';
                break;
        }

        if ($columnTag === null || $columnFilter === null) {
            return null;
        }

        return [
            'filter' => $columnFilter,
            'label' => $columnLabel,
            'tag' => $columnTag,
            'tagOptions' => $columnTagOptions,
        ];
    }

    /**
     * @param \XF\Mvc\Entity\Entity $entity
     * @return array
     */
    protected function entityGetMetadataForColumns($entity)
    {
        $columns = [];
        $structure = $entity->structure();

        foreach ($structure->columns as $columnName => $column) {
            if (empty($column['type']) ||
                $columnName === $structure->primaryKey) {
                continue;
            }

            $metadata = $this->entityGetMetadataForColumn($entity, $columnName, $column);
            if (!is_array($metadata)) {
                continue;
            }

            $columns[$columnName] = $metadata;
            $columns[$columnName] += [
                '_structureData' => $column,
                'name' => sprintf('values[%s]', $columnName),
                'value' => $entity->get($columnName),
            ];
        }

        return $columns;
    }

    /**
     * @return array
     */
    protected function entityListData()
    {
        $shortName = $this->getShortName();
        $finder = $this->finder($shortName);

        $structure = $this->em()->getEntityStructure($shortName);
        if (!empty($structure->columns['display_order'])) {
            $finder->order('display_order');
        }

        $filters = ['pageNavParams' => []];

        return [$finder, $filters];
    }

    /**
     * @param \XF\Mvc\Entity\Entity $entity
     * @return FormAction
     */
    protected function entitySaveProcess($entity)
    {
        $filters = [];
        $columns = $this->entityGetMetadataForColumns($entity);
        foreach ($columns as $columnName => $metadata) {
            $filters[$columnName] = $metadata['filter'];
        }

        $form = $this->formAction();
        $input = $this->filter(['values' => $filters]);
        $form->basicEntitySave($entity, $input['values']);

        $form->setup(function (FormAction $form) use ($entity) {
            $input = $this->filter([
                'hidden_columns' => 'array-str',
                'hidden_values' => 'array-str',
            ]);

            foreach ($input['hidden_columns'] as $columnName) {
                if (empty($input['hidden_values'][$columnName])) {
                    continue;
                }
                $entity->set($columnName, $input['hidden_values'][$columnName]);
            }
        });

        $form->setup(function (FormAction $form) use ($entity) {
            $input = $this->filter([
                'username_columns' => 'array-str',
                'username_values' => 'array-str',
            ]);

            foreach ($input['username_columns'] as $columnName) {
                $userId = 0;
                if (!empty($input['username_values'][$columnName])) {
                    /** @var \XF\Repository\User $userRepo */
                    $userRepo = $this->repository('XF:User');
                    $user = $userRepo->getUserByNameOrEmail($input['username_values'][$columnName]);
                    if (empty($user)) {
                        $form->logError(\XF::phrase('requested_user_not_found'));
                    } else {
                        $userId = $user->user_id;
                    }
                }

                $entity->set($columnName, $userId);
            }
        });

        return $form;
    }

    /**
     * @param ParameterBag $params
     * @return int
     */
    protected function getEntityIdFromParams(ParameterBag $params)
    {
        $structure = $this->em()->getEntityStructure($this->getShortName());
        if (is_string($structure->primaryKey)) {
            return $params->get($structure->primaryKey);
        }

        return 0;
    }

    /**
     * @return int
     */
    protected function getPerPage()
    {
        return 20;
    }

    /**
     * @return array
     */
    protected function getViewLinks()
    {
        $routePrefix = $this->getRoutePrefix();
        $links = [
            'index' => $routePrefix,
            'save' => sprintf('%s/save', $routePrefix)
        ];

        if ($this->supportsAdding()) {
            $links['add'] = sprintf('%s/add', $routePrefix);
        }

        if ($this->supportsDeleting()) {
            $links['delete'] = sprintf('%s/delete', $routePrefix);
        }

        if ($this->supportsEditing()) {
            $links['edit'] = sprintf('%s/edit', $routePrefix);
        }

        if ($this->supportsViewing()) {
            $links['view'] = sprintf('%s/view', $routePrefix);
        }

        return $links;
    }

    /**
     * @return array
     */
    protected function getViewPhrases()
    {
        $prefix = $this->getPrefixForPhrases();

        $phrases = [];
        foreach ([
                     'add',
                     'edit',
                     'entities',
                     'entity',
                 ] as $partial) {
            $phrases[$partial] = \XF::phrase(sprintf('%s_%s', $prefix, $partial));
        }

        return $phrases;
    }

    /**
     * @param string $action
     * @param array $viewParams
     * @return \XF\Mvc\Reply\View
     */
    protected function getViewReply($action, array $viewParams)
    {
        $viewClass = sprintf('%s:Entity\%s', $this->getPrefixForClasses(), ucwords($action));
        $templateTitle = sprintf('%s_entity_%s', $this->getPrefixForTemplates(), strtolower($action));

        $viewParams['controller'] = $this;
        $viewParams['links'] = $this->getViewLinks();
        $viewParams['phrases'] = $this->getViewPhrases();

        return $this->view($viewClass, $templateTitle, $viewParams);
    }

    /**
     * @return bool
     */
    protected function supportsAdding()
    {
        return true;
    }

    /**
     * @return bool
     */
    protected function supportsDeleting()
    {
        return true;
    }

    /**
     * @return bool
     */
    protected function supportsEditing()
    {
        return true;
    }

    /**
     * @return bool
     */
    protected function supportsViewing()
    {
        return false;
    }

    /**
     * @return string
     */
    abstract protected function getShortName();

    /**
     * @return string
     */
    abstract protected function getPrefixForClasses();

    /**
     * @return string
     */
    abstract protected function getPrefixForPhrases();

    /**
     * @return string
     */
    abstract protected function getPrefixForTemplates();

    /**
     * @return string
     */
    abstract protected function getRoutePrefix();

    // DevHelper/Autogen begins

    /**
     * @param \DevHelper\Util\AutogenContext $context
     * @throws \XF\PrintableException
     */
    public function devHelperAutogen($context)
    {
        $entity = $this->createEntity();
        try {
            $this->getEntityLabel($entity);
        } catch (\InvalidArgumentException $e) {
            $context->writeln("<error>{$e->getMessage()}</error>");
        }
        try {
            $this->getEntityColumnLabel($entity, __METHOD__);
        } catch (\InvalidArgumentException $e) {
            $context->writeln("<error>{$e->getMessage()}</error>");
        }

        $structure = $entity->structure();
        \DevHelper\Util\Autogen\AdminRoute::autogen(
            $context,
            $this->getRoutePrefix(),
            $structure->primaryKey,
            str_replace('\Admin\Controller\\', ':', get_class($this))
        );

        $phraseTitlePartials = ['_entities', '_entity'];
        if ($this->supportsAdding()) {
            $phraseTitlePartial[] = '_add';
        }
        if ($this->supportsEditing()) {
            $phraseTitlePartial[] = '_edit';
        }
        foreach ($phraseTitlePartials as $phraseTitlePartial) {
            \DevHelper\Util\Autogen\Phrase::autogen($context, $this->getPrefixForPhrases() . $phraseTitlePartial);
        }

        $prefixForTemplates = $this->getPrefixForTemplates();
        $templateTitlePartials = ['list'];
        if ($this->supportsDeleting()) {
            $templateTitlePartials[] = 'delete';
        }
        if ($this->supportsEditing()) {
            $templateTitlePartials[] = 'edit';
        }
        foreach ($templateTitlePartials as $templateTitlePartial) {
            $templateTitleSource = "devhelper_autogen_ace_{$templateTitlePartial}";
            $templateTitleTarget = "{$prefixForTemplates}_entity_{$templateTitlePartial}";
            \DevHelper\Util\Autogen\AdminTemplate::autogen($context, $templateTitleSource, $templateTitleTarget);
        }

        $context->writeln(get_class($this));
    }

    // DevHelper/Autogen ends
}
