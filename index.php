<?php
namespace anbrusi;

class mathml {

    /**
     * Set by the POST variable ^page' if available. 
     * Default is 'available' a list of all stored formulas
     * 
     * @var string
     */
    private string $page = '';

    private string $fileName = '';

    function __construct() {
        if (isset($_GET['page'])) {
            $this->page = $_GET['page'];
        } else {
            $this->page = 'available';
        }
    }

    /**
     * Renders the application
     * 
     * @return void 
     */
    public function dispatch():void {
        echo $this->renderPage($this->page);
    }

    /**
     * Returns the application HTML page
     * 
     * @return string 
     */
    private function renderPage(string $page):string {
        $html = '';
        $html .= '<!DOCTYPE html>';
        $html .= '<html lang="en">';
        $html .= $this->header();
        $html .= $this->body($page);
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
    private function body(string $page):string {
        $html = '';
        $html .= '<body>';
        $html .= '<h1>MathML test environment</h1>';
        $html .= '<form action="index.php" method="POST" enctype="" name="mainform">';
        $html .= $this->mainMenu();
        switch ($this->page) {
            case 'available':
                $html .= $this->renderAvailable();
                break;
            default:
                $html .= '<h2>No rendering code for page "'.$this->page.'"</h2>';
        }
        $html .= '</form>';
        $html .= '</body>';
        return $html;
    }

    /**
     * Returns a link to page $page. 
     * 
     * @param string $page 
     * @return string 
     */
    private function pageAnchor(string $page) {
        $anchor = '<a class="mainMenu" href="';
		if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] != '')) {
			$prefix = 'https://';
		} else {
			$prefix = 'http://';
		}
        $anchor .= $prefix.$_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME'].'?page='.$page;
        $anchor .= '">';
        return $anchor;
    }

    private function mainMenu():string {
        $html = '';
        $html .= '<p>';
        $html .= '<ul class="mainMenu">';
        $html .= '<li class="mainMenu">'.$this->pageAnchor('available').'Available Formulas</a></li>';
        $html .= '<li class="mainMenu">'.$this->pageAnchor('newFormula').'New Formula</a></li>';
        $html .= '<li class="mainMenu">'.$this->pageAnchor('showMathML').'Show MathML</a></li>';
        $html .= '</ul>';
        $html .= '</p>';
        return $html;
    }

    /**
     * Renders page 'available'
     * 
     * @return string 
     */
    private function renderAvailable():string {
        $html = '';
        $html .= '<h2>available</h2>';
        // $formulaDir = \anbrusi\isLib\Lconfig::CF_FORMULA_DIR;
        return $html;
    }
}
$mathml = new mathml();
$mathml->dispatch();
