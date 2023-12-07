<?php

class mathml {

    function __construct() {
        if (!\isLib\LinstanceStore::init()) {
            throw new \Exception('Could not initialize LinstanceStore');
        }
        // Set the initial controller
        \isLib\LinstanceStore::setController('Cformula');
        // Set the initial view
        \isLib\LinstanceStore::setView('VeditFormula');
    }

    /**
     * Renders the application
     * 
     * @return void 
     */
    public function dispatch():void {
        // Change the controller if required
        if (isset($_POST['ctl']) && $_POST['ctl'] != \isLib\LinstanceStore::getController()) {
            \isLib\LinstanceStore::setController($_POST['ctl']);
            $className = '\isCtl\\'.$_POST['ctl'];
            $className::setInitialView();
        }
        echo $this->renderPage();
    }

    /**
     * Returns the application HTML page
     * 
     * @return string 
     */
    private function renderPage():string {
        $html = '';
        $html .= '<!DOCTYPE html>';
        $html .= '<html lang="en">';
        $html .= $this->header();
        $html .= $this->body();
        $html .= '</html>';
        return $html;
    }

    /**
     * Header of HTML page
     * 
     * @return string 
     */
    private function header():string {
        $html = '';
        $html .= '<head>';
        $html .= '<meta charset="UTF-8">';
        $html .= '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        $html .= '<link rel="stylesheet" href="index.css" />';
        $html .= '<title>MathML</title>';
        return $html;
    }

    /**
     * body of HTML page
     * 
     * @return string 
     */
    private function body():string {
        $html = '';
        $html .= '<body>';
        $html .= '<h1>MathML test environment</h1>';
        $html .= '<form action="index.php" method="POST" enctype="" name="mainform">';
        $html .= \isLib\Lnavigation::dropdownBar('navbar', \isLib\Lnavigation::mainMenu);
        $controller = \isLib\LinstanceStore::getController();
        $className = '\isCtl\\'.$controller;
        $controllerObj = new $className($controller);
        $html .= $controllerObj->render();
        $html .= '</form>';
        $html .= '</body>';
        return $html;
    }
}

require 'vendor/autoload.php';

$mathml = new mathml();
$mathml->dispatch();
