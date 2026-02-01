<?php

declare(strict_types=1);

namespace Shanginn\CloudflareBrowser\Enums;

enum PdfFormat: string
{
    case A0 = 'A0';
    case A1 = 'A1';
    case A2 = 'A2';
    case A3 = 'A3';
    case A4 = 'A4';
    case A5 = 'A5';
    case A6 = 'A6';
    case LETTER = 'Letter';
    case LEGAL = 'Legal';
    case TABLOID = 'Tabloid';
    case LEDGER = 'Ledger';
}
