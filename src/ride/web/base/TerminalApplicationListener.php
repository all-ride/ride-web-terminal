<?php

namespace ride\web\base;

use ride\library\event\Event;

use ride\web\base\view\MenuItem;

class TerminalApplicationListener {

    /**
     * Adds a menu item for the terminal
     * @param ride\library\event\Event $event
     * @return null
     */
    public function handleTaskbar(Event $event) {
        $menuItem = new MenuItem();
        $menuItem->setTranslation('title.terminal');
        $menuItem->setRoute('system.terminal');

        $taskbar = $event->getArgument('taskbar');
        $settingsMenu = $taskbar->getSettingsMenu();

        $systemMenu = $settingsMenu->getItem('title.system');
        $systemMenu->addMenuItem($menuItem);
    }

}
