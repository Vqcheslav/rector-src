<?php

declare(strict_types=1);

namespace Rector\DeadCode\PhpDoc;

use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Param;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use Rector\BetterPhpDocParser\PhpDocManipulator\PhpDocTypeChanger;
use Rector\BetterPhpDocParser\ValueObject\Type\BracketsAwareUnionTypeNode;
use Rector\DeadCode\TypeNodeAnalyzer\GenericTypeNodeAnalyzer;
use Rector\DeadCode\TypeNodeAnalyzer\MixedArrayTypeNodeAnalyzer;
use Rector\NodeNameResolver\NodeNameResolver;
use Rector\NodeTypeResolver\TypeComparator\TypeComparator;
use Rector\TypeDeclaration\NodeAnalyzer\ParamAnalyzer;

final readonly class DeadParamTagValueNodeAnalyzer
{
    public function __construct(
        private NodeNameResolver $nodeNameResolver,
        private TypeComparator $typeComparator,
        private GenericTypeNodeAnalyzer $genericTypeNodeAnalyzer,
        private MixedArrayTypeNodeAnalyzer $mixedArrayTypeNodeAnalyzer,
        private ParamAnalyzer $paramAnalyzer,
        private PhpDocTypeChanger $phpDocTypeChanger,
    ) {
    }

    public function isDead(ParamTagValueNode $paramTagValueNode, FunctionLike $functionLike): bool
    {
        $param = $this->paramAnalyzer->getParamByName($paramTagValueNode->parameterName, $functionLike);
        if (! $param instanceof Param) {
            return false;
        }

        if ($param->type === null) {
            return false;
        }

        if ($paramTagValueNode->description !== '') {
            return false;
        }

        if ($paramTagValueNode->type instanceof UnionTypeNode && $param->type instanceof FullyQualified) {
            return false;
        }

        if ($param->type instanceof Name && $this->nodeNameResolver->isName($param->type, 'object')) {
            return $paramTagValueNode->type instanceof IdentifierTypeNode && (string) $paramTagValueNode->type === 'object';
        }

        if (! $this->typeComparator->arePhpParserAndPhpStanPhpDocTypesEqual(
            $param->type,
            $paramTagValueNode->type,
            $functionLike
        )) {
            return false;
        }

        if ($this->phpDocTypeChanger->isAllowed($paramTagValueNode->type)) {
            return false;
        }

        if (! $paramTagValueNode->type instanceof BracketsAwareUnionTypeNode) {
            return true;
        }

        return $this->isAllowedBracketAwareUnion($paramTagValueNode->type);
    }

    private function isAllowedBracketAwareUnion(BracketsAwareUnionTypeNode $bracketsAwareUnionTypeNode): bool
    {
        if ($this->mixedArrayTypeNodeAnalyzer->hasMixedArrayType($bracketsAwareUnionTypeNode)) {
            return false;
        }

        return ! $this->genericTypeNodeAnalyzer->hasGenericType($bracketsAwareUnionTypeNode);
    }
}
