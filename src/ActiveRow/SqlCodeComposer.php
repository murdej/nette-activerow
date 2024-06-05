<?php

namespace Murdej\ActiveRow;

class SqlCodeComposer
{
    use TSqlCodeComposer;

    public function getCodeVars(bool $asAssoc = true): array
    {
        return $asAssoc
            ? (
                $this->query
                ? [ $this->query => $this->vars ]
                : []
            )
            : [ $this->query, $this->vars ];
    }
}