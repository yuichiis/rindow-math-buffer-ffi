<?php
namespace Rindow\Math\Buffer\FFI;

use Interop\Polite\Math\Matrix\LinearBuffer;
use Interop\Polite\Math\Matrix\NDArray;
use TypeError;
use InvalidArgumentException;
use OutOfRangeException;
use LogicException;
use RuntimeException;
use FFI;

class complex_t {
    public float $real;
    public float $imag;
}

class Buffer implements LinearBuffer
{
    const MAX_BYTES = 2147483648; // 2**31
    static protected ?FFI $ffi = null;

    /** @var array<int,string> $typeString */
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
        //NDArray::complex16 => 'N/A',
        //NDArray::complex32 => 'N/A',
        NDArray::complex64 => 'rindow_complex_float',
        NDArray::complex128  => 'rindow_complex_double',
    ];
    /** @var array<int,int> $valueSize */
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
        //NDArray::complex16 => 'N/A',
        //NDArray::complex32 => 'N/A',
        NDArray::complex64 => 8,
        NDArray::complex128  => 16,
    ];

    protected int $size;
    protected int $dtype;
    protected object $data;

    public function __construct(int $size, int $dtype)
    {
        if ($size <= 0) {
            throw new InvalidArgumentException("Size must be positive");
        }

        if (self::$ffi === null) {
            $header = __DIR__ . '/buffer.h';
            $code = @file_get_contents($header);
            if ($code === false) {
                throw new RuntimeException("Unable to read buffer.h file");
            }
            self::$ffi = FFI::cdef($code);
        }

        if(!isset(self::$typeString[$dtype])) {
            throw new InvalidArgumentException("Invalid data type");
        }
        $limitsize = intdiv(self::MAX_BYTES,self::$valueSize[$dtype]);
        if($size>=$limitsize) {
            throw new InvalidArgumentException("Data size is too large.");
        }
        $this->size = $size;
        $this->dtype = $dtype;
        $declaration = self::$typeString[$dtype];
        //$size = $this->aligned($size,$dtype,16); // 128bit
        $this->data = self::$ffi->new("{$declaration}[{$size}]");
    }

    //protected function aligned(int $size, int $dtype,int $base) : int
    //{
    //    $valueSize = self::$valueSize[$dtype];
    //    $bytes = $size*$valueSize;
    //    $alignedBytes = intdiv(($bytes+$base-1),$base)*$base;
    //    $alignedSize = intdiv(($alignedBytes+$valueSize-1),$valueSize)*$valueSize;
    //    return $alignedSize;
    //}

    protected function assertOffset(string $method, mixed $offset) : void
    {
        if(!is_int($offset)) {
            throw new TypeError($method.'(): Argument #1 ($offset) must be of type int');
        }
        if($offset<0 || $offset>=$this->size) {
            throw new OutOfRangeException($method.'(): Index invalid or out of range');
        }
    }

    protected function assertOffsetIsInt(string $method, mixed $offset) : void
    {
        if(!is_int($offset)) {
            throw new TypeError($method.'(): Argument #1 ($offset) must be of type int');
        }
    }

    protected function isComplex(?int $dtype=null) : bool
    {
        $dtype = $dtype ?? $this->dtype;
        return $dtype === NDArray::complex64 || $dtype === NDArray::complex128;
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
            // CData (uint8_t) to PHP bool
            $value = $value !== 0; // Anything other than 0 is true
        } elseif ($this->isComplex()) {
            // Convert the FFI structure to a PHP object and return it (if you want to follow the existing behavior)
            // If necessary, you can convert it to a PHP complex_t here, but
            // You can also return the FFI CData object as is and access it with ->real, ->imag
            // return (object)['real' => $value->real, 'imag' => $value->imag];
        }
        return $value;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->assertOffset('offsetSet',$offset);
        if($this->isComplex()) {
            if(is_array($value)) {
                [$real,$imag] = $value;
            } elseif(is_object($value)) {
                // Consider types other than complex_t (as long as they have properties)
                if (!property_exists($value, 'real') || !property_exists($value, 'imag')) {
                    throw new InvalidArgumentException("Complex object must have 'real' and 'imag' properties.");
                }
                $real = $value->real;
                $imag = $value->imag;
            } else {
                $type = gettype($value);
                throw new InvalidArgumentException("Cannot convert to complex number.: ".$type);
            }
            $this->data[$offset]->real = (float)$real;
            $this->data[$offset]->imag = (float)$imag;
        } else {
            // Check if bool and convert PHP bool to int (0 or 1)
            if ($this->dtype === NDArray::bool) {
                $value = $value ? 1 : 0;
            }
            $this->data[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        //$this->assertOffsetIsInt('offsetUnset',$offset);
        throw new LogicException("Illigal Operation");
    }

    public function dump() : string
    {
        $byte = self::$valueSize[$this->dtype] * $this->size;
        if ($byte === 0) {
            return '';
        }
        // $alignedBytes = $this->aligned($byte,NDArray::int8,128);
        // $buf = self::$ffi->new("char[$alignedBytes]");
        // FFI::memcpy($buf,$this->data,$byte);
        // return FFI::string($buf,$byte);
        $ptr = FFI::addr($this->data[0]);   
        return FFI::string(FFI::cast('char*', $ptr), $byte);
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

    public function __clone()
    {
        $this->data = clone $this->data;
    }
}
