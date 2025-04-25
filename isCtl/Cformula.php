<?php

namespace isCtl;

class Cformula extends CcontrollerBase {

    /**
     * 
     * @param string $name The name of the controller
     * @return void 
     */
    /*
    function __construct(string $name) {
        parent::__construct($name);        
    }
    */

    public function viewHandler():void {
        $currentView = \isLib\LinstanceStore::getView();
        switch ($currentView) {
            case 'VadminFormulas':
                $this->VadminFormulasHandler();
                break;
            case 'VeditFile':
                $this->VeditFileHandler();
                break;
            case 'Verror':
                $this->VerrorHandler();
                break;
            case 'Vconfirmation';
                $this->VconfirmationHandler();
                break;
            default:
                throw new \Exception('Unimplemented handler for: '.$currentView);
        }
    }

    public function VadminFormulasHandler():void {
        // radio
        if (isset($_POST['available_files'])) {
            $file = $_POST['available_files'];
            \isLib\LinstanceStore::set('currentFile', $file);
        }
        // icon
        if (isset($_POST['editFile'])) {
            // change the view
            \isLib\LinstanceStore::setView('VeditFile');
        // button
        } elseif (isset($_POST['new'])) {
            // change the view
            $_POST['editFfile'] = '';
            \isLib\LinstanceStore::setView('VeditFile');
        // icon
        } elseif (isset($_POST['delete'])) {
            $_POST['message'] = 'Do You really want to delete '.$_POST['delete'].'?';
            $_POST['backview'] = 'VadminFormulas';
            $_POST['propagate'] = 'backview, delete';
            \isLib\LinstanceStore::setView('Vconfirmation');
        }
    }

    /**
     * Stores file and variables
     * 
     * @param string $name document root name of the file
     * @return void 
     */
    private function storeFile(string $name):void {
        // Store the formula
        $ressource = fopen(\isLib\Lconfig::CF_FILES_DIR.$name, 'w');
        fputs($ressource, $_POST['n_ckeditor']);
        // Store the variables
        $vars = [];
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'var_') === 0) {
                $varname = substr($key, 4);
                $vars[$varname] = $value;
            }
        }
        $json = json_encode($vars);
        $ressource = fopen(\isLib\Lconfig::CF_VARS_DIR.$name, 'w');
        fputs($ressource, $json);
    }

    public function VeditFileHandler():void {
        if (isset($_POST['store'])) {
            if ($_POST['file'] == '') {
                // A new file is created

                $oldfiles = \isLib\Lhtml::getFileArray(\isLib\Lconfig::CF_FILES_DIR);
                if (trim($_POST['new_file']) == '') {
                    $_POST['errmess'] = 'The file name is empty!';
                    $_POST['backview'] = 'VeditFile';
                    // Prepare for saving the content
                    $_POST['previous_content'] = $_POST['n_ckeditor'];
                    $_POST['propagate'] = 'backview, previous_content, file';
                    \isLib\LinstanceStore::setView('Verror');
                } elseif (in_array($_POST['new_file'], $oldfiles)) {
                    $_POST['errmess'] = 'The file already exists. Choose another name!';
                    $_POST['backview'] = 'VeditFile';
                    // Prepare for saving the content
                    $_POST['previous_content'] = $_POST['n_ckeditor'];
                    $_POST['propagate'] = 'backview, previous_content, file';
                    \isLib\LinstanceStore::setView('Verror');
                } else {  
                    // Make the file current                      
                     \isLib\LinstanceStore::set('currentFile', $_POST['new_file']);           
                    $this->storeFile($_POST['new_file']);
                    \isLib\LinstanceStore::setView('VadminFormulas');
                }
            } else {    
                // Make the file current                      
                 \isLib\LinstanceStore::set('currentFile', $_POST['file']);      
                $this->storeFile($_POST['file']);
                \isLib\LinstanceStore::setView('VadminFormulas');
            }
        } elseif (isset($_POST['esc'])) {
            \isLib\LinstanceStore::setView('VadminFormulas');
        }
    }

    public function VerrorHandler():void {
        if (isset($_POST['back']) && isset($_POST['backview'])) {
            \isLib\LinstanceStore::setView($_POST['backview']);
        }
    }

    public function VconfirmationHandler():void {
        if (isset($_POST['yes'])) {
            // Remove the file itself
            $file = \isLib\Lconfig::CF_FILES_DIR.$_POST['delete'];
            if (file_exists($file)) {
                unlink($file);
            }
            // Remove variables originating from this file
            $file = \isLib\Lconfig::CF_VARS_DIR.$_POST['delete'];
            if (file_exists($file)) {
                unlink($file);
            }
        }
        \isLib\LinstanceStore::setView($_POST['backview']);
    }

    public static function setInitialView():void {
        \isLib\LinstanceStore::setView('VadminFormulas');
    }

}