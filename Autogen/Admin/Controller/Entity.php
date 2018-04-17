<?php

namespace DevHelper\Autogen\Admin\Controller;

use XF\Admin\Controller\AbstractController;
use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\Redirect;
use XF\Mvc\Reply\View;

/**
 * @version 2
 */
abstract class Entity extends AbstractController
{
    /**
     * @return View
     */
    public function actionIndex()
    {
        $page = $this->filterPage();
        $perPage = $this->getPerPage();

        $finder = $this->em()->getFinder($this->getShortName())->limitByPage($page, $perPage);
        $total = $finder->total();

        $viewParams = [
            'entities' => $finder->fetch(),

            'page' => $page,
            'perPage' => $perPage,
            'total' => $total
        ];

        return $this->getViewReply('list', $viewParams);
    }

    /**
     * @return View
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
     * @return View|Redirect
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

        /** @noinspection PhpUndefinedMethodInspection */
        $entityLabel = $entity->getLabel();

        $viewParams = [
            'entity' => $entity,
            'entityLabel' => $entityLabel
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

        $columns = $this->filter('values', 'array');
        $entity->bulkSet($columns);

        $entity->preSave();
        if ($entity->hasErrors()) {
            return $this->error($entity->getErrors());
        }

        $entity->save();

        return $this->redirect($this->buildLink($this->getRoutePrefix()));
    }

    /**
     * @param int $entityId
     * @return \XF\Mvc\Entity\Entity
     * @throws \XF\Mvc\Reply\Exception
     */
    protected function assertEntityExists($entityId)
    {
        return $this->assertRecordExists($this->getShortName(), $entityId);
    }

    /**
     * @return \XF\Mvc\Entity\Entity
     */
    protected function createEntity()
    {
        return $this->em()->create($this->getShortName());
    }

