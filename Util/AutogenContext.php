<?php

namespace DevHelper\Util;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use XF\AddOn\AddOn;
use XF\App;
use XF\Mvc\Controller;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Finder;

class AutogenContext
{
    /**
     * @var Command
     */
    protected $command;

    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var App
     */
    protected $app;

    /**
     * @var AddOn
     */
    protected $addOn;

    /**
     * @var string[]
     */
    public $gitignoreAdds = [
        '/DevHelper/autogen.json',
        '/_build/',
        '/_data/',
        '/_releases/',
        '/vendor/',
    ];

    /**
     * @var string[]
     */
    public $gitignoreDeletes = [
        '/_output/',
        '/DevHelper/*',
        '!/DevHelper/autogen.json',
    ];

    /**
     * @param Command $command
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param App $app
     * @param AddOn $addOn
     */
    public function __construct($command, $input, $output, $app, $addOn)
    {
        $this->command = $command;
        $this->input = $input;
        $this->output = $output;
        $this->app = $app;
        $this->addOn = $addOn;
    }

    /**
     * @param Question $question
     * @return mixed
     */
    public function ask($question)
    {
        /** @var QuestionHelper $helper */
        $helper = $this->command->getHelper('question');
        return $helper->ask($this->input, $this->output, $question);
    }

    /**
     * @param string $shortName
     * @return Entity
     */
    public function createEntity($shortName)
    {
        return $this->app->em()->create($shortName);
    }

    /**
     * @param mixed $obj
     * @return bool
     * @see \DevHelper\Autogen\Admin\Controller\Entity::devHelperAutogen()
     */
    public function executeDevHelperAutogen($obj)
    {
        $f = [$obj, 'devHelperAutogen'];
        if (!is_callable($f)) {
            return false;
        }

        call_user_func($f, $this);
        return true;
    }

    /**
     * @param string $identifier
     * @return Finder
     */
    public function finder($identifier)
    {
        return $this->app->finder($identifier);
    }

    /**
     * @return string
     */
    public function getAddOnId()
    {
        return $this->addOn->getAddOnId();
    }

    /**
     * @return string
     */
    public function getAddOnDirectory()
    {
        return $this->addOn->getAddOnDirectory();
    }

    /**
     * @param string $class
     * @return Controller
     */
    public function newController($class)
    {
        return new $class($this->app, $this->app->request());
    }

    /**
     * @param string|array $messages
     * @param int $options
     * @return void
     */
    public function writeln($messages, $options = 0)
    {
        $this->output->writeln($messages, $options);
    }
}
