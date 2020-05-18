<?php
/**
 * Created by PhpStorm.
 * User: linzijie
 * Date: 2019-06-06
 * Time: 16:10
 */

namespace Support;


use Illuminate\Filesystem\FilesystemManager;
use League\Flysystem\Adapter\Local as LocalAdapter;

/**
 * Class MicroCutFilesystemManager
 * @package Support
 * 这里是为了兼容 百度CFS存储暂时不支持LOCK_EX
 */
class MicroCutFilesystemManager extends FilesystemManager
{

    /**
     * Create an instance of the local driver.
     *
     * @param  array $config
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    public function createLocalDriver(array $config)
    {
        $permissions = $config['permissions'] ?? [];

        $links = ($config['links'] ?? null) === 'skip'
            ? LocalAdapter::SKIP_LINKS
            : LocalAdapter::DISALLOW_LINKS;

        return $this->adapt($this->createFlysystem(new LocalAdapter(
            $config['root'], null, $links, $permissions
        ), $config));
    }
}