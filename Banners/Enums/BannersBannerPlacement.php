<?php

namespace Modules\Banners\Enums;

use BenSampo\Enum\Enum;

final class BannersBannerPlacement extends Enum
{
    const HELP_TEXT = 'HELP_TEXT';
    const CENTER_TOP = 'CENTER_TOP';
    const CENTER_BOTTOM = 'CENTER_BOTTOM';
    const RIGHT_TOP = 'RIGHT_TOP';
    const RIGHT_BOTTOM = 'RIGHT_BOTTOM';
    const EMPTY_LIST = 'EMPTY_LIST';
    const ONBOARDING = 'ONBOARDING';
    const CHECKLIST = 'CHECKLIST';
}
