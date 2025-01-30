<?php

namespace isLib;

class Lfilter {

    private string $editorContent = '';

    function __construct(string $editorContent) {
        $this->editorContent = $editorContent;
    }

    public function asciiContent():string {
        return $this->editorContent;
    }
}