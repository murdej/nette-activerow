<?php

namespace Murdej\ActiveRow;

trait TSqlCodeComposer
{
    protected $vars = [];

    protected $query = "";

    public function value($val)
    {
        $this->query .= '? ';
        $this->vars[] = $val;
        return $this;
    }

    public function values($vals, $sep = ', '): static
    {
        $f = true;
        foreach ($vals as $val) {
            if ($f) $f = false;
            else $this->query .= $sep;
            $this->query .= '? ';
            $this->vars[] = $val;
        }
        return $this;
    }

    public function code($code, ...$vars): static
    {
        $this->query .= $code;
        $this->vars = array_merge($this->vars, $vars);
        return $this;
    }

    public function identifier($code): static
    {
        //todo: escape identifier name
        $this->query .= $code;
        return $this;
    }

    public function codeIfNotNull($code, $var): static
    {
        if ($var !== null) {
            $this->c($code, $var);
        }

        return $this;
    }

    public function c($code, ...$vars): static { return $this->code($code, ...$vars); }

    public function i($code): static { return $this->identifier($code); }

    public function v($value): static { return $this->value($value); }

}