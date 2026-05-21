<?php

declare(strict_types=1);

namespace App\Repository\ORM\Functions;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\TokenType;


class CubeContains extends FunctionNode
{
    public $corner1 = null;
    public $corner2 = null;
    public $point = null;

    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
        $this->corner1 = $parser->ArithmeticExpression();
        $parser->match(TokenType::T_COMMA);
        $this->corner2 = $parser->ArithmeticExpression();
        $parser->match(TokenType::T_COMMA);
        $this->point = $parser->ArithmeticExpression();
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        return sprintf(
            '%s <@ cube_union(%s::cube, %s::cube)',
            $this->point->dispatch($sqlWalker),
            $this->corner1->dispatch($sqlWalker),
            $this->corner2->dispatch($sqlWalker)
        );
    }
}