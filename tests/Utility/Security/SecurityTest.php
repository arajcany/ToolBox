<?php

namespace arajcany\Test\Utility\Security;

use PHPUnit\Framework\TestCase;
use arajcany\ToolBox\Utility\Security\Security;
use Cake\Core\Configure;

class BoxesTest extends TestCase
{
    private $key;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        $this->key = '92874365087268653011890901274136501734697790106470100977377456371518958423414624532415651205034976541208181714859309728462003';
        Configure::write("InternalOptions.key", $this->key);

        parent::__construct($name, $data, $dataName);
    }

    public function testEncryptDecrypt()
    {
        $string = 'abc123!@#$%^&*()';
        $encrypted = Security::encrypt64($string);
        $decrypted = Security::decrypt64($encrypted);

        $this->assertEquals($string, $decrypted);
    }

    public function testEncrypt()
    {
        $string = 'abc123!@#$%^&*()';
        $encrypted = Security::encrypt64($string);

        $this->assertNotEquals($string, $encrypted);
    }

    public function testDecrypt()
    {
        $string = 'abc123!@#$%^&*()';
        $decrypted = Security::decrypt64($string);

        $this->assertNotEquals($string, $decrypted);
        $this->assertEquals('', $decrypted);
    }

    public function testEncryptDecryptUrl()
    {
        $string = 'abc123!@#$%^&*()';
        $encrypted = Security::encrypt64($string);
        $decrypted = Security::decrypt64($encrypted);

        $this->assertEquals($string, $decrypted);
    }

    public function testEncryptUrl()
    {
        $string = 'abc123!@#$%^&*()';
        $encrypted = Security::encrypt64Url($string);

        $this->assertNotEquals($string, $encrypted);
    }

    public function testDecryptUrl()
    {
        $string = 'abc123!@#$%^&*()';
        $decrypted = Security::decrypt64Url($string);

        $this->assertNotEquals($string, $decrypted);
        $this->assertEquals('', $decrypted);
    }

}