<?php

namespace Lethe\GitValidate;

use Phine\Path\Path;
use Symfony\Component\Filesystem\Filesystem;

class Installation
{
    private $fs;

    public function __construct(FileSystem $fs = null)
    {
        $this->fs = ($fs === null) ? new FileSystem() : $fs;
    }

    public function installHooks($hooks)
    {
        $gitRoot = self::findGitRoot($start);

        foreach ($hooks as $hook) {
            $dest = Path::join([$gitRoot, '.git', 'hooks', $hook]);
            $source = Path::join([dirname(__DIR__), 'bin', 'validate']);

            if ($this->fs->exists($dest)) {
                if ((new \SplFileInfo($dest))->isLink()) {
                    $this->fs->remove($dest.'.bak');
                    $this->fs->rename($dest, $dest.'.bak');
                    $this->fs->symlink($source, $dest, true);
                }
            } else {
                $this->fs->symlink($source, $dest, true);
            }
        }
    }

    public function copy($source, $target = null, $override = false)
    {
        if ($target === null) {
            $target = basename($source);
        }

        $gitRoot = self::findGitRoot();

        $targetPath = Path::join([$gitRoot, $target]);

        $this->fs->copy($source, $targetPath, $override);
    }

    public static function findGitRoot($start = null)
    {
        $start = $start ?: self::findParent();
        $root = null;
        $file = new \SplFileInfo(Path::join([$start, '.git']));
        if ($file->isDir()) {
            $root = $start;
        } elseif (dirname($start) !== $start) {
            $root = self::findGitRoot(dirname($start));
        }

        return $root;
    }

    private static function findParent()
    {
        $file = new \SplFileInfo(Path::canonical(Path::join([__DIR__, '..', '..', '..', '..', 'composer.json'])));
        if ($file->isFile()) {
            return Path::canonical(Path::join([__DIR__, '..', '..', '..', '..']));
        }

        return dirname(__DIR__);
    }
}
