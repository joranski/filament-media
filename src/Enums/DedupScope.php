<?php

declare(strict_types=1);

namespace Joranski\FilamentMedia\Enums;

enum DedupScope: string
{
    case Global = 'global';
    case Model = 'model';
    case Collection = 'collection';
}
