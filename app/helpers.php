<?php

if (! function_exists('psr_build_path')) {
    function psr_build_path(string $className)
    {
        $buildPath = Str::finish(config('generator.build_path'), '/');

        return str($className)
            ->replace('Telepath\\', '')
            ->replace('\\', '/')
            ->prepend($buildPath)
            ->append('.php')
            ->toString();
    }
}
