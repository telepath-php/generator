<?php

namespace App\Updater;

use Humbug\SelfUpdate\Updater;
use LaravelZero\Framework\Components\Updater\Strategy\StrategyInterface;

class UpdateStrategy implements StrategyInterface
{
    private string $packageName;

    private string $localVersion;

    private string $remoteVersion;

    public function setPackageName($name)
    {
        $this->packageName = $name;
    }

    public function setCurrentLocalVersion($version)
    {
        $this->localVersion = $version;
    }

    public function download(Updater $updater)
    {
        $url = "https://git.tii.tools/tii/telepathy/-/raw/{$this->remoteVersion}/builds/telepathy";

        file_put_contents($updater->getTempPharFile(), file_get_contents($url));
    }

    public function getCurrentRemoteVersion(Updater $updater)
    {
        $url = 'https://git.tii.tools/tii/telepathy/-/releases.json';
        $json = json_decode(file_get_contents($url));

        $this->remoteVersion = $json[0]->tag;

        return $this->remoteVersion;
    }

    public function getCurrentLocalVersion(Updater $updater)
    {
        return $this->localVersion;
    }

}