    /**
     * @param \XF\Mvc\Entity\Entity $entity
     * @return \XF\Mvc\Reply\View
     */
    protected function entityAddEdit($entity)
    {
        $viewParams = [
            'entity' => $entity,
            'columns' => []
        ];

        $structure = $entity->structure();

        foreach ($structure->columns as $columnName => $column) {
            if (empty($column['required']) ||
                empty($column['type'])) {
                continue;
            }

            /** @noinspection PhpUndefinedMethodInspection */
            $columnLabel = $entity->getColumnLabel($columnName);
            $columnTag = null;
            $columnTagOptions = [];
            switch ($column['type']) {
                case \XF\Mvc\Entity\Entity::STR:
                    if (!empty($column['maxLength']) && $column['maxLength'] <= 255) {
                        $columnTag = 'text-box';
                    } else {
                        $columnTag = 'text-area';
                    }
                    break;
            }

            $viewParams['columns'][$columnName] = [
                'label' => $columnLabel,
                'name' => sprintf('values[%s]', $columnName),
                'value' => $entity->get($columnName),
                'tag' => $columnTag,
                'tagOptions' => $columnTagOptions
            ];
        }

        foreach ($structure->relations as $relationKey => $relation) {
            if (empty($relation['entity']) ||
                empty($relation['type']) ||
                $relation['type'] !== \XF\Mvc\Entity\Entity::TO_ONE ||
                empty($relation['primary']) ||
                empty($relation['conditions']) ||
                !is_string($relation['conditions'])) {
                continue;
            }

            $columnName = $relation['conditions'];
            $relationChoices = $this->getRelationChoices($relation['entity']);

            if (count($relationChoices) > 0) {
                $viewParams['columns'][$columnName]['tag'] = 'select';
                $viewParams['columns'][$columnName]['tagOptions'] = [
                    'choices' => $relationChoices
                ];
            }
        }

        return $this->getViewReply('edit', $viewParams);
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
    protected function getRelationForums()
    {
        /** @var \XF\Repository\Node $nodeRepo */
        $nodeRepo = \XF::repository('XF:Node');
        $nodeTree = $nodeRepo->createNodeTree($nodeRepo->getFullNodeList());

        // only list nodes that are forums or contain forums
        $nodeTree = $nodeTree->filter(null, function ($id, $node, $depth, $children, $tree) {
            return ($children || $node->node_type_id == 'Forum');
        });

        $choices = [];

        foreach ($nodeTree->getFlattened(0) as $leaf) {
            /** @var \XF\Entity\Node $node */
            $node = $leaf['record'];
            $choices[] = [
                'value' => $node->node_id,
                'disabled' => $node->node_type_id !== 'Forum',
                'label' => str_repeat(html_entity_decode('&nbsp;&nbsp;'), $node->depth) . $node->title
            ];
        }

        return $choices;
    }

    /**
     * @param string $shortName
     * @return array
     */
    protected function getRelationChoices($shortName)
    {
        switch ($shortName) {
            case 'XF:Forum':
                return $this->getRelationForums();
            default:
                if (strpos($shortName, $this->getPrefixForClasses()) !== 0) {
                    return [];
                }
        }

        $choices = [];

        /** @var \XF\Mvc\Entity\Entity $entity */
        foreach ($this->em()->getFinder($shortName)->fetch() as $entity) {
            /** @noinspection PhpUndefinedMethodInspection */
            $entityLabel = $entity->getLabel();

            $choices[] = [
                'value' => $entity->getEntityId(),
                'label' => $entityLabel
            ];
        }

        return $choices;
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
     * @param \Symfony\Component\Console\Command\Command $command
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param \XF\AddOn\AddOn $addOn
     * @throws \XF\PrintableException
     */
    public function devHelperAutogen($command, $input, $output, $addOn)
    {
        /** @var \Symfony\Component\Console\Helper\QuestionHelper $helper */
        $helper = $command->getHelper('question');

        foreach ([
                     '_add',
                     '_edit',
                     '_entities',
                     '_entity',
                 ] as $phraseTitlePartial) {
            $phraseTitle = $this->getPrefixForPhrases() . $phraseTitlePartial;

            /** @var \XF\Entity\Phrase $phrase */
            $phrase = $this->finder('XF:Phrase')
                ->where('language_id', 0)
                ->where('addon_id', $addOn->getAddOnId())
                ->where('title', $phraseTitle)
                ->fetchOne();
            if ($phrase) {
                $output->writeln(
                    "<info>Phrase #{$phrase->phrase_id} {$phrase->title} = {$phrase->phrase_text} OK</info>",
                    \Symfony\Component\Console\Output\OutputInterface::VERBOSITY_VERY_VERBOSE
                );
                continue;
            }

            $questionText = sprintf('<question>Enter phrase text for %s:</question> ', $phraseTitle);
            $question = new \Symfony\Component\Console\Question\Question($questionText);
            $question->setValidator(function ($answer) {
                $answer = utf8_trim($answer);
                if (empty($answer)) {
                    throw new \InvalidArgumentException('Phrase text cannot be empty');
                }
                return $answer;
            });
            $phraseText = $helper->ask($input, $output, $question);

            /** @var \XF\Entity\Phrase $newPhrase */
            $newPhrase = $this->em()->create('XF:Phrase');
            $newPhrase->addon_id = $addOn->getAddOnId();
            $newPhrase->language_id = 0;
            $newPhrase->title = $phraseTitle;
            $newPhrase->phrase_text = $phraseText;
            $newPhrase->save();

            $output->writeln("<info>Phrase #{$newPhrase->phrase_id} {$newPhrase->title} = {$newPhrase->phrase_text} NEW</info>");
        }

        $prefixForTemplates = $this->getPrefixForTemplates();
        foreach ([
                     'delete',
                     'edit',
                     'list',
                 ] as $action) {
            $templateTitleSource = "devhelper_autogen_ace_{$action}";
            $templateTitleTarget = "{$prefixForTemplates}_entity_{$action}";

            /** @var \XF\Entity\Template $templateSource */
            $templateSource = $this->finder('XF:Template')
                ->where('type', 'admin')
                ->where('style_id', 0)
                ->where('addon_id', 'DevHelper')
                ->where('title', $templateTitleSource)
                ->fetchOne();
            if (!$templateSource) {
                throw new \LogicException("Source template {$templateTitleSource} not found");
            }

            /** @var \XF\Entity\Template $templateTarget */
            $templateTarget = $this->finder('XF:Template')
                ->where('type', 'admin')
                ->where('style_id', 0)
                ->where('addon_id', $addOn->getAddOnId())
                ->where('title', $templateTitleTarget)
                ->fetchOne();

            if ($templateTarget) {
                if ($templateTarget->template === $templateSource->template) {
                    $output->writeln(
                        "<info>Template #{$templateTarget->template_id} {$templateTarget->title} OK</info>",
                        \Symfony\Component\Console\Output\OutputInterface::VERBOSITY_VERY_VERBOSE
                    );
                } else {
                    $templateTarget->template = $templateSource->template;
                    $templateTarget->save();

                    $output->writeln("<info>Template #{$templateTarget->template_id} {$templateTarget->title} UPDATED</info>");
                }
            } else {
                /** @var \XF\Entity\Template $newTemplate */
                $newTemplate = $this->em()->create('XF:Template');
                $newTemplate->type = 'admin';
                $newTemplate->title = $templateTitleTarget;
                $newTemplate->style_id = 0;
                $newTemplate->template = $templateSource->template;
                $newTemplate->addon_id = $addOn->getAddOnId();
                $newTemplate->save();

                $output->writeln("<info>Template #{$newTemplate->template_id} {$newTemplate->title} NEW</info>");
            }
        }

        $output->writeln(__CLASS__);
    }

    // DevHelper/Autogen ends
}
