<?php
namespace RindowTest\Math\Buffer\FFI\BufferTest;

use PHPUnit\Framework\TestCase;
use Interop\Polite\Math\Matrix\NDArray;
use Rindow\Math\Buffer\FFI\Buffer;
use Rindow\Math\Buffer\FFI\BufferMacOS;
use Rindow\Math\Buffer\FFI\BufferFactory;
use ArgumentCountError;
use LogicException;
use RuntimeException;
use OutOfRangeException;
use TypeError;
use FFI;

class BufferTest extends TestCase
{
    protected object $factory; 
    public function setUp() : void
    {
        $this->factory = new BufferFactory();
    }

    protected function notSupportComplex() : bool
    {
        //return PHP_OS==='Darwin';
        return false;
    }

    //public function testExtensionVersion()
    //{
    //    $this->assertEquals('0.1.7',phpversion('rindow_openblas'));
    //}

    public function testNormal()
    {
        $buf = $this->factory->Buffer(3,NDArray::float32);
        $buf[0] = 0.5;
        $buf[1] = 1.5;
        $buf[2] = 2.5;
        $this->assertEquals(3,count($buf));
        $this->assertEquals(NDArray::float32,$buf->dtype());
        $this->assertTrue(is_float($buf[0]));
        $this->assertEquals(0.5,$buf[0]);
        $this->assertEquals(1.5,$buf[1]);
        $this->assertEquals(2.5,$buf[2]);
    }

    public function testDtypesAndOffsetOfDtypes()
    {
        $buf = $this->factory->Buffer(3,NDArray::bool);
        $buf[2] = true;
        $this->assertEquals(NDArray::bool,$buf->dtype());
        $this->assertTrue(is_bool($buf[0]));
        $this->assertEquals(true,$buf[2]);

        $buf = $this->factory->Buffer(3,NDArray::int8);
        $buf[2] = -1;
        $this->assertEquals(NDArray::int8,$buf->dtype());
        $this->assertTrue(is_int($buf[0]));
        $this->assertEquals(-1,$buf[2]);

        $buf = $this->factory->Buffer(3,NDArray::uint8);
        $buf[2] = -1;
        $this->assertEquals(NDArray::uint8,$buf->dtype());
        $this->assertTrue(is_int($buf[0]));
        $this->assertEquals(255,$buf[2]);

        $buf = $this->factory->Buffer(3,NDArray::int16);
        $buf[2] = -1;
        $this->assertEquals(NDArray::int16,$buf->dtype());
        $this->assertTrue(is_int($buf[0]));
        $this->assertEquals(-1,$buf[2]);

        $buf = $this->factory->Buffer(3,NDArray::uint16);
        $buf[2] = -1;
        $this->assertEquals(NDArray::uint16,$buf->dtype());
        $this->assertTrue(is_int($buf[0]));
        $this->assertEquals(65535,$buf[2]);

        $buf = $this->factory->Buffer(3,NDArray::int32);
        $buf[2] = -1;
        $this->assertEquals(NDArray::int32,$buf->dtype());
        $this->assertTrue(is_int($buf[0]));
        $this->assertEquals(-1,$buf[2]);

        $buf = $this->factory->Buffer(3,NDArray::uint32);
        $buf[2] = -1;
        $this->assertEquals(NDArray::uint32,$buf->dtype());
        $this->assertTrue(is_int($buf[0]));
        $this->assertEquals(4294967295,$buf[2]);

        $buf = $this->factory->Buffer(3,NDArray::int64);
        $buf[2] = -1;
        $this->assertEquals(NDArray::int64,$buf->dtype());
        $this->assertTrue(is_int($buf[0]));
        $this->assertEquals(-1,$buf[2]);

        $buf = $this->factory->Buffer(3,NDArray::uint64);
        $buf[2] = -1;
        $this->assertEquals(NDArray::uint64,$buf->dtype());
        $this->assertTrue(is_int($buf[0]));
        $this->assertEquals(-1,$buf[2]); // *** CAUTION ****

        $buf = $this->factory->Buffer(3,NDArray::float32);
        $buf[2] = 0.5;
        $this->assertEquals(NDArray::float32,$buf->dtype());
        $this->assertTrue(is_float($buf[0]));
        $this->assertEquals(0.5,$buf[2]);

        $buf = $this->factory->Buffer(3,NDArray::float64);
        $buf[2] = 0.5;
        $this->assertEquals(NDArray::float64,$buf->dtype());
        $this->assertTrue(is_float($buf[0]));
        $this->assertEquals(0.5,$buf[2]);

        if(!$this->notSupportComplex()) {
            $buf = $this->factory->Buffer(3,NDArray::complex64);
            $this->assertEquals(NDArray::complex64,$buf->dtype());
            $this->assertEquals(3,count($buf));
            $this->assertEquals(8,$buf->value_size());
            $this->assertEquals(NDArray::complex64,$buf->dtype());
            $buf[1] = [1.5,2.5];
            $buf[2] = (object)['real'=>3.5,'imag'=>4.5];
            $vv = $buf[1];
            $this->assertEquals(1.5,$vv->real);
            $this->assertEquals(2.5,$vv->imag);
            $vv = $buf[2];
            $this->assertEquals(3.5,$vv->real);
            $this->assertEquals(4.5,$vv->imag);
        
            $buf = $this->factory->Buffer(3,NDArray::complex128);
            $this->assertEquals(NDArray::complex128,$buf->dtype());
            $this->assertEquals(3,count($buf));
            $this->assertEquals(16,$buf->value_size());
            $this->assertEquals(NDArray::complex128,$buf->dtype());
            $buf[1] = [1.5,2.5];
            $buf[2] = (object)['real'=>3.5,'imag'=>4.5];
            $vv = $buf[1];
            $this->assertEquals(1.5,$vv->real);
            $this->assertEquals(2.5,$vv->imag);
            $vv = $buf[2];
            $this->assertEquals(3.5,$vv->real);
            $this->assertEquals(4.5,$vv->imag);
        }
    }

