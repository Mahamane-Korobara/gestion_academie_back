<?php

namespace App\Enums;

enum TypeDocument: string
{
    case PDF = 'pdf';
    case WORD = 'word';
    case EXCEL = 'excel';
    case POWERPOINT = 'powerpoint';
    case IMAGE = 'image';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function mimeTypes(): array
    {
        return [
            'pdf' => ['application/pdf'],
            'word' => ['application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'excel' => ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'powerpoint' => ['application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'],
            'image' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        ];
    }

    public static function extensions(): array
    {
        return [
            'pdf' => ['pdf'],
            'word' => ['doc', 'docx'],
            'excel' => ['xls', 'xlsx'],
            'powerpoint' => ['ppt', 'pptx'],
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        ];
    }
}