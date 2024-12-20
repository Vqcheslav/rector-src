<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnUnionTypeRector;
use Rector\ValueObject\PhpVersionFeature;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(ReturnUnionTypeRector::class);
    $rectorConfig->phpVersion(PhpVersionFeature::UNION_INTERSECTION_TYPES);
};
