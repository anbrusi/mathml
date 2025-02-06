<?php

namespace isLib;

class LinstanceStore {
   
    public static function init():bool {
        if (!isset($_POST['sessname'])) {
            $_POST['sessname'] = 'sn'.uniqid();
        }
        session_name($_POST['sessname']);
        return session_start();
    }

    public static function propagation():string {
        return \isLib\Lhtml::propagatePost('sessname');
    }

    public static function controllerAvailable():bool {
        return isset($_SESSION['navigation']['ctl']);
    }

    public static function setController(string $controller):void {
        $_SESSION['navigation']['ctl'] = $controller;
    }

    public static function getController():string {
        if (self::controllerAvailable()) {
            return $_SESSION['navigation']['ctl'];
        } else {
            return '';
        }
    }

    public static function viewAvailable():bool {
        return isset($_SESSION['navigation']['view']);
    }

    public static function setView(string $view):void {
        $_SESSION['navigation']['view'] = $view;
    }

    /**
     * Returns a view, if available, an empty string if not.
     * @return string 
     */
    public static function getView():string {
        if (self::viewAvailable()) {
            return $_SESSION['navigation']['view'];
        } else {
            return '';
        }
    }

    public static function available(string $name):bool {
        return isset($_SESSION['generic'][$name]);
    }

    public static function set(string $name, mixed $value):void {
        $_SESSION['generic'][$name] = $value;
    }

    public static function get(string $name):mixed {
        return $_SESSION['generic'][$name];
    }

    public static function NCvarianbleAvailable(string $name):bool {
        return isset($_SESSION['ncvars'][$name]);
    }

    public static function setNCvariable(string $name, mixed $value):void {
        $_SESSION['ncvars'][$name] = $value;
    }

    public static function getNCvariable(string $name):mixed {
        return $_SESSION['ncvars'][$name];
    }

    /**
     * Returns an array of nanoCAS variables or an empty array, if there are none.
     * 
     * @return array 
     */
    public static function listNCvariables():array {
        if (isset($_SESSION['ncvars'])) {
            return $_SESSION['ncvars'];
        } else {
            return [];
        }
    }
}