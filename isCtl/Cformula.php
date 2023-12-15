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
        if (isset($_POST['set'])) {
            $file = $_POST['available_files'];
            \isLib\LinstanceStore::set('currentFile', $file);
        } elseif (isset($_POST['edit'])) {
            // change the view
            if (\isLib\LinstanceStore::available('currentFile')) {
                $_POST['file'] = \isLib\Lconfig::CF_FILES_DIR.\isLib\LinstanceStore::get('currentFile');
                \isLib\LinstanceStore::setView('VeditFile');
            } else {
                $_POST['errmess'] = 'There is no current file';
                \isLib\LinstanceStore::setView('Verror');
                $_POST['backview'] = 'VadminFormulas';
                $_POST['propagate'] = 'backview';
            }
        } elseif (isset($_POST['new'])) {
            // change the view
            $_POST['file'] = '';
            \isLib\LinstanceStore::setView('VeditFile');
        } elseif (isset($_POST['delete'])) {
            $_POST['message'] = 'Do You really want to delete '.$_POST['delete'].'?';
            $_POST['backview'] = 'VadminFormulas';
            $_POST['propagate'] = 'backview, delete';
            \isLib\LinstanceStore::setView('Vconfirmation');
        }
    }

    public function VeditFileHandler():void {
        if (isset($_POST['store'])) {
            if ($_POST['file'] == '') {
                // A new file is created

                $oldfiles = \isLib\Lhtml::getFileArray(\isLib\Lconfig::CF_FILES_DIR);
                if (in_array($_POST['new_file'], $oldfiles)) {
                    $_POST['errmess'] = 'The file already exists. Choose another name!';
                    $_POST['backview'] = 'VeditFile';
                    // Prepare for saving the content
                    $_POST['previous_content'] = $_POST['n_ckeditor'];
                    $_POST['propagate'] = 'backview, previous_content, file';
                    \isLib\LinstanceStore::setView('Verror');
                } else {            
                    $ressource = fopen(\islib\Lconfig::CF_FILES_DIR.$_POST['new_file'], 'w');
                    fputs($ressource, $_POST['n_ckeditor']);
                    \isLib\LinstanceStore::setView('VadminFormulas');
                }
            } else {                
                $ressource = fopen($_POST['file'], 'w');
                fputs($ressource, $_POST['n_ckeditor']);
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
            $file = \isLib\Lconfig::CF_FILES_DIR.$_POST['delete'];
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