<?php

namespace isLib;

class LinstanceStore {
   
    private static string $sessname = '';

    public static function init():bool {
        if (self::$sessname == '') {
            self::$sessname = uniqid('sn');
        }
        session_name(self::$sessname);
        return session_start();
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