    public function testOffsetExists()
    {
        $buf = $this->factory->Buffer(3,NDArray::float32);
        $this->assertTrue(isset($buf[0]));
        $this->assertTrue(isset($buf[2]));
        $this->assertFalse(isset($buf[-1]));
        $this->assertFalse(isset($buf[3]));
    }

    public function testUnset()
    {
        $buf = $this->factory->Buffer(3,NDArray::float32);
        $buf[0] = 1;
        $this->assertEquals(1,$buf[0]);
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Illigal Operation');
        unset($buf[0]); // unset means set zero
        //$this->assertEquals(0,$buf[0]);
    }

    public function testDumpAndLoad()
    {
        $buf = $this->factory->Buffer(3,NDArray::float32);
        $buf[0] = 1;
        $buf[1] = 2;
        $buf[2] = 3;

        $buf2 = $this->factory->Buffer(3,NDArray::float32);
        $buf2[0] = 0;
        $buf2[1] = 0;
        $buf2[2] = 0;

        $dump = $buf->dump();
        $buf2->load($dump);
        $this->assertEquals(1,$buf2[0]);
        $this->assertEquals(2,$buf2[1]);
        $this->assertEquals(3,$buf2[2]);
    }

    public function testSetOutOfBoundsWithHighOffset()
    {
        //$buf = new \SplFixedArray(3);
        $buf = $this->factory->Buffer(3,NDArray::float32);
        $this->expectException(OutOfRangeException::class);
        $this->expectExceptionMessage('Index invalid or out of range');
        $buf[3] = 1;
    }

    public function testSetOutOfBoundsWithLowOffset()
    {
        //$buf = new \SplFixedArray(3);
        $buf = $this->factory->Buffer(3,NDArray::float32);
        $this->expectException(OutOfRangeException::class);
        $this->expectExceptionMessage('Index invalid or out of range');
        $buf[-1] = 1;
    }

