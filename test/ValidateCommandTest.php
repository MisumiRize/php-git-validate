<?php

use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Application;
use Lethe\GitValidate\ValidateCommand;

class ValidateCommandTest extends \PHPUnit_Framework_TestCase
{
    private $tester;

    protected function setUp()
    {
        $application = new Application();
        $application->add(new ValidateCommand());
        $command = $application->find('validate');
        $this->tester = new CommandTester($command);
    }

    /**
     * @test
     * @expectedException \RuntimeException
     * @expectedExceptionMessage invalid json
     */
    public function malformedJson()
    {
        $this->tester->execute([
            'command' => 'validate',
            'hook' => 'pre-commit',
            '--config' => 'test/fixture/malformed.json',
        ]);
    }

    /**
     * @test
     * @expectedException \RuntimeException
     * @expectedExceptionMessage invalid json
     */
    public function rootArray()
    {
        $this->tester->execute([
            'command' => 'validate',
            'hook' => 'pre-commit',
            '--config' => 'test/fixture/root-array.json',
        ]);
    }

    /**
     * @test
     * @expectedException \RuntimeException
     * @expectedExceptionMessageRegExp /should be array\/hash\/string value/
     */
    public function hookNotArray()
    {
        $this->tester->execute([
            'command' => 'validate',
            'hook' => 'pre-commit',
            '--config' => 'test/fixture/hook-nonarray.json',
        ]);
    }

    /**
     * @test
     * @expectedException \RuntimeException
     * @expectedExceptionMessageRegExp /should be hash value/
     */
    public function scriptsNotHash()
    {
        $this->tester->execute([
            'command' => 'validate',
            'hook' => 'pre-commit',
            '--config' => 'test/fixture/scripts-nonhash.json',
        ]);
    }

    /**
     * @test
     */
    public function noScript()
    {
        $this->tester->execute([
            'command' => 'validate',
            'hook' => 'pre-commit',
            '--config' => 'test/fixture/no-script.json',
        ]);
        $this->assertRegExp('/no script found/', $this->tester->getDisplay());
    }

    /**
     * @test
     */
    public function willSuccess()
    {
        $this->tester->execute([
            'command' => 'validate',
            'hook' => 'pre-commit',
            '--config' => 'test/fixture/success.json',
        ]);
        $this->assertRegExp('/ok/', $this->tester->getDisplay());
    }

    /**
     * @test
     */
    public function willFail()
    {
        $this->tester->execute([
            'command' => 'validate',
            'hook' => 'pre-commit',
            '--config' => 'test/fixture/fail.json',
        ]);
        $this->assertRegExp('/failed!/', $this->tester->getDisplay());
    }
}
