<?php

namespace ride\web\base\controller;

use ride\library\log\Log;
use ride\library\system\System;

use \ErrorException;
use \Exception;

/**
 * Controller for the terminal application
 */
class TerminalController extends AbstractController {

    /**
     * Name of the session variable to store the current path
     * @var string
     */
    const SESSION_PATH = 'terminal.path';

    /**
     * The current path
     * @var string
     */
    private $path;

    /**
     * The path of the running system
     * @var string
     */
    private $defaultPath;

    /**
     * Initializes the path before every action
     * @return null
     */
    public function preAction() {
        $this->defaultPath = getcwd();

        if ($this->request->hasSession()) {
            $this->path = $this->request->getSession()->get(self::SESSION_PATH);
        }

        return true;
    }

    /**
     * Saves the current path in the session and restores the system's path
     * @return null
     */
    public function postAction() {
        if ($this->path) {
            $this->request->getSession()->set(self::SESSION_PATH, $this->path);
        }

        parent::postAction();
    }

    /**
     * Action to show and process the terminal form
     * @return null
     */
    public function indexAction(System $system, Log $log) {
        $form = $this->createFormBuilder();
        $form->setId('form-terminal');
        $form->addRow('command', 'string', array(
            'attributes' => array(
                'autocomplete' => 'off',
            )
        ));
        $form->setRequest($this->request);

        $form = $form->build();
        if ($form->isSubmitted()) {
            $data = $form->getData();

            $command = $data['command'];

            $output = '';
            $isError = false;

            try {
                if ($this->path) {
                    chdir($this->path);
                }

                $tokens = explode(' ', $command);
                if ($tokens[0] == 'cd') {
                    // handle a change directory command
                    try {
                        if (array_key_exists(1, $tokens) && $tokens[1]) {
                            chdir($tokens[1]);
                        } else {
                            chdir($this->defaultPath);
                        }

                        $this->path = getcwd();
                    } catch (ErrorException $exception) {
                        $output = 'Error: No such file or directory';
                        $isError = true;
                    }
                } else {
                    // any other command
                    if ($system->isUnix() && strpos($command, '2>') === false) {
                        $commandSuffix = ' 2>&1';
                    } else {
                        $commandSuffix = '';
                    }

                    $output = $system->execute($command . $commandSuffix);
                    $output = htmlentities(implode("\n", $output));
                }
            } catch (Exception $exception) {
                $log->logException($exception);

                $output = 'Error: ' . str_replace(' 2>&1', '', $exception->getMessage());
                $isError = true;
            }

            if ($this->path) {
                chdir($this->defaultPath);
            }

            $this->setJsonView(array(
                'path' => $this->path ? $this->path : $this->defaultPath,
                'command' => $command,
                'output' => $output,
                'error' => $isError,
            ));
        } else {
            $this->setTemplateView('base/terminal', array(
                'form' => $form->getView(),
                'path' => $this->path ? $this->path : $this->defaultPath
            ));
        }
    }

}