    public function testGetOutOfBoundsWithHighOffset()
    {
        //$buf = new \SplFixedArray(3);
        $buf = $this->factory->Buffer(3,NDArray::float32);
        $this->expectException(OutOfRangeException::class);
        $this->expectExceptionMessage('Index invalid or out of range');
        $x = $buf[3];
    }

    public function testGetOutOfBoundsWithLowOffset()
    {
        //$buf = new \SplFixedArray(3);
        $buf = $this->factory->Buffer(3,NDArray::float32);
        $this->expectException(OutOfRangeException::class);
        $this->expectExceptionMessage('Index invalid or out of range');
        $x = $buf[-1];
    }

    public function testUnsetOutOfBoundsWithHighOffset()
    {
        //$buf = new \SplFixedArray(3);
        $buf = $this->factory->Buffer(3,NDArray::float32);
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Illigal Operation');
        unset($buf[3]);
        //$this->assertTrue(true);
    }

    public function testUnsetOutOfBoundsWithLowOffset()
    {
        //$buf = new \SplFixedArray(3);
        $buf = $this->factory->Buffer(3,NDArray::float32);
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Illigal Operation');
        unset($buf[-1]);
        //$this->assertTrue(true);
    }

    public function testIsExistsOutOfBoundsWithHighOffset()
    {
        //$buf = new \SplFixedArray(3);
        $buf = $this->factory->Buffer(3,NDArray::float32);
        $this->assertFalse(isset($buf[3]));
    }

    public function testIsExistsOutOfBoundsWithLowOffset()
    {
        //$buf = new \SplFixedArray(3);
        $buf = $this->factory->Buffer(3,NDArray::float32);
        $this->assertFalse(isset($buf[-1]));
    }

    public function testOffsetSetWithNoOffset()
    {
        $buf = $this->factory->Buffer(3,NDArray::float32);
        $this->expectException(ArgumentCountError::class);
        //if(version_compare(PHP_VERSION, '8.0.0')<0) {
        //    $this->expectExceptionMessage('offsetSet() expects exactly 2 parameters, 0 given');
        //} else {
        //    $this->expectExceptionMessage('offsetSet() expects exactly 2 arguments, 0 given');
        //}
        $this->expectExceptionMessage('Too few arguments to function');
        $a = $buf->offsetSet();
    }

    public function testOffsetSetIllegalTypeOffset()
    {
        $buf = $this->factory->Buffer(3,NDArray::float32);
        $this->expectException(TypeError::class);
        if(version_compare(PHP_VERSION, '8.0.0')<0) {
            $this->expectExceptionMessage('offsetSet() expects parameter 1 to be int');
        } else {
            $this->expectExceptionMessage('offsetSet(): Argument #1 ($offset) must be of type int');
        }
        $buf->offsetSet(new \stdClass(),1);
    }

    public function testOffsetGetWithNoOffset()
    {
        $buf = $this->factory->Buffer(3,NDArray::float32);
        $this->expectException(ArgumentCountError::class);
        //if(version_compare(PHP_VERSION, '8.0.0')<0) {
        //    $this->expectExceptionMessage('offsetGet() expects exactly 1 parameter, 0 given');
        //} else {
        //    $this->expectExceptionMessage('offsetGet() expects exactly 1 argument, 0 given');
        //}
        $this->expectExceptionMessage('Too few arguments to function');
        $a = $buf->offsetGet();
    }

    public function testOffsetGetIllegalType()
    {
        $buf = $this->factory->Buffer(3,NDArray::float32);
        $this->expectException(TypeError::class);
        if(version_compare(PHP_VERSION, '8.0.0')<0) {
            $this->expectExceptionMessage('offsetGet() expects parameter 1 to be int');
        } else {
            $this->expectExceptionMessage('offsetGet(): Argument #1 ($offset) must be of type int');
        }
        $a = $buf->offsetGet(new \stdClass());
    }

