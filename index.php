<?php

class mathml {


    /**
     * Set by the POST variable 'ctl'' if available. 
     * Default is 'Cavailable' a list of all stored formulas
     * 
     * @var string
     */
    private string $controller = '';

    function __construct() {
        if (isset($_POST['ctl'])) {
            $this->controller = $_POST['ctl'];
        } else {
            $this->controller = 'Cformula';
        }
    }

    /**
     * Renders the application
     * 
     * @return void 
     */
    public function dispatch():void {
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
        $html .= \isLib\Lmenu::dropdownBar('navbar', \isLib\Lmenu::mainMenu);
        $className = '\isCtl\\'.$this->controller;
        $controller = new $className();
        $html .= $controller->render();
        $html .= '</form>';
        $html .= '</body>';
        return $html;
    }
}

require 'vendor/autoload.php';

$mathml = new mathml();
$mathml->dispatch();
