<?php

namespace Lethe\GitValidate;

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
            $dest = $gitRoot.'/.git/hooks/'.$hook;
            $source = realpath(__DIR__.'/../bin/validate');

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

        $targetPath = $gitRoot.'/'.$target;

        $this->fs->copy($source, $targetPath, $override);
    }

    public static function findGitRoot($start = null)
    {
        $start = $start ?: self::findParent();
        $root = null;
        $file = new \SplFileInfo($start.'/.git');
        if ($file->isDir()) {
            $root = $start;
        } elseif (dirname($start) !== $start) {
            $root = self::findGitRoot(dirname($start));
        }

        return $root;
    }

    private static function findParent()
    {
        $file = new \SplFileInfo(realpath(__DIR__.'/../../../../composer.json'));
        if ($file->isFile()) {
            return realpath(__DIR__.'/../../../../');
        }

        return realpath(__DIR__.'/../');
    }
}
