<?php

declare(strict_types=1);

namespace Shanginn\CloudflareBrowser\Enums;

enum WaitUntil: string
{
    case LOAD = 'load';
    case DOM_CONTENT_LOADED = 'domcontentloaded';
    case NETWORK_IDLE = 'networkidle';
}
