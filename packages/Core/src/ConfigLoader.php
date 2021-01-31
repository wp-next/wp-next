<?php

namespace WpNext\Core;

use Illuminate\Config\Repository;
use League\Flysystem\Adapter\Local as LocalAdapter;
use League\Flysystem\Filesystem;

class ConfigLoader
{
    public function loadConfig()
    {
        $config = new Repository();

        $this->filePaths()->each(function ($filePath, $key) use ($config) {
            $config->set($key, require $filePath);
        });

        return $config;
    }

    protected function filePaths()
    {
        return collect($this->allFiles())->mapWithKeys(function ($file, $key) {
            return [$this->fileName($file) => $this->realFilePath($file)];
        });
    }

    protected function allFiles()
    {
        $adapter = new LocalAdapter(app()->configPath());
        $filesystem = new Filesystem($adapter);

        return $filesystem->listContents('/');
    }

    protected function fileName($file)
    {
        return str_replace('.php', '', $file['path']);
    }

    protected function realFilePath($file)
    {
        return app()->configPath($file['path']);
    }
}
