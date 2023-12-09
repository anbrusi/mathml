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
        if (isset($_POST['sessname'])) {
            return '<input type="hidden" name="sessname" value="'.$_POST['sessname'].'" />';
        }
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

    public static function set(string $name, mixed $value ) {
        $_SESSION['generic'][$name] = $value;
    }

    public static function get(string $name):mixed {
        return $_SESSION['generic'][$name];
    }
}