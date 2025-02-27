<?php

namespace isCtl;


class CnumericAnswer extends Ccontrollerbase {

    public function viewHandler():void {
        $currentView = \isLib\LinstanceStore::getView();
        switch ($currentView) {
            case 'VnumericAnswer':
                $this->VnumericAnswerHandler();
                break;
            default:
                throw new \Exception('Unimplemented handler for: '.$currentView);
        }
    }

    public function VnumericAnswerHandler():void {

    }

    public static function setInitialView():void {
        \isLib\LinstanceStore::setView('VnumericAnswer');
    }
}