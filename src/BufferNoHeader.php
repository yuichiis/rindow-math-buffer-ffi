<?php
namespace Rindow\Math\Buffer\FFI;

use FFI;
use InvalidArgumentException;
use Interop\Polite\Math\Matrix\NDArray;

class BufferNoHeader extends Buffer
{
    public function __construct(int $size, int $dtype)
    {
        if(parent::$ffi===null) {
            parent::$ffi = FFI::cdef('');
        }
        if(!isset(parent::$typeString[$dtype])) {
            throw new InvalidArgumentException("Invalid data type");
        }
        $limitsize = intdiv(parent::MAX_BYTES,parent::$valueSize[$dtype]);
        if($size>=$limitsize) {
            throw new InvalidArgumentException("Data size is too large.");
        }
        $this->size = $size;
        $this->dtype = $dtype;
        switch($dtype) {
            case NDArray::complex64: {
                $declaration = parent::$typeString[NDArray::float32];
                $size *= 2;
                break;
            }
            case NDArray::complex128: {
                $declaration = parent::$typeString[NDArray::float64];
                $size *= 2;
                break;
            }
            default: {
                $declaration = parent::$typeString[$dtype];
                break;
            }
        }
        $this->data = parent::$ffi->new("{$declaration}[{$size}]");
    }

    public function addr(int $offset) : FFI\CData
    {
        if($this->isComplex()) {
            $offset *= 2;
        }
        return FFI::addr($this->data[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        $this->assertOffset('offsetGet',$offset);
        if($this->isComplex()) {
            $offset *= 2;
            $real = $this->data[$offset];
            $imag = $this->data[$offset+1];
            $value = (object)['real'=>$real,'imag'=>$imag];
        } else {
            $value = $this->data[$offset];
        }
        if($this->dtype===NDArray::bool) {
            $value = $value ? true : false;
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
                $real = $value->real;
                $imag = $value->imag;
            } else {
                $type = gettype($value);
                throw new InvalidArgumentException("Cannot convert to complex number.: ".$type);
            }
            $offset *= 2;
            $this->data[$offset] = $real;
            $this->data[$offset+1] = $imag;
        } else {
            $this->data[$offset] = $value;
        }
    }
}
