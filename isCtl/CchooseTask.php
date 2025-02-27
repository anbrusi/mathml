<?php

namespace isCtl;

class CchooseTask extends CcontrollerBase {

    public function viewHandler():void {
        $currentView = \isLib\LinstanceStore::getView();
        switch ($currentView) {
            case 'VadminTasks':
                $this->VadminTasksHandler();
                break;
            case 'VeditTask':
                $this->VeditTaskHandler();
                break;
            case 'Vconfirmation':
                $this->VconfirmationHandler();
                break;
            case 'Verror':
                $this->VerrorHandler();
                break;
            default:
                throw new \Exception('Unimplemented handler for: '.$currentView);
        }
    }

    public function VadminTasksHandler():void {
        // radio
        if (isset($_POST['available_tasks'])) {
            $task = $_POST['available_tasks'];
            \isLib\LinstanceStore::set('currentTask', $task);
        }
        if (isset($_POST['edit'])) {
            // The file name in VeditFile is $_POST['file], while here it is $_POST['edit'].
            $_POST['task'] = $_POST['edit'];
            // change the view
            \isLib\LinstanceStore::setView('VeditTask');
        } elseif (isset($_POST['new'])) {
            // change the view
            $_POST['task'] = '';
            \isLib\LinstanceStore::setView('VeditTask');
        } elseif (isset($_POST['delete'])) {
            $_POST['message'] = 'Do You really want to delete '.$_POST['delete'].'?';
            $_POST['backview'] = 'VadminTasks';
            $_POST['propagate'] = 'backview, delete';
            \isLib\LinstanceStore::setView('Vconfirmation');
        }
    }

    public function VerrorHandler():void {
        if (isset($_POST['back'])) {
            \isLib\LinstanceStore::setView($_POST['backview']);
        }
    }
    
    private function storeTask(string $task):void {
        // Store the problem
        $ressource = fopen(\isLib\Lconfig::CF_PROBLEMS_DIR.$task.'.html', 'w');
        fputs($ressource, $_POST['problem']);
        // Store the solution
        $ressource = fopen(\isLib\Lconfig::CF_SOLUTIONS_DIR.$task.'.html', 'w');
        fputs($ressource, $_POST['solution']);
    }

    public function VeditTaskHandler():void {
        if (isset($_POST['store'])) {
            if ($_POST['task'] == '') {
                // A new task is created

                $oldproblems = \isLib\Lhtml::getFileArray(\isLib\Lconfig::CF_PROBLEMS_DIR);
                // The names of problem files without extension are task names
                $oldtasks = [];
                foreach ($oldproblems as $filename) {
                    $oldtasks[] = substr($filename, 0, strrpos($filename, '.'));
                }
                if (in_array($_POST['new_task'], $oldtasks)) {
                    $_POST['errmess'] = 'The task already exists. Choose another name!';
                    $_POST['backview'] = 'VeditTask';
                    // Prepare for saving the content
                    $_POST['previous_problem'] = $_POST['problem'];
                    $_POST['previous_solution'] = $_POST['solution'];
                    $_POST['propagate'] = 'backview, previous_problem, previous_solution';
                    \isLib\LinstanceStore::setView('Verror');
                } else {  
                    // Make the task current                      
                     \isLib\LinstanceStore::set('currentTask', $_POST['new_task']);           
                    $this->storeTask($_POST['new_task']);
                    \isLib\LinstanceStore::setView('VadminFormulas');
                }
            } else {    
                // Make the task current                      
                \isLib\LinstanceStore::set('currentTask', $_POST['task']);      
                $this->storeTask($_POST['task']);
                \isLib\LinstanceStore::setView('VadminTasks');
            }
        } elseif (isset($_POST['esc'])) {
            \isLib\LinstanceStore::setView('VadminTasks');
        }
    }

    public function VconfirmationHandler():void {
        if (isset($_POST['yes'])) {
            // Remove the problem
            $file = \isLib\Lconfig::CF_PROBLEMS_DIR.$_POST['delete'].'.html';
            if (file_exists($file)) {
                unlink($file);
            }
            // Remove the solution
            $file = \isLib\Lconfig::CF_SOLUTIONS_DIR.$_POST['delete'].'.html';
            if (file_exists($file)) {
                unlink($file);
            }
        }
        \isLib\LinstanceStore::setView($_POST['backview']);
    }

    public static function setInitialView():void {
        \isLib\LinstanceStore::setView('VadminTasks');
    }
}