    public function testOffsetUnsetWithNoOffset()
    {
        $buf = $this->factory->Buffer(3,NDArray::float32);
        $this->expectException(ArgumentCountError::class);
        //if(version_compare(PHP_VERSION, '8.0.0')<0) {
        //    $this->expectExceptionMessage('offsetUnset() expects exactly 1 parameter, 0 given');
        //} else {
        //    $this->expectExceptionMessage('offsetUnset() expects exactly 1 argument, 0 given');
        //}
        $this->expectExceptionMessage('Too few arguments to function');
        $buf->offsetUnset();
    }

    public function testOffsetUnsetIllegalType()
    {
        $buf = $this->factory->Buffer(3,NDArray::float32);
        //$this->expectException(TypeError::class);
        //if(version_compare(PHP_VERSION, '8.0.0')<0) {
        //    $this->expectExceptionMessage('offsetUnset() expects parameter 1 to be int');
        //} else {
        //    $this->expectExceptionMessage('offsetUnset(): Argument #1 ($offset) must be of type int');
        //}
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Illigal Operation');
        $buf->offsetUnset(new \stdClass());
    }

    public function testLoadWithNoOffset()
    {
        $buf = $this->factory->Buffer(3,NDArray::float32);
        $this->expectException(ArgumentCountError::class);
        //if(version_compare(PHP_VERSION, '8.0.0')<0) {
        //    $this->expectExceptionMessage('load() expects exactly 1 parameter, 0 given');
        //} else {
        //    $this->expectExceptionMessage('load() expects exactly 1 argument, 0 given');
        //}
        $this->expectExceptionMessage('Too few arguments to function');
        $buf->load();
    }

    public function testLoadIllegalType()
    {
        $buf = $this->factory->Buffer(3,NDArray::float32);
        $this->expectException(TypeError::class);
        if(version_compare(PHP_VERSION, '8.0.0')<0) {
            $this->expectExceptionMessage('load() expects parameter 1 to be string');
        } else {
            //$this->expectExceptionMessage('load(): Argument #1 must be of type string');
            $this->expectExceptionMessage('load(): Argument #1 ($string) must be of type');
        }
        $buf->load(new \stdClass());
    }

    public function testConstractWithNoArgument()
    {
        $this->expectException(ArgumentCountError::class);
        //if(version_compare(PHP_VERSION, '8.0.0')<0) {
        //    $this->expectExceptionMessage('__construct() expects exactly 2 parameters, 0 given');
        //} else {
        //    $this->expectExceptionMessage('__construct() expects exactly 2 arguments, 0 given');
        //}
        $this->expectExceptionMessage('Too few arguments to function');
        $buf = $this->factory->Buffer();
    }

    public function testConstractIllegalType()
    {
        $this->expectException(TypeError::class);
        if(version_compare(PHP_VERSION, '8.0.0')<0) {
            $this->expectExceptionMessage('BufferFactory::Buffer() expects parameter 1 to be int');
        } else {
            $this->expectExceptionMessage('BufferFactory::Buffer(): Argument #1 ($size) must be of type int');
        }
        $buf = $this->factory->Buffer(new \stdClass(),NDArray::float32);
    }
    
    public function testAddr()
    {
        $buf = $this->factory->Buffer(4,NDArray::int32);
        $buf[0] = 10;
        $buf[1] = 11;
        $buf[2] = 12;
        $buf[3] = 13;
        $addr = $buf->addr(1);
        $this->assertInstanceOf(FFI\CData::class,$addr);
        $this->assertEquals(11,$addr[0]);
        $this->assertEquals(12,$addr[1]);
        $this->assertEquals(13,$addr[2]);
    }

    public function testClone()
    {
        $buf = $this->factory->Buffer(4,NDArray::int32);
        $buf[0] = 10;
        $buf[1] = 11;
        $buf[2] = 12;
        $buf[3] = 13;
        $clone = clone $buf;
        $buf[0] = 0;
        $buf[1] = 1;
        $buf[2] = 2;
        $buf[3] = 3;

        $this->assertEquals(10,$clone[0]);
        $this->assertEquals(11,$clone[1]);
        $this->assertEquals(12,$clone[2]);
        $this->assertEquals(13,$clone[3]);
    }
}
