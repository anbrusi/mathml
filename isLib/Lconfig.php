<?php

namespace isLib;

class Lconfig {

    const CF_FILES_DIR = 'files/';

    const CF_VARS_DIR = 'vars/';
    
    public static function urlBase():string {
        if ($_SERVER['SERVER_NAME'] == 'myeclipse') {
            return 'https://myeclipse/mathml/';
        } else {
            return 'https://matml.misas.ch/';
        }
    }
}