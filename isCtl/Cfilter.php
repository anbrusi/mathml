<?php

namespace isCtl;

class Cfilter extends CcontrollerBase {

    public function viewHandler():void {
        $currentView = \isLib\LinstanceStore::getView();
        switch ($currentView) {
            case 'Vfilter':
                $this->VfilterHandler();
                break;
            default:
                throw new \Exception('Unimplemented handler for: '.$currentView);
        }
    }

    public function VfilterHandler():void {
        // Check that a task has been selected
        $task = \isLib\LinstanceStore::get('currentTask');
        if ($task) {
            $ressource = fopen(\isLib\Lconfig::CF_PROBLEMS_DIR.$task.'.html', 'r');
            $_POST['problemcontent'] = fgets($ressource);
            $ressource = fopen(\isLib\Lconfig::CF_SOLUTIONS_DIR.$task.'.html', 'r');
            $solutioncontent = fgets($ressource);
            $_POST['solutioncontent'] = $solutioncontent;
            $Lfilter = new \isLib\Lfilter($solutioncontent);
            $_POST['filteredsolution'] = $Lfilter->asciiContent();
        } else {
            $_POST['errmess'] = 'No current task set';
            \isLib\LinstanceStore::setView('Verror');
        }
    }

    public static function setInitialView():void {
        \isLib\LinstanceStore::setView('Vfilter');
    }
}