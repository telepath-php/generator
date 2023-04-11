<?php

if (! function_exists('psr_build_path')) {
    function psr_build_path(string $className)
    {
        $buildPath = base_path('build/');

        return str($className)
            ->replace('Telepath\\', '')
            ->replace('\\', '/')
            ->prepend($buildPath)
            ->append('.php')
            ->toString();
    }
}
