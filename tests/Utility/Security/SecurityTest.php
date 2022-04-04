<?php

namespace Security;

use PHPUnit\Framework\TestCase;
use arajcany\ToolBox\Utility\Security\Security;
use Cake\Core\Configure;

class SecurityTest extends TestCase
{
    private string $key;
    private string $salt;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        $this->key = sha1(Security::randomBytes(2048));
        Configure::write("InternalOptions.key", $this->key);

        $this->salt = sha1(Security::randomBytes(2048));
        Configure::write("InternalOptions.salt", $this->salt);

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