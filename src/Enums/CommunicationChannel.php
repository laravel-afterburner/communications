<?php

namespace Afterburner\Communications\Enums;

enum CommunicationChannel: string
{
    case Email = 'email';
    case InApp = 'in_app';
    case System = 'system';
}
