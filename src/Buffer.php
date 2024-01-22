<?php
namespace Rindow\Math\Buffer\FFI;

use Interop\Polite\Math\Matrix\LinearBuffer;
use Interop\Polite\Math\Matrix\NDArray;
use TypeError;
use InvalidArgumentException;
use OutOfRangeException;
use LogicException;
use FFI;

class Buffer implements LinearBuffer
{
    protected static $typeString = [
        NDArray::bool    => 'uint8_t',
        NDArray::int8    => 'int8_t',
        NDArray::int16   => 'int16_t',
        NDArray::int32   => 'int32_t',
        NDArray::int64   => 'int64_t',
        NDArray::uint8   => 'uint8_t',
        NDArray::uint16  => 'uint16_t',
        NDArray::uint32  => 'uint32_t',
        NDArray::uint64  => 'uint64_t',
        //NDArray::float8  => 'N/A',
        //NDArray::float16 => 'N/A',
        NDArray::float32 => 'float',
        NDArray::float64 => 'double',
    ];
    protected static $valueSize = [
        NDArray::bool    => 1,
        NDArray::int8    => 1,
        NDArray::int16   => 2,
        NDArray::int32   => 4,
        NDArray::int64   => 8,
        NDArray::uint8   => 1,
        NDArray::uint16  => 2,
        NDArray::uint32  => 4,
        NDArray::uint64  => 8,
        //NDArray::float8  => 'N/A',
        //NDArray::float16 => 'N/A',
        NDArray::float32 => 4,
        NDArray::float64 => 8,
    ];

    protected int $size;
    protected int $dtype;
    protected object $data;

    public function __construct(int $size, int $dtype)
    {
        if(!isset(self::$typeString[$dtype])) {
            throw new InvalidArgumentException("Invalid data type");
        }
        $this->size = $size;
        $this->dtype = $dtype;
        $declaration = self::$typeString[$dtype];
        $this->data = FFI::new("{$declaration}[{$size}]");
    }

    protected function assertOffset($method, mixed $offset) : void
    {
        if(!is_int($offset)) {
            throw new TypeError($method.'(): Argument #1 ($offset) must be of type int');
        }
        if($offset<0 || $offset>=$this->size) {
            throw new OutOfRangeException($method.'(): Index invalid or out of range');
        }
    }

    protected function assertOffsetIsInt($method, mixed $offset) : void
    {
        if(!is_int($offset)) {
            throw new TypeError($method.'(): Argument #1 ($offset) must be of type int');
        }
    }

    public function dtype() : int
    {
        return $this->dtype;
    }

    public function value_size() : int
    {
        return $this::$valueSize[$this->dtype];
    }

    public function addr(int $offset) : FFI\CData
    {
        return FFI::addr($this->data[$offset]);
    }

    public function count() : int
    {
        return $this->size;
    }

    public function offsetExists(mixed $offset) : bool
    {
        $this->assertOffsetIsInt('offsetExists',$offset);
        return ($offset>=0)&&($offset<$this->size);
    }

    public function offsetGet(mixed $offset): mixed
    {
        $this->assertOffset('offsetGet',$offset);
        $value = $this->data[$offset];
        if($this->dtype===NDArray::bool) {
            $value = $value ? true : false;
        }
        return $value;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->assertOffset('offsetSet',$offset);
        $this->data[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        //$this->assertOffsetIsInt('offsetUnset',$offset);
        throw new LogicException("Illigal Operation");
    }

    public function dump() : string
    {
        $byte = self::$valueSize[$this->dtype] * $this->size;
        $buf = FFI::new("char[$byte]");
        FFI::memcpy($buf,$this->data,$byte);
        return FFI::string($buf,$byte);
    }

    public function load(string $string) : void
    {
        $byte = self::$valueSize[$this->dtype] * $this->size;
        $strlen = strlen($string);
        if($strlen!=$byte) {
            throw new InvalidArgumentException("Unmatch data size. buffer size is $byte. $strlen byte given.");
        }
        FFI::memcpy($this->data,$string,$byte);
    }
}
