<?php

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;
use org\bovigo\vfs\vfsStreamDirectory;
use Lethe\GitValidate\Installation;

class InstallationTest extends \PHPUnit_Framework_TestCase
{
    private $fs;
    private $installation;
    private $root;
    private $url;

    protected function setUp()
    {
        $this->fs = $this->getMock(
            '\Symfony\Component\Filesystem\Filesystem',
            ['copy', 'symlink', 'exists']
        );
        $this->installation = new Installation($this->fs);

        vfsStreamWrapper::register();
        $this->root = new vfsStreamDirectory('root');
        vfsStreamWrapper::setRoot($this->root);
        $this->url = vfsStream::url($this->root->getName());
    }

    /**
     * @test
     */
    public function installHooks()
    {
        $dest = dirname(__DIR__).'/.git/hooks/pre-commit';
        $this->fs->method('exists')
            ->with($dest)
            ->will($this->returnValue(false));
        $this->fs->expects($this->once())
            ->method('symlink')
            ->with(realpath(__DIR__.'/../bin/validate'), $dest);
        $this->installation->installHooks(['pre-commit']);
    }

    /**
     * @test
     */
    public function copy()
    {
        $this->fs->expects($this->once())
            ->method('copy')
            ->with('from', dirname(__DIR__).'/to', false);
        $this->installation->copy('from', 'to');
    }

    /**
     * @test
     */
    public function findGitRoot()
    {
        vfsStream::create([
            'repo' => [
                '.git' => [],
                'vendor' => [
                    'misumirize' => [
                        'php-git-validate' => []
                     ],
                 ],
            ],
        ], $this->root);
        $url = $this->url.'/repo/vendor/misumirize/php-git-validate';
        $this->assertEquals($this->url.'/repo', Installation::findGitRoot($url));
    }
}
