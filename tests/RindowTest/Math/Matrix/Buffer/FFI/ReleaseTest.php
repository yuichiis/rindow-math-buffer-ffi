<?php
namespace RindowTest\Math\Buffer\FFI\ReleaseTest;

use PHPUnit\Framework\TestCase;
use Rindow\Math\Buffer\FFI\BufferFactory;
use Rindow\Math\Buffer\FFI\Buffer;
use Interop\Polite\Math\Matrix\NDArray;
use FFI;

class ReleaseTest extends TestCase
{
    public function testFFINotLoaded()
    {
        $factory = new BufferFactory();
        if(extension_loaded('ffi')) {
            $buffer = $factory->Buffer(1,NDArray::float32);
            $this->assertInstanceof(Buffer::class,$buffer);
        } else {
            $this->assertFalse($factory->isAvailable());
        }
    }
}