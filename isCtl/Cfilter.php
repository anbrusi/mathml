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
        try {
            $task = \isLib\LinstanceStore::get('currentTask');
            if ($task) {
                $ressource = fopen(\isLib\Lconfig::CF_PROBLEMS_DIR.$task.'.html', 'r');
                $_POST['problemcontent'] = fgets($ressource);
                $ressource = fopen(\isLib\Lconfig::CF_SOLUTIONS_DIR.$task.'.html', 'r');
                $solutioncontent = fgets($ressource);
                $LmathTransformations = new \isLib\LmathTransformations($_POST['problemcontent'], $solutioncontent);
                $_POST['solutioncontent'] = $LmathTransformations->getAnnotatedSolution();
                $Lfilter = new \isLib\Lfilter($solutioncontent);
                $Lfilter->extractMathContent();
                $asciiContent = $Lfilter->getMathContent();
                $html = '';
                $html .= '<pre>';
                $html .= '<ul>';
                foreach ($asciiContent as $expression) {
                    $html .= '<li>'.$expression['position']."\t".$expression['length']."\t".$expression['origin']."\t".$expression['ascii'].'</li>';
                }
                $html .= '</ul>';
                $html .= '</pre>';
                $_POST['asciicontent'] = $html;
            } else {
                $_POST['errmess'] = 'No current task set';
                \isLib\LinstanceStore::setView('Verror');
            }
        } catch (\isLib\isMathException $ex) {
            $_POST['ex'] = $ex;
            \isLib\LinstanceStore::setView('VmathError');
        }
    }

    public static function setInitialView():void {
        \isLib\LinstanceStore::setView('Vfilter');
    }
}