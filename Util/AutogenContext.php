<?php

namespace DevHelper\Util;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use XF\AddOn\AddOn;
use XF\App;
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
     * @param string|array $messages
     * @param int $options
     */
    public function writeln($messages, $options = 0)
    {
        $this->output->writeln($messages, $options);
    }
}
