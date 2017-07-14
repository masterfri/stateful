<?php

namespace Masterfri\Stateful;

class Signal
{
    /**
     * Signal type
     * 
     * @var string
     */ 
    protected $type;
    
    /**
     * Additional arguments
     * 
     * @var Illuminate\Support\Collection
     */ 
    protected $args;

    /**
     * Constructor
     * 
     * @param string $type
     * @param mixed $args
     */ 
    public function __construct($type, $args=[])
    {
        $this->type = $type;
        $this->args = collect($args);
    }

    /**
     * Get signal type
     * 
     * @return string
     */ 
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get additional arguments
     * 
     * @return array
     */ 
    public function getArguments()
    {
        return $this->args;
    }
}